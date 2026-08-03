<?php

$appDebug = getenv('APP_DEBUG') === '1';
ini_set('display_errors', $appDebug ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

set_exception_handler(static function (Throwable $exception) use ($appDebug): void {
    error_log((string) $exception);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }
    echo $appDebug
        ? 'Erro interno: ' . $exception->getMessage()
        : 'Não foi possível concluir a operação. Tente novamente.';
});

function app_normalize_web_path(string $path): string
{
    $path = trim(str_replace('\\', '/', $path));

    if ($path === '' || $path === '/') {
        return '';
    }

    $path = '/' . trim($path, '/');
    return rtrim($path, '/');
}

function app_base_path(): string
{
    static $basePath = null;

    if ($basePath !== null) {
        return $basePath;
    }

    $configured = getenv('APP_BASE_PATH');
    if ($configured !== false && $configured !== '') {
        $basePath = app_normalize_web_path($configured);
        return $basePath;
    }

    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $baseDir = str_replace('\\', '/', dirname($scriptName));
    $basePath = app_normalize_web_path($baseDir);

    return $basePath;
}

function app_index_path(): string
{
    static $indexPath = null;

    if ($indexPath !== null) {
        return $indexPath;
    }

    $configured = getenv('APP_INDEX_PATH');
    if ($configured !== false && $configured !== '') {
        $indexPath = app_normalize_web_path($configured);
        return $indexPath === '' ? '/index.php' : $indexPath;
    }

    $basePath = app_base_path();
    $indexPath = $basePath === '' ? '/index.php' : $basePath . '/index.php';

    return $indexPath;
}

function app_url(string $path = ''): string
{
    $path = trim($path);
    if ($path === '' || $path === '/') {
        return app_index_path();
    }

    return app_index_path() . '/' . ltrim($path, '/');
}

function asset_url(string $path = ''): string
{
    $basePath = app_base_path();
    $path = ltrim(trim($path), '/');

    if ($path === '') {
        return $basePath === '' ? '/' : $basePath . '/';
    }

    return ($basePath === '' ? '' : $basePath) . '/' . $path;
}

require_once __DIR__ . '/../app/Support/Auth.php';
require_once __DIR__ . '/../app/Support/Csrf.php';

Auth::boot();
