<?php
// ==========================================================
// CONEXÃO COM O BANCO DE DADOS (PostgreSQL / Supabase)
// ==========================================================

// 1. Procura e carrega o arquivo .env automaticamente
$envPaths = [
    __DIR__ . '/.env',
    __DIR__ . '/../.env',
    __DIR__ . '/../../.env'
];

foreach ($envPaths as $envFile) {
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (strpos($line, '=') === false) continue;
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . "=" . trim($value));
        }
        break; // Achou o .env, para de procurar
    }
}

// 2. Resgata as variáveis de ambiente
$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: '5432';
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

// 3. Monta a string de conexão (DSN) atualizada para pgsql
$dsn = "pgsql:host=$host;port=$port;dbname=$db";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// 4. Inicia a conexão
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Se der erro, para a aplicação e mostra o motivo
    throw new \PDOException("ERRO DE CONEXÃO: " . $e->getMessage(), (int)$e->getCode());
}