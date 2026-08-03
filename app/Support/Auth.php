<?php

declare(strict_types=1);

final class Auth
{
    private const IDLE_TIMEOUT_SECONDS = 7200;

    public static function boot(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $httpsEnabled = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || getenv('APP_HTTPS') === '1';
        $cookiePath = app_base_path() ?: '/';

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('levy_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $cookiePath,
            'secure' => $httpsEnabled,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        $lastActivity = (int) ($_SESSION['auth_last_activity'] ?? 0);
        if ($lastActivity > 0 && time() - $lastActivity > self::IDLE_TIMEOUT_SECONDS) {
            self::logout();
            session_start();
        }

        if (isset($_SESSION['auth_user'])) {
            $_SESSION['auth_last_activity'] = time();
        }
    }

    public static function hasConfiguredUser(PDO $pdo): bool
    {
        return (bool) $pdo->query(
            "SELECT EXISTS (
                SELECT 1 FROM usuarios
                WHERE email IS NOT NULL AND senha_hash IS NOT NULL AND ativo = 1
            )"
        )->fetchColumn();
    }

    public static function attempt(PDO $pdo, string $email, string $password): bool
    {
        $email = self::normalizeEmail($email);
        $stmt = $pdo->prepare(
            "SELECT id, nome, email, senha_hash, is_admin
             FROM usuarios
             WHERE LOWER(email) = ? AND ativo = 1
             LIMIT 1"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string) $user['senha_hash'])) {
            return false;
        }

        if (password_needs_rehash((string) $user['senha_hash'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare(
                "UPDATE usuarios SET senha_hash = ?, atualizado_em = CURRENT_TIMESTAMP WHERE id = ?"
            );
            $update->execute([$newHash, $user['id']]);
        }

        session_regenerate_id(true);
        $_SESSION['auth_user'] = [
            'id' => (int) $user['id'],
            'nome' => (string) $user['nome'],
            'email' => (string) $user['email'],
            'is_admin' => (int) $user['is_admin'] === 1,
        ];
        $_SESSION['auth_last_activity'] = time();
        unset($_SESSION['csrf_token']);

        return true;
    }

    public static function check(PDO $pdo): bool
    {
        $id = self::idOrNull();
        if ($id === null) {
            return false;
        }

        $stmt = $pdo->prepare("SELECT nome, email, is_admin FROM usuarios WHERE id = ? AND ativo = 1");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) {
            self::logout();
            return false;
        }

        $_SESSION['auth_user'] = [
            'id' => $id,
            'nome' => (string) $user['nome'],
            'email' => (string) $user['email'],
            'is_admin' => (int) $user['is_admin'] === 1,
        ];
        $_SESSION['auth_last_activity'] = time();
        return true;
    }

    public static function requireUser(PDO $pdo): void
    {
        if (!self::check($pdo)) {
            header('Location: ' . app_url('login'));
            exit;
        }
    }

    public static function requireAdmin(PDO $pdo): void
    {
        self::requireUser($pdo);
        if (!self::isAdmin()) {
            http_response_code(403);
            echo 'Acesso negado.';
            exit;
        }
    }

    public static function id(): int
    {
        $id = self::idOrNull();
        if ($id === null) {
            throw new RuntimeException('Usuário não autenticado.');
        }
        return $id;
    }

    public static function user(): ?array
    {
        return isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user'])
            ? $_SESSION['auth_user']
            : null;
    }

    public static function isAdmin(): bool
    {
        return (bool) (self::user()['is_admin'] ?? false);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function loginKey(string $email): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return hash('sha256', self::normalizeEmail($email) . '|' . $ip);
    }

    public static function loginAllowed(PDO $pdo, string $key): bool
    {
        $stmt = $pdo->prepare(
            "SELECT bloqueado_ate IS NULL OR bloqueado_ate <= CURRENT_TIMESTAMP
             FROM tentativas_login WHERE chave = ?"
        );
        $stmt->execute([$key]);
        $allowed = $stmt->fetchColumn();
        return $allowed === false || (bool) $allowed;
    }

    public static function recordFailedLogin(PDO $pdo, string $key): void
    {
        $stmt = $pdo->prepare(
            "INSERT INTO tentativas_login (chave, tentativas)
             VALUES (?, 1)
             ON CONFLICT (chave) DO UPDATE SET
                 tentativas = CASE
                     WHEN tentativas_login.primeira_tentativa < CURRENT_TIMESTAMP - INTERVAL '15 minutes' THEN 1
                     ELSE tentativas_login.tentativas + 1
                 END,
                 primeira_tentativa = CASE
                     WHEN tentativas_login.primeira_tentativa < CURRENT_TIMESTAMP - INTERVAL '15 minutes' THEN CURRENT_TIMESTAMP
                     ELSE tentativas_login.primeira_tentativa
                 END,
                 bloqueado_ate = CASE
                     WHEN (CASE
                         WHEN tentativas_login.primeira_tentativa < CURRENT_TIMESTAMP - INTERVAL '15 minutes' THEN 1
                         ELSE tentativas_login.tentativas + 1
                     END) >= 5 THEN CURRENT_TIMESTAMP + INTERVAL '15 minutes'
                     ELSE NULL
                 END,
                 atualizado_em = CURRENT_TIMESTAMP"
        );
        $stmt->execute([$key]);
    }

    public static function clearFailedLogins(PDO $pdo, string $key): void
    {
        $stmt = $pdo->prepare("DELETE FROM tentativas_login WHERE chave = ?");
        $stmt->execute([$key]);
    }

    private static function idOrNull(): ?int
    {
        $id = $_SESSION['auth_user']['id'] ?? null;
        return is_int($id) || ctype_digit((string) $id) ? (int) $id : null;
    }

    private static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}

function current_user_id(): int
{
    return Auth::id();
}

function current_user(): ?array
{
    return Auth::user();
}
