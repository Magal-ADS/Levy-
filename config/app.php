<?php

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
