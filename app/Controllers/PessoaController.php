<?php
// app/Controllers/PessoaController.php

class PessoaController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // 1. Lista todos os amigos
    public function index() {
        $usuarioId = $_SESSION['usuario_id'] ?? 0;
        $stmt = $this->pdo->prepare("SELECT p.*, u.email as usuario_email, u.nome as usuario_nome FROM pessoas p LEFT JOIN usuarios u ON p.vinculo_usuario_id = u.id WHERE p.usuario_id = ? ORDER BY p.nome ASC");
        $stmt->execute([$usuarioId]);
        $pessoas = $stmt->fetchAll();
        
        require_once '../app/Views/pessoas.php';
    }

    // 2. Salva um novo amigo (POST)
    public function salvar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim($_POST['nome']);
            $usuarioEmail = isset($_POST['usuario_email']) ? trim($_POST['usuario_email']) : '';
            $vinculoId = null;

            if ($usuarioEmail !== '') {
                $stmtU = $this->pdo->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
                $stmtU->execute([$usuarioEmail]);
                $u = $stmtU->fetch();
                if ($u) $vinculoId = $u['id'];
            }
            
            if (!empty($nome)) {
                $usuarioId = $_SESSION['usuario_id'] ?? 0;
                $stmt = $this->pdo->prepare("INSERT INTO pessoas (nome, vinculo_usuario_id, usuario_id) VALUES (?, ?, ?)");
                $stmt->execute([$nome, $vinculoId, $usuarioId]);
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

        $usuarioId = $_SESSION['usuario_id'] ?? 0;
        $stmt = $this->pdo->prepare("SELECT * FROM pessoas WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$id, $usuarioId]);
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
            $usuarioEmail = isset($_POST['usuario_email']) ? trim($_POST['usuario_email']) : '';
            $vinculoId = null;

            if ($usuarioEmail !== '') {
                $stmtU = $this->pdo->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
                $stmtU->execute([$usuarioEmail]);
                $u = $stmtU->fetch();
                if ($u) $vinculoId = $u['id'];
            }

            if (!empty($id) && !empty($nome)) {
                $usuarioId = $_SESSION['usuario_id'] ?? 0;
                $stmt = $this->pdo->prepare("UPDATE pessoas SET nome = ?, vinculo_usuario_id = ? WHERE id = ? AND usuario_id = ?");
                $stmt->execute([$nome, $vinculoId, $id, $usuarioId]);
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
                // Tenta deletar a pessoa para o usuário atual
                $usuarioId = $_SESSION['usuario_id'] ?? 0;
                $stmt = $this->pdo->prepare("DELETE FROM pessoas WHERE id = ? AND usuario_id = ?");
                $stmt->execute([$id, $usuarioId]);
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