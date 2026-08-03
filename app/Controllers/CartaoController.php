<?php
// app/Controllers/CartaoController.php

class CartaoController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        $stmt = $this->pdo->prepare("SELECT * FROM cartoes WHERE usuario_id = ? ORDER BY nome ASC");
        $stmt->execute([current_user_id()]);
        $cartoes = $stmt->fetchAll();
        require_once '../app/Views/cartoes.php';
    }

    public function salvar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['nome'])) {
            $stmt = $this->pdo->prepare("INSERT INTO cartoes (usuario_id, nome) VALUES (?, ?)");
            $stmt->execute([current_user_id(), trim($_POST['nome'])]);
        }
        header('Location: ' . app_url('cartoes') . '?sucesso=1');
        exit;
    }

    // ESSA É A FUNÇÃO QUE ESTAVA FALTANDO:
    public function deletar() {
        require_post_request();
        $id = $_POST['id'] ?? null;
        if ($id) {
            try {
                $stmt = $this->pdo->prepare("DELETE FROM cartoes WHERE id = ? AND usuario_id = ?");
                $stmt->execute([$id, current_user_id()]);
                header('Location: ' . app_url('cartoes') . '?sucesso=1');
            } catch (Exception $e) {
                // Se der erro de vínculo (cartão sendo usado em alguma conta), avisa na URL
                header('Location: ' . app_url('cartoes') . '?erro=vinculo');
            }
            exit;
        }
    }
}
