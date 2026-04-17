<?php
// app/Controllers/PerfilController.php

class PerfilController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        $usuarioId = $_SESSION['usuario_id'] ?? 0;
        if (!$usuarioId) {
            header('Location: /login');
            exit;
        }

        $stmt = $this->pdo->prepare("SELECT id, nome, email, saldo_inicial_mes, salario_base FROM usuarios WHERE id = ?");
        $stmt->execute([$usuarioId]);
        $usuario = $stmt->fetch();

        require_once __DIR__ . '/../Views/perfil.php';
    }

    public function atualizar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /financeiro/public/index.php/perfil');
            exit;
        }

        $usuarioId = $_SESSION['usuario_id'] ?? 0;
        if (!$usuarioId) {
            header('Location: /login');
            exit;
        }

        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $saldo = isset($_POST['saldo_inicial_mes']) ? floatval(str_replace(',', '.', $_POST['saldo_inicial_mes'])) : null;
        $salario = isset($_POST['salario_base']) ? floatval(str_replace(',', '.', $_POST['salario_base'])) : null;
        $novaSenha = $_POST['nova_senha'] ?? '';

        if ($nome === '' || $email === '') {
            header('Location: /financeiro/public/index.php/perfil?erro=campos');
            exit;
        }

        if ($novaSenha !== '') {
            $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET nome = ?, email = ?, saldo_inicial_mes = ?, salario_base = ?, senha = ? WHERE id = ?";
            $params = [$nome, $email, $saldo, $salario, $hash, $usuarioId];
        } else {
            $sql = "UPDATE usuarios SET nome = ?, email = ?, saldo_inicial_mes = ?, salario_base = ? WHERE id = ?";
            $params = [$nome, $email, $saldo, $salario, $usuarioId];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        // Atualiza a sessão com o novo nome
        $_SESSION['usuario_nome'] = $nome;

        header('Location: /financeiro/public/index.php/perfil?sucesso=1');
        exit;
    }
}
