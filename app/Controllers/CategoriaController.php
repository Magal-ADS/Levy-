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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim($_POST['nome'] ?? '');
            $tipo = trim($_POST['tipo'] ?? '');

            if ($nome === '') {
                header('Location: /financeiro/public/index.php/categorias?erro=nome');
                exit;
            }

            if (!in_array($tipo, ['receita', 'despesa'], true)) {
                header('Location: /financeiro/public/index.php/categorias?erro=tipo');
                exit;
            }

            $stmt = $this->pdo->prepare("INSERT INTO categorias (nome, tipo) VALUES (?, ?)");
            $stmt->execute([$nome, $tipo]);
        }

        header('Location: /financeiro/public/index.php/categorias?sucesso=criada');
        exit;
    }

    public function atualizar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $nome = trim($_POST['nome'] ?? '');
            $tipo = trim($_POST['tipo'] ?? '');

            if (!$id || !preg_match('/^\d+$/', (string) $id)) {
                header('Location: /financeiro/public/index.php/categorias?erro=id');
                exit;
            }

            if ($nome === '') {
                header('Location: /financeiro/public/index.php/categorias?erro=nome');
                exit;
            }

            if (!in_array($tipo, ['receita', 'despesa'], true)) {
                header('Location: /financeiro/public/index.php/categorias?erro=tipo');
                exit;
            }

            $stmt = $this->pdo->prepare("UPDATE categorias SET nome = ?, tipo = ? WHERE id = ?");
            $stmt->execute([$nome, $tipo, $id]);
        }

        header('Location: /financeiro/public/index.php/categorias?sucesso=atualizada');
        exit;
    }

    public function deletar() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            try {
                $stmt = $this->pdo->prepare("DELETE FROM categorias WHERE id = ?");
                $stmt->execute([$id]);
                header('Location: /financeiro/public/index.php/categorias?sucesso=deletada');
            } catch (Exception $e) {
                header('Location: /financeiro/public/index.php/categorias?erro=vinculo');
            }
            exit;
        }
    }
}
