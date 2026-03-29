<?php
// ==========================================================
// SEEDER - Dados iniciais do MagalFin / Levy
// ==========================================================
// Uso: php seed.php
// ==========================================================

// 1. Carrega .env
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

// 2. Conectar ao banco MySQL
$host = getenv('DB_HOST') ?: '127.0.0.1';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'controle_financeiro';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "🐬 Conectado ao banco MySQL: $db\n";
    echo str_repeat('-', 50) . "\n\n";
} catch (PDOException $e) {
    die("ERRO ao conectar: " . $e->getMessage() . "\n");
}

// ==========================================================
// 1. Usuário Principal (Magal)
// ==========================================================
echo "1. Usuários...\n";

$usuarioNome = 'Magal';
$salarioBase = 2300.00;

// Verifica se já existe um usuário
$checkUser = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();

if ($checkUser == 0) {
    $stmt = $pdo->prepare("INSERT INTO usuarios (id, nome, salario_base, saldo_inicial_mes) VALUES (1, ?, ?, 0.00)");
    $stmt->execute([$usuarioNome, $salarioBase]);
    echo "   [OK] Usuário $usuarioNome criado com sucesso.\n";
} else {
    echo "   [--] Usuário já existe no sistema.\n";
}

// ==========================================================
// 2. Categorias Iniciais
// ==========================================================
echo "\n2. Categorias Base...\n";

$categorias = [
    ['nome' => 'Alimentação', 'tipo' => 'despesa'],
    ['nome' => 'Moradia',      'tipo' => 'despesa'],
    ['nome' => 'Lazer',        'tipo' => 'despesa'],
    ['nome' => 'Transporte',   'tipo' => 'despesa'],
    ['nome' => 'Saúde',        'tipo' => 'despesa'],
    ['nome' => 'Salário',      'tipo' => 'receita'],
    ['nome' => 'Pix Recebido', 'tipo' => 'receita'],
    ['nome' => 'Outros',       'tipo' => 'despesa'],
];

$stmtCat = $pdo->prepare("INSERT INTO categorias (nome, tipo) SELECT ?, ? WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE nome = ?)");

foreach ($categorias as $c) {
    $stmtCat->execute([$c['nome'], $c['tipo'], $c['nome']]);
    if ($stmtCat->rowCount() > 0) {
        echo "   [OK] Categoria: {$c['nome']}\n";
    } else {
        echo "   [--] Categoria: {$c['nome']} já existe\n";
    }
}

// ==========================================================
// 3. Cartões Exemplo
// ==========================================================
echo "\n3. Cartões...\n";

$cartoes = [
    ['nome' => 'Nubank', 'nome_cartao' => 'Nubank Principal'],
    ['nome' => 'Inter',  'nome_cartao' => 'Inter Reserva'],
];

$stmtCar = $pdo->prepare("INSERT INTO cartoes (nome, nome_cartao) SELECT ?, ? WHERE NOT EXISTS (SELECT 1 FROM cartoes WHERE nome = ?)");

foreach ($cartoes as $car) {
    $stmtCar->execute([$car['nome'], $car['nome_cartao'], $car['nome']]);
    if ($stmtCar->rowCount() > 0) {
        echo "   [OK] Cartão: {$car['nome']}\n";
    } else {
        echo "   [--] Cartão: {$car['nome']} já existe\n";
    }
}

// ==========================================================
// 4. Amigos (Pessoas para Divisão)
// ==========================================================
echo "\n4. Amigos (Pessoas)...\n";

$pessoas = ['Lucio', 'Gustavo', 'Daise'];

$stmtPess = $pdo->prepare("INSERT INTO pessoas (nome) SELECT ? WHERE NOT EXISTS (SELECT 1 FROM pessoas WHERE nome = ?)");

foreach ($pessoas as $p) {
    $stmtPess->execute([$p, $p]);
    if ($stmtPess->rowCount() > 0) {
        echo "   [OK] Pessoa: $p\n";
    } else {
        echo "   [--] Pessoa: $p já existe\n";
    }
}

// ==========================================================
// FIM DO SCRIPT
// ==========================================================
echo "\n" . str_repeat('-', 50) . "\n";
echo "Seed concluído com sucesso no MySQL!\n";
echo str_repeat('-', 50) . "\n";