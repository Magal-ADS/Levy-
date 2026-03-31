<?php
// ==========================================================
// PONTE DE TRANSFERÊNCIA: MySQL (Local) -> PostgreSQL (Supabase)
// ==========================================================
// Uso: php transferir_dados.php
// ==========================================================

echo "🔄 Iniciando transferência de dados...\n\n";

// 1. CONEXÃO LOCAL (MySQL / XAMPP)
$mysqlUser = 'root';
$mysqlPass = ''; // Senha do seu XAMPP (geralmente vazia)
$mysqlDb   = 'controle_financeiro';

try {
    $pdoMySql = new PDO("mysql:host=127.0.0.1;dbname=$mysqlDb;charset=utf8mb4", $mysqlUser, $mysqlPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "🐬 Conectado ao MySQL (Origem) com sucesso!\n";
} catch (PDOException $e) {
    die("❌ ERRO no MySQL: " . $e->getMessage() . "\n");
}

// 2. CONEXÃO NUVEM (PostgreSQL / Supabase)
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . "=" . trim($value));
    }
}

$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$db   = getenv('DB_NAME');

try {
    $pdoPgSql = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "🐘 Conectado ao Supabase (Destino) com sucesso!\n\n";
} catch (PDOException $e) {
    die("❌ ERRO no Supabase: " . $e->getMessage() . "\n");
}

// 3. LIMPANDO O SUPABASE
// Isso apaga os dados "Seed" que o migrate criou para não duplicar IDs
echo "🧹 Limpando dados iniciais do Supabase...\n";
$pdoPgSql->exec("TRUNCATE TABLE usuarios, pessoas, cartoes, categorias, transacoes, divisoes_transacao, contas_fixas RESTART IDENTITY CASCADE");

// 4. TRANSFERINDO TABELA POR TABELA (Na ordem correta)
$tabelas = ['usuarios', 'pessoas', 'cartoes', 'categorias', 'transacoes', 'divisoes_transacao', 'contas_fixas'];

foreach ($tabelas as $tabela) {
    // Busca tudo do MySQL
    $stmtMySql = $pdoMySql->query("SELECT * FROM $tabela");
    $registros = $stmtMySql->fetchAll();

    if (count($registros) > 0) {
        // Prepara o Insert pro Postgres
        $colunas = array_keys($registros[0]);
        $nomesColunas = implode(', ', $colunas);
        $placeholders = implode(', ', array_fill(0, count($colunas), '?'));
        
        $stmtPgSql = $pdoPgSql->prepare("INSERT INTO $tabela ($nomesColunas) VALUES ($placeholders)");

        $pdoPgSql->beginTransaction();
        try {
            foreach ($registros as $linha) {
                // Passa os valores exatos pro Postgres (mantendo os IDs originais)
                $stmtPgSql->execute(array_values($linha));
            }
            $pdoPgSql->commit();
            echo "✅ $tabela: " . count($registros) . " registros transferidos.\n";
            
            // Atualiza a contagem do ID (SERIAL) pro Postgres não se perder nas próximas inserções
            $pdoPgSql->exec("SELECT setval(pg_get_serial_sequence('$tabela', 'id'), coalesce(max(id), 1), max(id) IS NOT null) FROM $tabela");

        } catch (Exception $e) {
            $pdoPgSql->rollBack();
            echo "❌ ERRO ao transferir $tabela: " . $e->getMessage() . "\n";
        }
    } else {
        echo "⏭️  $tabela: Vazia (0 registros).\n";
    }
}

echo "\n🚀 TRANSFERÊNCIA CONCLUÍDA! O Supabase agora é um clone do seu XAMPP!\n";