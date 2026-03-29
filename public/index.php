<?php
// public/index.php

// Puxa a conexão com o banco de dados
require_once '../config/database.php';

// Pega a URL que o usuário acessou
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// --- ROTAS DA APLICAÇÃO ---

// 1. --- TRANSAÇÕES (MOVIMENTAÇÕES) ---

// Nova Conta (Formulário)
if (strpos($uri, '/nova-conta') !== false) {
    require_once '../app/Controllers/TransacaoController.php';
    (new TransacaoController($pdo))->nova();
} 
// Salvar Nova (POST)
elseif (strpos($uri, '/salvar-transacao') !== false) {
    require_once '../app/Controllers/TransacaoController.php';
    (new TransacaoController($pdo))->salvar();
}
// Tela de Transações (A nova tela com Gráfico de Pizza)
elseif (strpos($uri, '/transacoes') !== false) {
    require_once '../app/Controllers/TransacaoController.php';
    (new TransacaoController($pdo))->index();
}
// Editar Transação
elseif (strpos($uri, '/editar-transacao') !== false) {
    require_once '../app/Controllers/TransacaoController.php';
    (new TransacaoController($pdo))->editar();
}
// Atualizar Transação (POST)
elseif (strpos($uri, '/atualizar-transacao') !== false) {
    require_once '../app/Controllers/TransacaoController.php';
    (new TransacaoController($pdo))->atualizar();
}
// Deletar Transação
elseif (strpos($uri, '/deletar-transacao') !== false) {
    require_once '../app/Controllers/TransacaoController.php';
    (new TransacaoController($pdo))->deletar();
}

// 2. --- AMIGOS (PESSOAS) ---

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

// 3. --- CATEGORIAS ---

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

// 4. --- CARTÕES ---

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

// 5. --- RECEBIMENTOS (DÍVIDAS) ---

elseif (strpos($uri, '/recebimentos') !== false) {
    require_once '../app/Controllers/RecebimentoController.php';
    (new RecebimentoController($pdo))->index();
}
elseif (strpos($uri, '/baixar-recebimento') !== false) {
    require_once '../app/Controllers/RecebimentoController.php';
    (new RecebimentoController($pdo))->baixar();
}

// 6. --- CONFIGURAÇÕES / PERFIL ---

elseif (strpos($uri, '/configuracoes') !== false) {
    require_once '../app/Controllers/ConfigController.php';
    (new ConfigController($pdo))->index();
}
elseif (strpos($uri, '/salvar-configuracoes') !== false) {
    require_once '../app/Controllers/ConfigController.php';
    (new ConfigController($pdo))->salvar();
}

// 13. --- DASHBOARD (PADRÃO) ---

else {
    require_once '../app/Controllers/DashboardController.php';
    (new DashboardController($pdo))->index();
}