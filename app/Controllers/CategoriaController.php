<?php
// app/Controllers/CategoriaController.php

class CategoriaController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        $usuarioId = $_SESSION['usuario_id'] ?? 0;
        $stmt = $this->pdo->prepare("SELECT * FROM categorias WHERE usuario_id = ? ORDER BY nome ASC");
        $stmt->execute([$usuarioId]);
        $categorias = $stmt->fetchAll();
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

            $usuarioId = $_SESSION['usuario_id'] ?? 0;
            $stmt = $this->pdo->prepare("INSERT INTO categorias (nome, tipo, usuario_id) VALUES (?, ?, ?)");
            $stmt->execute([$nome, $tipo, $usuarioId]);
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

            $usuarioId = $_SESSION['usuario_id'] ?? 0;
            $stmt = $this->pdo->prepare("UPDATE categorias SET nome = ?, tipo = ? WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$nome, $tipo, $id, $usuarioId]);
        }

        header('Location: /financeiro/public/index.php/categorias?sucesso=atualizada');
        exit;
    }

    public function deletar() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            try {
                $usuarioId = $_SESSION['usuario_id'] ?? 0;
                $stmt = $this->pdo->prepare("DELETE FROM categorias WHERE id = ? AND usuario_id = ?");
                $stmt->execute([$id, $usuarioId]);
                header('Location: /financeiro/public/index.php/categorias?sucesso=deletada');
            } catch (Exception $e) {
                header('Location: /financeiro/public/index.php/categorias?erro=vinculo');
            }
            exit;
        }
    }
}
