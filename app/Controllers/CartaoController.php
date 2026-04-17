<?php
// app/Controllers/CartaoController.php

class CartaoController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        $usuarioId = $_SESSION['usuario_id'] ?? 0;
        $stmt = $this->pdo->prepare("SELECT * FROM cartoes WHERE usuario_id = ? ORDER BY nome ASC");
        $stmt->execute([$usuarioId]);
        $cartoes = $stmt->fetchAll();
        require_once '../app/Views/cartoes.php';
    }

    public function salvar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['nome'])) {
            $usuarioId = $_SESSION['usuario_id'] ?? 0;
            $stmt = $this->pdo->prepare("INSERT INTO cartoes (nome, usuario_id) VALUES (?, ?)");
            $stmt->execute([trim($_POST['nome']), $usuarioId]);
        }
        header('Location: /financeiro/public/index.php/cartoes?sucesso=1');
        exit;
    }

    // ESSA É A FUNÇÃO QUE ESTAVA FALTANDO:
    public function deletar() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            try {
                $usuarioId = $_SESSION['usuario_id'] ?? 0;
                $stmt = $this->pdo->prepare("DELETE FROM cartoes WHERE id = ? AND usuario_id = ?");
                $stmt->execute([$id, $usuarioId]);
                header('Location: /financeiro/public/index.php/cartoes?sucesso=1');
            } catch (Exception $e) {
                // Se der erro de vínculo (cartão sendo usado em alguma conta), avisa na URL
                header('Location: /financeiro/public/index.php/cartoes?erro=vinculo');
            }
            exit;
        }
    }
}