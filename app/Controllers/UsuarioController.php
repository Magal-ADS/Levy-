<?php

declare(strict_types=1);

class UsuarioController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function index(): void
    {
        Auth::requireAdmin($this->pdo);
        $usuarios = $this->pdo->query(
            "SELECT id, nome, email, ativo, is_admin, criado_em
             FROM usuarios
             WHERE email IS NOT NULL
             ORDER BY nome ASC"
        )->fetchAll();
        $error = $_GET['erro'] ?? null;
        require __DIR__ . '/../Views/usuarios.php';
    }

    public function salvar(): void
    {
        Auth::requireAdmin($this->pdo);
        require_post_request();

        $name = trim($_POST['nome'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = (string) ($_POST['senha'] ?? '');
        $confirmation = (string) ($_POST['senha_confirmacao'] ?? '');

        if ($name === '' || strlen($name) > 100) {
            $this->redirectError('nome');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $this->redirectError('email');
        }
        if (strlen($password) < 12 || strlen($password) > 255) {
            $this->redirectError('senha');
        }
        if (!hash_equals($password, $confirmation)) {
            $this->redirectError('confirmacao');
        }

        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare(
                "INSERT INTO usuarios (nome, email, senha_hash, ativo, is_admin)
                 VALUES (?, ?, ?, 1, 0)
                 RETURNING id"
            );
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $userId = (int) $stmt->fetchColumn();

            $seed = $this->pdo->prepare(
                "INSERT INTO categorias (usuario_id, nome, tipo) VALUES
                 (?, 'Alimentação', 'despesa'),
                 (?, 'Moradia', 'despesa'),
                 (?, 'Salário', 'receita'),
                 (?, 'Lazer', 'despesa')"
            );
            $seed->execute([$userId, $userId, $userId, $userId]);
            $this->pdo->commit();
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($e->getCode() === '23505') {
                $this->redirectError('email_existente');
            }
            throw $e;
        }

        header('Location: ' . app_url('usuarios') . '?sucesso=1');
        exit;
    }

    public function alternarStatus(): void
    {
        Auth::requireAdmin($this->pdo);
        require_post_request();

        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id === current_user_id()) {
            $this->redirectError('status');
        }

        $stmt = $this->pdo->prepare(
            "UPDATE usuarios SET ativo = CASE WHEN ativo = 1 THEN 0 ELSE 1 END,
                    atualizado_em = CURRENT_TIMESTAMP
             WHERE id = ? AND email IS NOT NULL AND is_admin = 0"
        );
        $stmt->execute([$id]);

        header('Location: ' . app_url('usuarios') . '?sucesso=status');
        exit;
    }

    private function redirectError(string $error): never
    {
        header('Location: ' . app_url('usuarios') . '?erro=' . urlencode($error));
        exit;
    }
}
