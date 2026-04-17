<?php
// ==========================================================
// MIGRATION - Estrutura Levy / MagalFin (PostgreSQL / Supabase)
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

// 2. Configurações de conexão (Supabase)
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '5432';
$user = getenv('DB_USER') ?: 'postgres';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'postgres';

// ATENÇÃO AQUI: Mudou de "mysql:" para "pgsql:"
$dsn = "pgsql:host=$host;port=$port;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "🐘 Conectado ao PostgreSQL (Supabase) @ $host\n\n";
} catch (PDOException $e) {
    die("ERRO ao conectar: " . $e->getMessage() . "\n");
}

// ==========================================================
// TABELAS (PostgreSQL Syntax)
// Importante: Ordenado pelas dependências das Chaves Estrangeiras
// ==========================================================

$tabelas = [
    'usuarios' => "
        CREATE TABLE IF NOT EXISTS usuarios (
            id SERIAL PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE,
            salario_base DECIMAL(10, 2) DEFAULT 0.00,
            saldo_inicial_mes DECIMAL(10, 2) DEFAULT 0.00
        )
    ",

    'pessoas' => "
        CREATE TABLE IF NOT EXISTS pessoas (
            id SERIAL PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            vinculo_usuario_id INT REFERENCES usuarios(id) ON DELETE SET NULL
        )
    ",

    'cartoes' => "
        CREATE TABLE IF NOT EXISTS cartoes (
            id SERIAL PRIMARY KEY,
            nome_cartao VARCHAR(50),
            nome VARCHAR(100) NOT NULL
        )
    ",

    'categorias' => "
        CREATE TABLE IF NOT EXISTS categorias (
            id SERIAL PRIMARY KEY,
            nome VARCHAR(50) NOT NULL,
            tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('receita', 'despesa'))
        )
    ",

    'transacoes' => "
        CREATE TABLE IF NOT EXISTS transacoes (
            id SERIAL PRIMARY KEY,
            usuario_id INT REFERENCES usuarios(id) ON DELETE CASCADE,
            descricao VARCHAR(255) NOT NULL,
            valor_total DECIMAL(10, 2) NOT NULL,
            tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('receita', 'despesa')),
            data_movimentacao DATE NOT NULL,
            mes_referencia VARCHAR(7) NOT NULL,
            categoria_id INT REFERENCES categorias(id) ON DELETE SET NULL,
            cartao_id INT REFERENCES cartoes(id) ON DELETE SET NULL,
            hash_parcelamento VARCHAR(50) NULL
        )
    ",

    'divisoes_transacao' => "
        CREATE TABLE IF NOT EXISTS divisoes_transacao (
            id SERIAL PRIMARY KEY,
            transacao_id INT NOT NULL REFERENCES transacoes(id) ON DELETE CASCADE,
            pessoa_id INT REFERENCES pessoas(id) ON DELETE SET NULL,
            valor_divisao DECIMAL(10, 2) NOT NULL,
            status_pago SMALLINT DEFAULT 0
        )
    ",

    'contas_fixas' => "
        CREATE TABLE IF NOT EXISTS contas_fixas (
            id SERIAL PRIMARY KEY,
            descricao VARCHAR(255) NOT NULL,
            valor_estimado DECIMAL(10, 2) NOT NULL,
            dia_vencimento INT NOT NULL,
            categoria_id INT REFERENCES categorias(id) ON DELETE SET NULL,
            cartao_id INT REFERENCES cartoes(id) ON DELETE SET NULL,
            tipo_pagamento VARCHAR(20) DEFAULT 'manual' CHECK (tipo_pagamento IN ('automatico', 'manual')),
            ativo SMALLINT DEFAULT 1
        )
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

// ==========================================================
// SEED - Dados Iniciais de Teste
// ==========================================================

echo "\n🌱 Populando dados iniciais...\n";

// Usuário Levy
$check = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
if ($check == 0) {
    $pdo->exec("INSERT INTO usuarios (nome, email, salario_base, saldo_inicial_mes) VALUES ('Levy', 'levy@example.com', 2300.00, 0.00)");
    echo "[SEED] Usuário Levy criado.\n";
}

// Categorias Base
$check = $pdo->query("SELECT COUNT(*) FROM categorias")->fetchColumn();
if ($check == 0) {
    $pdo->exec("INSERT INTO categorias (nome, tipo) VALUES 
        ('Alimentação', 'despesa'), 
        ('Moradia', 'despesa'), 
        ('Salário', 'receita'),
        ('Lazer', 'despesa')");
    echo "[SEED] Categorias básicas criadas.\n";
}

echo "\n✅ Migration concluída com sucesso no Supabase!\n";
