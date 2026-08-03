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

echo "\n" . str_repeat('-', 50) . "\n";
echo "Seed concluido com sucesso.\n";
echo str_repeat('-', 50) . "\n";
