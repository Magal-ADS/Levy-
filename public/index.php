<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$indexPath = app_index_path();
if (str_starts_with($requestPath, $indexPath)) {
    $requestPath = substr($requestPath, strlen($indexPath));
}
$route = '/' . trim($requestPath, '/');
if ($route === '//') {
    $route = '/';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_validate_request();
}

require_once __DIR__ . '/../app/Controllers/AuthController.php';
$authController = new AuthController($pdo);

if (!Auth::hasConfiguredUser($pdo)) {
    if ($route === '/setup' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $authController->setup();
    }
    if ($route === '/setup') {
        $authController->setupForm();
        exit;
    }
    header('Location: ' . app_url('setup'));
    exit;
}

if ($route === '/login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController->login();
}
if ($route === '/login') {
    $authController->loginForm();
    exit;
}

Auth::requireUser($pdo);

if ($route === '/logout') {
    $authController->logout();
}

switch ($route) {
    case '/nova-conta':
        require_once __DIR__ . '/../app/Controllers/TransacaoController.php';
        (new TransacaoController($pdo))->nova();
        break;
    case '/salvar-transacao':
        require_once __DIR__ . '/../app/Controllers/TransacaoController.php';
        (new TransacaoController($pdo))->salvar();
        break;
    case '/transacoes':
        require_once __DIR__ . '/../app/Controllers/TransacaoController.php';
        (new TransacaoController($pdo))->index();
        break;
    case '/editar-transacao':
        require_once __DIR__ . '/../app/Controllers/TransacaoController.php';
        (new TransacaoController($pdo))->editar();
        break;
    case '/atualizar-transacao':
        require_once __DIR__ . '/../app/Controllers/TransacaoController.php';
        (new TransacaoController($pdo))->atualizar();
        break;
    case '/deletar-transacao':
        require_once __DIR__ . '/../app/Controllers/TransacaoController.php';
        (new TransacaoController($pdo))->deletar();
        break;
    case '/contas-fixas':
        require_once __DIR__ . '/../app/Controllers/ContaFixaController.php';
        $controller = new ContaFixaController($pdo);
        $_SERVER['REQUEST_METHOD'] === 'POST' ? $controller->salvar() : $controller->index();
        break;
    case '/pagar-conta-fixa':
        require_once __DIR__ . '/../app/Controllers/ContaFixaController.php';
        (new ContaFixaController($pdo))->pagar();
        break;
    case '/deletar-conta-fixa':
        require_once __DIR__ . '/../app/Controllers/ContaFixaController.php';
        (new ContaFixaController($pdo))->deletar();
        break;
    case '/pessoas':
        require_once __DIR__ . '/../app/Controllers/PessoaController.php';
        $controller = new PessoaController($pdo);
        $_SERVER['REQUEST_METHOD'] === 'POST' ? $controller->salvar() : $controller->index();
        break;
    case '/deletar-pessoa':
        require_once __DIR__ . '/../app/Controllers/PessoaController.php';
        (new PessoaController($pdo))->deletar();
        break;
    case '/categorias':
        require_once __DIR__ . '/../app/Controllers/CategoriaController.php';
        $controller = new CategoriaController($pdo);
        $_SERVER['REQUEST_METHOD'] === 'POST' ? $controller->salvar() : $controller->index();
        break;
    case '/atualizar-categoria':
        require_once __DIR__ . '/../app/Controllers/CategoriaController.php';
        (new CategoriaController($pdo))->atualizar();
        break;
    case '/deletar-categoria':
        require_once __DIR__ . '/../app/Controllers/CategoriaController.php';
        (new CategoriaController($pdo))->deletar();
        break;
    case '/cartoes':
        require_once __DIR__ . '/../app/Controllers/CartaoController.php';
        $controller = new CartaoController($pdo);
        $_SERVER['REQUEST_METHOD'] === 'POST' ? $controller->salvar() : $controller->index();
        break;
    case '/deletar-cartao':
        require_once __DIR__ . '/../app/Controllers/CartaoController.php';
        (new CartaoController($pdo))->deletar();
        break;
    case '/relatorio-pessoa':
        require_once __DIR__ . '/../app/Controllers/RecebimentoController.php';
        (new RecebimentoController($pdo))->gerarPdfPessoa();
        break;
    case '/recebimentos':
        require_once __DIR__ . '/../app/Controllers/RecebimentoController.php';
        (new RecebimentoController($pdo))->index();
        break;
    case '/baixar-recebimento':
        require_once __DIR__ . '/../app/Controllers/RecebimentoController.php';
        (new RecebimentoController($pdo))->baixar();
        break;
    case '/configuracoes':
        require_once __DIR__ . '/../app/Controllers/ConfigController.php';
        (new ConfigController($pdo))->index();
        break;
    case '/salvar-configuracoes':
        require_once __DIR__ . '/../app/Controllers/ConfigController.php';
        (new ConfigController($pdo))->salvar();
        break;
    case '/usuarios':
        require_once __DIR__ . '/../app/Controllers/UsuarioController.php';
        $controller = new UsuarioController($pdo);
        $_SERVER['REQUEST_METHOD'] === 'POST' ? $controller->salvar() : $controller->index();
        break;
    case '/alternar-status-usuario':
        require_once __DIR__ . '/../app/Controllers/UsuarioController.php';
        (new UsuarioController($pdo))->alternarStatus();
        break;
    case '/':
        require_once __DIR__ . '/../app/Controllers/DashboardController.php';
        (new DashboardController($pdo))->index();
        break;
    default:
        http_response_code(404);
        echo 'Página não encontrada.';
}
