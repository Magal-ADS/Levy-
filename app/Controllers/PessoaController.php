<?php
// app/Controllers/PessoaController.php

class PessoaController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // 1. Lista todos os amigos
    public function index() {
        $stmt = $this->pdo->query("SELECT * FROM pessoas ORDER BY nome ASC");
        $pessoas = $stmt->fetchAll();
        
        require_once '../app/Views/pessoas.php';
    }

    // 2. Salva um novo amigo (POST)
    public function salvar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim($_POST['nome']);
            
            if (!empty($nome)) {
                $stmt = $this->pdo->prepare("INSERT INTO pessoas (nome) VALUES (?)");
                $stmt->execute([$nome]);
            }
            
            header('Location: /financeiro/public/index.php/pessoas?sucesso=1');
            exit;
        }
    }

    // 3. Abre a tela de edição (GET)
    public function editar() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /financeiro/public/index.php/pessoas');
            exit;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM pessoas WHERE id = ?");
        $stmt->execute([$id]);
        $pessoa = $stmt->fetch();

        if (!$pessoa) {
            header('Location: /financeiro/public/index.php/pessoas');
            exit;
        }

        require_once '../app/Views/editar-pessoa.php';
    }

    // 4. Salva a alteração do nome (POST)
    public function atualizar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $nome = trim($_POST['nome']);

            if (!empty($id) && !empty($nome)) {
                $stmt = $this->pdo->prepare("UPDATE pessoas SET nome = ? WHERE id = ?");
                $stmt->execute([$nome, $id]);
            }

            header('Location: /financeiro/public/index.php/pessoas?sucesso=1');
            exit;
        }
    }

    // 5. Remove um amigo do sistema
    public function deletar() {
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            try {
                // Tenta deletar a pessoa
                $stmt = $this->pdo->prepare("DELETE FROM pessoas WHERE id = ?");
                $stmt->execute([$id]);
                header('Location: /financeiro/public/index.php/pessoas?sucesso=1');
            } catch (PDOException $e) {
                // Se cair aqui, é porque ela tem transações vinculadas no banco
                // Enviamos um erro via URL para você tratar na View
                header('Location: /financeiro/public/index.php/pessoas?erro=vinculo');
            }
            exit;
        }
    }
}