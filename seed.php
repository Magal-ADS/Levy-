<?php
// ==========================================================
// SEEDER - Dados iniciais do Levy
// ==========================================================
// Uso: php seed.php
// ==========================================================

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}

$host = getenv('DB_HOST') ?: 'db';
$port = getenv('DB_PORT') ?: '5432';
$user = getenv('DB_USER') ?: 'postgres';
$pass = getenv('DB_PASS') ?: 'postgres';
$db = getenv('DB_NAME') ?: 'financeiro';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "Conectado ao PostgreSQL: $db\n";
    echo str_repeat('-', 50) . "\n\n";
} catch (PDOException $e) {
    die("ERRO ao conectar: " . $e->getMessage() . "\n");
}

echo "1. Usuarios...\n";

$usuarioNome = 'Levy';
$salarioBase = 2300.00;

$checkUser = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();

if ($checkUser == 0) {
    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, salario_base, saldo_inicial_mes, is_admin) VALUES (?, ?, 0.00, 1)");
    $stmt->execute([$usuarioNome, $salarioBase]);
    echo "   [OK] Usuario $usuarioNome criado com sucesso.\n";
} else {
    echo "   [--] Usuario ja existe no sistema.\n";
}

$usuarioId = (int) $pdo->query("SELECT id FROM usuarios ORDER BY id ASC LIMIT 1")->fetchColumn();
if ($usuarioId <= 0) {
    throw new RuntimeException('Nenhum usuário proprietário disponível. Execute migrate.php primeiro.');
}

echo "\n2. Categorias Base...\n";

$categorias = [
    ['nome' => 'Alimentacao', 'tipo' => 'despesa'],
    ['nome' => 'Moradia', 'tipo' => 'despesa'],
    ['nome' => 'Lazer', 'tipo' => 'despesa'],
    ['nome' => 'Transporte', 'tipo' => 'despesa'],
    ['nome' => 'Saude', 'tipo' => 'despesa'],
    ['nome' => 'Salario', 'tipo' => 'receita'],
    ['nome' => 'Pix Recebido', 'tipo' => 'receita'],
    ['nome' => 'Outros', 'tipo' => 'despesa'],
];

$stmtCat = $pdo->prepare("INSERT INTO categorias (usuario_id, nome, tipo) SELECT ?, ?, ? WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE usuario_id = ? AND nome = ?)");

foreach ($categorias as $categoria) {
    $stmtCat->execute([$usuarioId, $categoria['nome'], $categoria['tipo'], $usuarioId, $categoria['nome']]);
    if ($stmtCat->rowCount() > 0) {
        echo "   [OK] Categoria: {$categoria['nome']}\n";
    } else {
        echo "   [--] Categoria: {$categoria['nome']} ja existe\n";
    }
}

echo "\n3. Cartoes...\n";

$cartoes = [
    ['nome' => 'Nubank', 'nome_cartao' => 'Nubank Principal'],
    ['nome' => 'Inter', 'nome_cartao' => 'Inter Reserva'],
];

$stmtCar = $pdo->prepare("INSERT INTO cartoes (usuario_id, nome, nome_cartao) SELECT ?, ?, ? WHERE NOT EXISTS (SELECT 1 FROM cartoes WHERE usuario_id = ? AND nome = ?)");

foreach ($cartoes as $cartao) {
    $stmtCar->execute([$usuarioId, $cartao['nome'], $cartao['nome_cartao'], $usuarioId, $cartao['nome']]);
    if ($stmtCar->rowCount() > 0) {
        echo "   [OK] Cartao: {$cartao['nome']}\n";
    } else {
        echo "   [--] Cartao: {$cartao['nome']} ja existe\n";
    }
}

echo "\n4. Pessoas...\n";

$pessoas = ['Lucio', 'Gustavo', 'Daise'];

$stmtPessoa = $pdo->prepare("INSERT INTO pessoas (usuario_id, nome) SELECT ?, ? WHERE NOT EXISTS (SELECT 1 FROM pessoas WHERE usuario_id = ? AND nome = ?)");

foreach ($pessoas as $pessoa) {
    $stmtPessoa->execute([$usuarioId, $pessoa, $usuarioId, $pessoa]);
    if ($stmtPessoa->rowCount() > 0) {
        echo "   [OK] Pessoa: $pessoa\n";
    } else {
        echo "   [--] Pessoa: $pessoa ja existe\n";
    }
}

// Dados exclusivamente para desenvolvimento local. A senha curta é intencional
// para facilitar o acesso ao ambiente Docker e não deve ser usada em produção.
echo "\n5. Super admin local e massa de testes...\n";

$adminEmail = 'admin@magal.com';
$adminPassword = '123';
$adminHash = password_hash($adminPassword, PASSWORD_DEFAULT);

$stmtAdmin = $pdo->prepare(
    "INSERT INTO usuarios (nome, salario_base, saldo_inicial_mes, email, senha_hash, ativo, is_admin)
     SELECT 'Magal Admin', 5000.00, 1200.00, ?, ?, 1, 1
     WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE LOWER(email) = LOWER(?))"
);
$stmtAdmin->execute([$adminEmail, $adminHash, $adminEmail]);

// Mantém o acesso previsível em cada execução do seed local.
$stmtAdminUpdate = $pdo->prepare(
    "UPDATE usuarios
     SET nome = 'Magal Admin', senha_hash = ?, ativo = 1, is_admin = 1,
         salario_base = 5000.00, saldo_inicial_mes = 1200.00, atualizado_em = CURRENT_TIMESTAMP
     WHERE LOWER(email) = LOWER(?)"
);
$stmtAdminUpdate->execute([$adminHash, $adminEmail]);
$stmtAdminId = $pdo->prepare('SELECT id FROM usuarios WHERE LOWER(email) = LOWER(?)');
$stmtAdminId->execute([$adminEmail]);
$adminId = (int) $stmtAdminId->fetchColumn();
echo "   [OK] Super admin local: {$adminEmail}\n";

$categoriasTeste = [
    ['Alimentação', 'despesa'], ['Lazer', 'despesa'], ['Transporte', 'despesa'],
    ['Moradia', 'despesa'], ['Salário', 'receita'],
];
$stmtCategoriaTeste = $pdo->prepare(
    'INSERT INTO categorias (usuario_id, nome, tipo) SELECT ?, ?, ? WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE usuario_id = ? AND nome = ?)'
);
foreach ($categoriasTeste as [$nome, $tipo]) {
    $stmtCategoriaTeste->execute([$adminId, $nome, $tipo, $adminId, $nome]);
}

$cartoesTeste = ['Nubank', 'Inter'];
$stmtCartaoTeste = $pdo->prepare(
    'INSERT INTO cartoes (usuario_id, nome) SELECT ?, ? WHERE NOT EXISTS (SELECT 1 FROM cartoes WHERE usuario_id = ? AND nome = ?)'
);
foreach ($cartoesTeste as $nome) {
    $stmtCartaoTeste->execute([$adminId, $nome, $adminId, $nome]);
}

$pessoasTeste = ['Lucio', 'Gustavo', 'Daise'];
$stmtPessoaTeste = $pdo->prepare(
    'INSERT INTO pessoas (usuario_id, nome) SELECT ?, ? WHERE NOT EXISTS (SELECT 1 FROM pessoas WHERE usuario_id = ? AND nome = ?)'
);
foreach ($pessoasTeste as $nome) {
    $stmtPessoaTeste->execute([$adminId, $nome, $adminId, $nome]);
}

$buscarId = static function (string $tabela, string $nome) use ($pdo, $adminId): int {
    $stmt = $pdo->prepare("SELECT id FROM {$tabela} WHERE usuario_id = ? AND nome = ? LIMIT 1");
    $stmt->execute([$adminId, $nome]);
    return (int) $stmt->fetchColumn();
};

$idsCategorias = [];
foreach ($categoriasTeste as [$nome]) {
    $idsCategorias[$nome] = $buscarId('categorias', $nome);
}
$idsCartoes = [];
foreach ($cartoesTeste as $nome) {
    $idsCartoes[$nome] = $buscarId('cartoes', $nome);
}
$idsPessoas = [];
foreach ($pessoasTeste as $nome) {
    $idsPessoas[$nome] = $buscarId('pessoas', $nome);
}

$mesTeste = date('Y-m');
$inserirTransacaoTeste = static function (array $dados, array $divisoes) use ($pdo, $adminId, $mesTeste): void {
    $stmtExiste = $pdo->prepare('SELECT id FROM transacoes WHERE usuario_id = ? AND descricao = ? AND mes_referencia = ? LIMIT 1');
    $stmtExiste->execute([$adminId, $dados['descricao'], $mesTeste]);
    if ($stmtExiste->fetchColumn()) {
        return;
    }

    $pdo->beginTransaction();
    try {
        $stmtTransacao = $pdo->prepare(
            'INSERT INTO transacoes (usuario_id, descricao, valor_total, tipo, data_movimentacao, mes_referencia, categoria_id, cartao_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING id'
        );
        $stmtTransacao->execute([$adminId, $dados['descricao'], $dados['valor'], $dados['tipo'], $dados['data'], $mesTeste, $dados['categoria_id'], $dados['cartao_id']]);
        $transacaoId = (int) $stmtTransacao->fetchColumn();

        $stmtDivisao = $pdo->prepare('INSERT INTO divisoes_transacao (usuario_id, transacao_id, pessoa_id, valor_divisao, status_pago) VALUES (?, ?, ?, ?, ?)');
        foreach ($divisoes as [$pessoaId, $valor, $statusPago]) {
            $stmtDivisao->execute([$adminId, $transacaoId, $pessoaId, $valor, $statusPago]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
};

$inserirTransacaoTeste([
    'descricao' => 'Mercado compartilhado', 'valor' => 360.00, 'tipo' => 'despesa', 'data' => $mesTeste . '-05',
    'categoria_id' => $idsCategorias['Alimentação'], 'cartao_id' => $idsCartoes['Nubank'],
], [[null, 180.00, 1], [$idsPessoas['Lucio'], 90.00, 0], [$idsPessoas['Gustavo'], 90.00, 0]]);

$inserirTransacaoTeste([
    'descricao' => 'Jantar de sexta', 'valor' => 240.00, 'tipo' => 'despesa', 'data' => $mesTeste . '-12',
    'categoria_id' => $idsCategorias['Lazer'], 'cartao_id' => $idsCartoes['Inter'],
], [[null, 80.00, 1], [$idsPessoas['Gustavo'], 80.00, 0], [$idsPessoas['Daise'], 80.00, 0]]);

$inserirTransacaoTeste([
    'descricao' => 'Combustível', 'valor' => 150.00, 'tipo' => 'despesa', 'data' => $mesTeste . '-16',
    'categoria_id' => $idsCategorias['Transporte'], 'cartao_id' => null,
], [[null, 150.00, 1]]);

$inserirTransacaoTeste([
    'descricao' => 'Assinaturas digitais', 'valor' => 59.90, 'tipo' => 'despesa', 'data' => $mesTeste . '-20',
    'categoria_id' => $idsCategorias['Lazer'], 'cartao_id' => $idsCartoes['Nubank'],
], [[null, 59.90, 1]]);

$inserirTransacaoTeste([
    'descricao' => 'Salário mensal', 'valor' => 5000.00, 'tipo' => 'receita', 'data' => $mesTeste . '-01',
    'categoria_id' => $idsCategorias['Salário'], 'cartao_id' => null,
], [[null, 5000.00, 1]]);
echo "   [OK] Massa de testes criada para {$mesTeste}.\n";
echo "   [AVISO] Acesso local: {$adminEmail} / {$adminPassword}\n";

echo "\n" . str_repeat('-', 50) . "\n";
echo "Seed concluido com sucesso.\n";
echo str_repeat('-', 50) . "\n";
