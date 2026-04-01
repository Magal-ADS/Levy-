<?php
// public/index.php

require_once '../config/database.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (strpos($uri, '/nova-conta') !== false) {
    require_once '../app/Controllers/TransacaoController.php';
    (new TransacaoController($pdo))->nova();
}
elseif (strpos($uri, '/salvar-transacao') !== false) {
    require_once '../app/Controllers/TransacaoController.php';
    (new TransacaoController($pdo))->salvar();
}
elseif (strpos($uri, '/transacoes') !== false) {
    require_once '../app/Controllers/TransacaoController.php';
    (new TransacaoController($pdo))->index();
}
elseif (strpos($uri, '/editar-transacao') !== false) {
    require_once '../app/Controllers/TransacaoController.php';
    (new TransacaoController($pdo))->editar();
}
elseif (strpos($uri, '/atualizar-transacao') !== false) {
    require_once '../app/Controllers/TransacaoController.php';
    (new TransacaoController($pdo))->atualizar();
}
elseif (strpos($uri, '/deletar-transacao') !== false) {
    require_once '../app/Controllers/TransacaoController.php';
    (new TransacaoController($pdo))->deletar();
}
elseif (strpos($uri, '/contas-fixas') !== false) {
    require_once '../app/Controllers/ContaFixaController.php';
    $controller = new ContaFixaController($pdo);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->salvar();
    } else {
        $controller->index();
    }
}
elseif (strpos($uri, '/pagar-conta-fixa') !== false) {
    require_once '../app/Controllers/ContaFixaController.php';
    (new ContaFixaController($pdo))->pagar();
}
elseif (strpos($uri, '/deletar-conta-fixa') !== false) {
    require_once '../app/Controllers/ContaFixaController.php';
    (new ContaFixaController($pdo))->deletar();
}
elseif (strpos($uri, '/pessoas') !== false) {
    require_once '../app/Controllers/PessoaController.php';
    $controller = new PessoaController($pdo);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->salvar();
    } else {
        $controller->index();
    }
}
elseif (strpos($uri, '/deletar-pessoa') !== false) {
    require_once '../app/Controllers/PessoaController.php';
    (new PessoaController($pdo))->deletar();
}
elseif (strpos($uri, '/categorias') !== false) {
    require_once '../app/Controllers/CategoriaController.php';
    $controller = new CategoriaController($pdo);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->salvar();
    } else {
        $controller->index();
    }
}
elseif (strpos($uri, '/deletar-categoria') !== false) {
    require_once '../app/Controllers/CategoriaController.php';
    (new CategoriaController($pdo))->deletar();
}
elseif (strpos($uri, '/cartoes') !== false) {
    require_once '../app/Controllers/CartaoController.php';
    $controller = new CartaoController($pdo);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->salvar();
    } else {
        $controller->index();
    }
}
elseif (strpos($uri, '/deletar-cartao') !== false) {
    require_once '../app/Controllers/CartaoController.php';
    (new CartaoController($pdo))->deletar();
}
elseif (strpos($uri, '/relatorio-pessoa') !== false) {
    require_once '../app/Controllers/RecebimentoController.php';
    (new RecebimentoController($pdo))->gerarPdfPessoa();
}
elseif (strpos($uri, '/recebimentos') !== false) {
    require_once '../app/Controllers/RecebimentoController.php';
    (new RecebimentoController($pdo))->index();
}
elseif (strpos($uri, '/baixar-recebimento') !== false) {
    require_once '../app/Controllers/RecebimentoController.php';
    (new RecebimentoController($pdo))->baixar();
}
elseif (strpos($uri, '/configuracoes') !== false) {
    require_once '../app/Controllers/ConfigController.php';
    (new ConfigController($pdo))->index();
}
elseif (strpos($uri, '/salvar-configuracoes') !== false) {
    require_once '../app/Controllers/ConfigController.php';
    (new ConfigController($pdo))->salvar();
}
else {
    require_once '../app/Controllers/DashboardController.php';
    (new DashboardController($pdo))->index();
}
