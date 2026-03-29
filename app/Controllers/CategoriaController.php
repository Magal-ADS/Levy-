<?php
// app/Controllers/CategoriaController.php

class CategoriaController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        $categorias = $this->pdo->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll();
        require_once '../app/Views/categorias.php';
    }

    public function salvar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['nome'])) {
            $stmt = $this->pdo->prepare("INSERT INTO categorias (nome) VALUES (?)");
            $stmt->execute([trim($_POST['nome'])]);
        }
        header('Location: /financeiro/public/index.php/categorias?sucesso=1');
        exit;
    }

    // ESSA É A FUNÇÃO QUE ESTAVA FALTANDO:
    public function deletar() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            try {
                $stmt = $this->pdo->prepare("DELETE FROM categorias WHERE id = ?");
                $stmt->execute([$id]);
                header('Location: /financeiro/public/index.php/categorias?sucesso=1');
            } catch (Exception $e) {
                // Se der erro de vínculo (categoria sendo usada), avisa na URL
                header('Location: /financeiro/public/index.php/categorias?erro=vinculo');
            }
            exit;
        }
    }
}