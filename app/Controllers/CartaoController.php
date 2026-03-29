<?php
// app/Controllers/CartaoController.php

class CartaoController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        $cartoes = $this->pdo->query("SELECT * FROM cartoes ORDER BY nome ASC")->fetchAll();
        require_once '../app/Views/cartoes.php';
    }

    public function salvar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['nome'])) {
            $stmt = $this->pdo->prepare("INSERT INTO cartoes (nome) VALUES (?)");
            $stmt->execute([trim($_POST['nome'])]);
        }
        header('Location: /financeiro/public/index.php/cartoes?sucesso=1');
        exit;
    }

    // ESSA É A FUNÇÃO QUE ESTAVA FALTANDO:
    public function deletar() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            try {
                $stmt = $this->pdo->prepare("DELETE FROM cartoes WHERE id = ?");
                $stmt->execute([$id]);
                header('Location: /financeiro/public/index.php/cartoes?sucesso=1');
            } catch (Exception $e) {
                // Se der erro de vínculo (cartão sendo usado em alguma conta), avisa na URL
                header('Location: /financeiro/public/index.php/cartoes?erro=vinculo');
            }
            exit;
        }
    }
}