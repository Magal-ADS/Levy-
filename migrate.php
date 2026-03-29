<?php
// ==========================================================
// MIGRATION - Estrutura Levy / MagalFin (MySQL)
// ==========================================================
// Uso: php migrate.php
// ==========================================================

// 1. Carregar variáveis de ambiente do .env
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

// 2. Configurações de conexão (tenta pegar do DATABASE_URL ou variáveis separadas)
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'controle_financeiro';

$dsn = "mysql:host=$host;port=$port;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Cria o banco de dados se não existir
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo->exec("USE `$db` text");
    
    echo "🐬 Conectado ao MySQL: $db @ $host\n\n";
} catch (PDOException $e) {
    die("ERRO ao conectar: " . $e->getMessage() . "\n");
}

// Desativa verificação de chaves estrangeiras para criar as tabelas sem erro de ordem
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

// ==========================================================
// TABELAS (MySQL Syntax)
// ==========================================================

$tabelas = [
    'usuarios' => "
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            salario_base DECIMAL(10, 2) DEFAULT 0.00,
            saldo_inicial_mes DECIMAL(10, 2) DEFAULT 0.00
        ) ENGINE=InnoDB
    ",

    'pessoas' => "
        CREATE TABLE IF NOT EXISTS pessoas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL
        ) ENGINE=InnoDB
    ",

    'cartoes' => "
        CREATE TABLE IF NOT EXISTS cartoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome_cartao VARCHAR(50),
            nome VARCHAR(100) NOT NULL
        ) ENGINE=InnoDB
    ",

    'categorias' => "
        CREATE TABLE IF NOT EXISTS categorias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(50) NOT NULL,
            tipo ENUM('receita', 'despesa') NOT NULL
        ) ENGINE=InnoDB
    ",

    'transacoes' => "
        CREATE TABLE IF NOT EXISTS transacoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            descricao VARCHAR(255) NOT NULL,
            valor_total DECIMAL(10, 2) NOT NULL,
            tipo ENUM('receita', 'despesa') NOT NULL,
            data_movimentacao DATE NOT NULL,
            mes_referencia VARCHAR(7) NOT NULL,
            categoria_id INT,
            cartao_id INT,
            FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
            FOREIGN KEY (cartao_id) REFERENCES cartoes(id) ON DELETE SET NULL
        ) ENGINE=InnoDB
    ",

    'divisoes_transacao' => "
        CREATE TABLE IF NOT EXISTS divisoes_transacao (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transacao_id INT NOT NULL,
            pessoa_id INT,
            valor_divisao DECIMAL(10, 2) NOT NULL,
            status_pago TINYINT(1) DEFAULT 0,
            FOREIGN KEY (transacao_id) REFERENCES transacoes(id) ON DELETE CASCADE,
            FOREIGN KEY (pessoa_id) REFERENCES pessoas(id) ON DELETE SET NULL
        ) ENGINE=InnoDB
    ",

    'contas_fixas' => "
        CREATE TABLE IF NOT EXISTS contas_fixas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            descricao VARCHAR(255) NOT NULL,
            valor_estimado DECIMAL(10, 2) NOT NULL,
            dia_vencimento INT NOT NULL,
            categoria_id INT,
            cartao_id INT,
            tipo_pagamento ENUM('automatico', 'manual') DEFAULT 'manual',
            ativo TINYINT(1) DEFAULT 1,
            FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
            FOREIGN KEY (cartao_id) REFERENCES cartoes(id) ON DELETE SET NULL
        ) ENGINE=InnoDB
    "
];

// 3. Execução da criação das tabelas
foreach ($tabelas as $nome => $sql) {
    try {
        $pdo->exec($sql);
        echo "[OK] Tabela: $nome\n";
    } catch (PDOException $e) {
        echo "[ERRO] $nome: " . $e->getMessage() . "\n";
    }
}

// Reativa verificação de chaves estrangeiras
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

// ==========================================================
// SEED - Dados Iniciais de Teste
// ==========================================================

echo "\n🌱 Populando dados iniciais...\n";

// Usuário Levy
$check = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE id = 1")->fetchColumn();
if ($check == 0) {
    $pdo->exec("INSERT INTO usuarios (id, nome, salario_base, saldo_inicial_mes) VALUES (1, 'Levy', 2300.00, 0.00)");
}

// Categorias Base
$check = $pdo->query("SELECT COUNT(*) FROM categorias")->fetchColumn();
if ($check == 0) {
    $pdo->exec("INSERT INTO categorias (nome, tipo) VALUES 
        ('Alimentação', 'despesa'), 
        ('Moradia', 'despesa'), 
        ('Salário', 'receita'),
        ('Lazer', 'despesa')");
}

echo "\n✅ Migration concluída com sucesso!\n";