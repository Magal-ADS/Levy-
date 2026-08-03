<?php
// ==========================================================
// IMPORTADOR CSV -> POSTGRESQL
// ==========================================================
// Uso:
//   php import_csv_seed.php
//   php import_csv_seed.php --dir=./csv --truncate
// ==========================================================

function carregarEnv(string $basePath): void
{
    $envFile = $basePath . '/.env';
    if (!file_exists($envFile)) {
        return;
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if (getenv($key) !== false) {
            continue;
        }

        putenv($key . '=' . trim($value));
    }
}

function parseArgs(array $argv): array
{
    $options = [
        'dir' => __DIR__,
        'truncate' => false,
    ];

    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--truncate') {
            $options['truncate'] = true;
            continue;
        }

        if (strpos($arg, '--dir=') === 0) {
            $options['dir'] = substr($arg, 6);
        }
    }

    return $options;
}

function normalizarCabecalho(string $valor): string
{
    $valor = preg_replace('/^\xEF\xBB\xBF/', '', $valor);
    return trim($valor, " \t\n\r\0\x0B\"'");
}

function detectarDelimitador(string $primeiraLinha): string
{
    $delimitadores = [';', ',', "\t", '|'];
    $melhor = ';';
    $maiorContagem = -1;

    foreach ($delimitadores as $delimitador) {
        $contagem = substr_count($primeiraLinha, $delimitador);
        if ($contagem > $maiorContagem) {
            $maiorContagem = $contagem;
            $melhor = $delimitador;
        }
    }

    return $melhor;
}

function buscarCsvs(string $diretorio, array $tabelas): array
{
    $mapaArquivos = [];
    $itens = scandir($diretorio);

    if ($itens === false) {
        return $mapaArquivos;
    }

    foreach ($itens as $item) {
        $caminho = $diretorio . DIRECTORY_SEPARATOR . $item;
        if (!is_file($caminho)) {
            continue;
        }

        if (strtolower(pathinfo($item, PATHINFO_EXTENSION)) !== 'csv') {
            continue;
        }

        $nomeBase = strtolower(pathinfo($item, PATHINFO_FILENAME));
        $nomeBase = preg_replace('/_rows$/', '', $nomeBase);
        if (in_array($nomeBase, $tabelas, true)) {
            $mapaArquivos[$nomeBase] = $caminho;
        }
    }

    return $mapaArquivos;
}

function buscarColunasTabela(PDO $pdo, string $tabela): array
{
    $sql = "
        SELECT column_name, data_type
        FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = ?
        ORDER BY ordinal_position
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tabela]);

    $colunas = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $coluna) {
        $colunas[$coluna['column_name']] = $coluna['data_type'];
    }

    return $colunas;
}

function normalizarValor(?string $valor, ?string $tipoColuna)
{
    if ($valor === null) {
        return null;
    }

    $valor = trim($valor);
    if ($valor === '' || strcasecmp($valor, 'null') === 0) {
        return null;
    }

    if ($tipoColuna !== null) {
        $tiposNumericos = [
            'smallint',
            'integer',
            'bigint',
            'numeric',
            'decimal',
            'real',
            'double precision',
        ];

        if (in_array($tipoColuna, $tiposNumericos, true)) {
            $valorNormalizado = str_replace('.', '', $valor);
            if (preg_match('/^-?\d+,\d+$/', $valor)) {
                return str_replace(',', '.', $valor);
            }
            if (preg_match('/^-?\d{1,3}(\.\d{3})+,\d+$/', $valor)) {
                return str_replace(',', '.', $valorNormalizado);
            }
        }
    }

    return $valor;
}

function importarTabela(PDO $pdo, string $tabela, string $arquivoCsv): int
{
    $colunasTabela = buscarColunasTabela($pdo, $tabela);
    if ($colunasTabela === []) {
        throw new RuntimeException("Tabela '$tabela' nao encontrada no banco.");
    }

    $handle = fopen($arquivoCsv, 'rb');
    if ($handle === false) {
        throw new RuntimeException("Nao foi possivel abrir o arquivo: $arquivoCsv");
    }

    $primeiraLinha = fgets($handle);
    if ($primeiraLinha === false) {
        fclose($handle);
        return 0;
    }

    $delimitador = detectarDelimitador($primeiraLinha);
    rewind($handle);

    $cabecalho = fgetcsv($handle, 0, $delimitador);
    if ($cabecalho === false || $cabecalho === [null]) {
        fclose($handle);
        return 0;
    }

    $cabecalho = array_map('normalizarCabecalho', $cabecalho);
    $mapeamentoColunas = [];
    $colunasIgnoradas = [];

    foreach ($cabecalho as $indice => $coluna) {
        if (!array_key_exists($coluna, $colunasTabela)) {
            $colunasIgnoradas[] = $coluna;
            continue;
        }

        $mapeamentoColunas[] = [
            'indice' => $indice,
            'coluna' => $coluna,
        ];
    }

    if ($mapeamentoColunas === []) {
        fclose($handle);
        throw new RuntimeException("Nenhuma coluna do arquivo '$arquivoCsv' existe na tabela '$tabela'.");
    }

    $colunasImportadas = array_column($mapeamentoColunas, 'coluna');
    $usuarioIdPadrao = null;
    $tabelasComDono = ['pessoas', 'cartoes', 'categorias', 'transacoes', 'divisoes_transacao', 'contas_fixas'];
    if (in_array($tabela, $tabelasComDono, true) && !in_array('usuario_id', $colunasImportadas, true)) {
        $usuarioIdPadrao = $pdo->query("SELECT id FROM usuarios ORDER BY id ASC LIMIT 1")->fetchColumn();
        if (!$usuarioIdPadrao) {
            fclose($handle);
            throw new RuntimeException("O CSV legado de '$tabela' nao possui usuario_id e nenhum usuario proprietario foi importado.");
        }
        $colunasImportadas[] = 'usuario_id';
    }

    $placeholders = implode(', ', array_fill(0, count($colunasImportadas), '?'));
    $colunasSql = implode(', ', array_map(static fn(string $coluna): string => '"' . $coluna . '"', $colunasImportadas));
    $sql = "INSERT INTO {$tabela} ({$colunasSql}) VALUES ({$placeholders})";
    $stmt = $pdo->prepare($sql);

    $linhasImportadas = 0;

    while (($linha = fgetcsv($handle, 0, $delimitador)) !== false) {
        if ($linha === [null]) {
            continue;
        }

        $valores = [];

        foreach ($mapeamentoColunas as $colunaMapeada) {
            $indice = $colunaMapeada['indice'];
            $coluna = $colunaMapeada['coluna'];
            $valores[] = normalizarValor($linha[$indice] ?? null, $colunasTabela[$coluna] ?? null);
        }

        if ($usuarioIdPadrao !== null) {
            $valores[] = (int) $usuarioIdPadrao;
        }

        $stmt->execute($valores);
        $linhasImportadas++;
    }

    fclose($handle);

    if ($colunasIgnoradas !== []) {
        echo "[--] {$tabela}: colunas ignoradas do CSV -> " . implode(', ', $colunasIgnoradas) . "\n";
    }

    return $linhasImportadas;
}

function atualizarSequence(PDO $pdo, string $tabela): void
{
    $sql = "SELECT setval(
        pg_get_serial_sequence(?, 'id'),
        COALESCE((SELECT MAX(id) FROM {$tabela}), 1),
        (SELECT COUNT(*) > 0 FROM {$tabela})
    )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tabela]);
}

carregarEnv(__DIR__);
$options = parseArgs($argv);

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '5432';
$user = getenv('DB_USER') ?: 'postgres';
$pass = getenv('DB_PASS') ?: 'postgres';
$db = getenv('DB_NAME') ?: 'financeiro';

$diretorioCsv = $options['dir'];
if (!preg_match('/^[A-Za-z]:\\\\|^\//', $diretorioCsv)) {
    $diretorioCsv = realpath(__DIR__ . DIRECTORY_SEPARATOR . $diretorioCsv) ?: (__DIR__ . DIRECTORY_SEPARATOR . $diretorioCsv);
}

if (!is_dir($diretorioCsv)) {
    fwrite(STDERR, "Diretorio de CSVs nao encontrado: {$diretorioCsv}\n");
    exit(1);
}

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "ERRO ao conectar: " . $e->getMessage() . "\n");
    exit(1);
}

$tabelas = [
    'usuarios',
    'pessoas',
    'cartoes',
    'categorias',
    'transacoes',
    'divisoes_transacao',
    'contas_fixas',
];

$arquivosEncontrados = buscarCsvs($diretorioCsv, $tabelas);

if ($arquivosEncontrados === []) {
    fwrite(STDERR, "Nenhum CSV encontrado em {$diretorioCsv}. Use nomes como usuarios.csv, transacoes.csv, etc.\n");
    exit(1);
}

echo "Conectado ao PostgreSQL: {$db}\n";
echo "Diretorio dos CSVs: {$diretorioCsv}\n";
echo str_repeat('-', 60) . "\n";

if ($options['truncate']) {
    echo "Limpando tabelas antes da importacao...\n";
    $pdo->exec('TRUNCATE TABLE tentativas_login, usuarios, pessoas, cartoes, categorias, transacoes, divisoes_transacao, contas_fixas RESTART IDENTITY CASCADE');
}

$totalArquivos = 0;
$totalLinhas = 0;

foreach ($tabelas as $tabela) {
    if (!isset($arquivosEncontrados[$tabela])) {
        echo "[--] {$tabela}: CSV nao encontrado, ignorando.\n";
        continue;
    }

    $arquivo = $arquivosEncontrados[$tabela];
    echo "[..] {$tabela}: importando " . basename($arquivo) . "\n";

    $pdo->beginTransaction();

    try {
        $linhas = importarTabela($pdo, $tabela, $arquivo);
        atualizarSequence($pdo, $tabela);
        $pdo->commit();

        $totalArquivos++;
        $totalLinhas += $linhas;

        echo "[OK] {$tabela}: {$linhas} registros importados.\n";
    } catch (Throwable $e) {
        $pdo->rollBack();
        fwrite(STDERR, "[ERRO] {$tabela}: " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo str_repeat('-', 60) . "\n";
echo "Importacao concluida. {$totalArquivos} arquivo(s), {$totalLinhas} registro(s).\n";
