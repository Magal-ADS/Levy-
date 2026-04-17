<?php
// app/Controllers/AuthController.php

class AuthController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function login() {
        // Apenas renderiza a view de login isolada e interrompe a execução
        require_once __DIR__ . '/../Views/login.php';
        exit;
    }

    public function autenticar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /financeiro/public/index.php/login');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if ($email === '' || $senha === '') {
            header('Location: /financeiro/public/index.php/login?erro=invalid');
            exit;
        }

        $stmt = $this->pdo->prepare("SELECT id, senha FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if (!$u || !password_verify($senha, $u['senha'])) {
            header('Location: /financeiro/public/index.php/login?erro=credenciais');
            exit;
        }

        // Autentica o usuário na sessão
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = (int) $u['id'];

        header('Location: /financeiro/public/index.php');
        exit;
    }

    public function logout() {
        session_start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        header('Location: /financeiro/public/index.php/login');
        exit;
    }
}
