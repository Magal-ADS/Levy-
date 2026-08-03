<?php

declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8')
        . '">';
}

function csrf_validate_request(): void
{
    $received = $_POST['_csrf'] ?? '';
    if (!is_string($received) || !hash_equals(csrf_token(), $received)) {
        http_response_code(403);
        echo 'Sua sessão expirou ou a requisição é inválida. Atualize a página e tente novamente.';
        exit;
    }
}

function require_post_request(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        echo 'Método não permitido.';
        exit;
    }
}
