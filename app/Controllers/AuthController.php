<?php

declare(strict_types=1);

class AuthController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function loginForm(): void
    {
        if (Auth::check($this->pdo)) {
            header('Location: ' . app_url());
            exit;
        }

        $error = $_GET['erro'] ?? null;
        require __DIR__ . '/../Views/auth/login.php';
    }

    public function login(): void
    {
        require_post_request();

        $email = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['senha'] ?? '');
        $key = Auth::loginKey($email);

        if (!Auth::loginAllowed($this->pdo, $key)) {
            header('Location: ' . app_url('login') . '?erro=bloqueado');
            exit;
        }

        if (!Auth::attempt($this->pdo, $email, $password)) {
            Auth::recordFailedLogin($this->pdo, $key);
            usleep(300000);
            header('Location: ' . app_url('login') . '?erro=credenciais');
            exit;
        }

        Auth::clearFailedLogins($this->pdo, $key);
        header('Location: ' . app_url());
        exit;
    }

    public function setupForm(): void
    {
        if (Auth::hasConfiguredUser($this->pdo)) {
            header('Location: ' . app_url('login'));
            exit;
        }

        $error = $_GET['erro'] ?? null;
        require __DIR__ . '/../Views/auth/setup.php';
    }

    public function setup(): void
    {
        require_post_request();
        if (Auth::hasConfiguredUser($this->pdo)) {
            http_response_code(403);
            echo 'A configuração inicial já foi concluída.';
            exit;
        }

        $name = trim($_POST['nome'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = (string) ($_POST['senha'] ?? '');
        $confirmation = (string) ($_POST['senha_confirmacao'] ?? '');

        $error = $this->validateUserData($name, $email, $password, $confirmation);
        if ($error !== null) {
            header('Location: ' . app_url('setup') . '?erro=' . urlencode($error));
            exit;
        }

        $ownerId = $this->pdo->query("SELECT id FROM usuarios ORDER BY id ASC LIMIT 1")->fetchColumn();
        if (!$ownerId) {
            throw new RuntimeException('Usuário proprietário não encontrado. Execute a migração novamente.');
        }

        $stmt = $this->pdo->prepare(
            "UPDATE usuarios
             SET nome = ?, email = ?, senha_hash = ?, ativo = 1, is_admin = 1,
                 atualizado_em = CURRENT_TIMESTAMP
             WHERE id = ? AND senha_hash IS NULL"
        );
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $ownerId]);

        if ($stmt->rowCount() !== 1 || !Auth::attempt($this->pdo, $email, $password)) {
            throw new RuntimeException('Não foi possível concluir a configuração inicial.');
        }

        header('Location: ' . app_url() . '?setup=1');
        exit;
    }

    public function logout(): void
    {
        require_post_request();
        Auth::logout();
        header('Location: ' . app_url('login'));
        exit;
    }

    private function validateUserData(string $name, string $email, string $password, string $confirmation): ?string
    {
        if ($name === '' || strlen($name) > 100) {
            return 'nome';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            return 'email';
        }
        if (strlen($password) < 12 || strlen($password) > 255) {
            return 'senha';
        }
        if (!hash_equals($password, $confirmation)) {
            return 'confirmacao';
        }
        return null;
    }
}
