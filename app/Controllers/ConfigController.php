<?php
// app/Controllers/ConfigController.php

class ConfigController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        $usuarioId = current_user_id();
        $mesSelecionado = $_GET['mes'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $mesSelecionado)) {
            $mesSelecionado = date('Y-m');
        }

        $stmt = $this->pdo->prepare("SELECT id, nome, email, salario_base, saldo_inicial_mes FROM usuarios WHERE id = ?");
        $stmt->execute([$usuarioId]);
        $usuario = $stmt->fetch();

        $salarioBase = (float) ($usuario['salario_base'] ?? 0);

        $sqlAnalise = "SELECT
                           COALESCE(c.nome, 'Sem categoria') AS categoria_nome,
                           SUM(dt.valor_divisao) AS total_gasto
                       FROM divisoes_transacao dt
                       INNER JOIN transacoes t ON t.id = dt.transacao_id
                       LEFT JOIN categorias c ON c.id = t.categoria_id
                       WHERE dt.pessoa_id IS NULL
                         AND t.usuario_id = ?
                         AND t.tipo = 'despesa'
                         AND t.mes_referencia = ?
                       GROUP BY COALESCE(c.nome, 'Sem categoria')
                       ORDER BY total_gasto DESC, categoria_nome ASC";

        $stmtAnalise = $this->pdo->prepare($sqlAnalise);
        $stmtAnalise->execute([$usuarioId, $mesSelecionado]);
        $analiseCategorias = $stmtAnalise->fetchAll();

        $totalDespesasMes = 0;
        foreach ($analiseCategorias as &$categoria) {
            $categoria['total_gasto'] = (float) $categoria['total_gasto'];
            $categoria['percentual_salario'] = $salarioBase > 0
                ? ($categoria['total_gasto'] / $salarioBase) * 100
                : 0;
            $totalDespesasMes += $categoria['total_gasto'];
        }
        unset($categoria);

        $saldoLivre = $salarioBase - $totalDespesasMes;
        $percentualSaldoLivre = $salarioBase > 0
            ? ($saldoLivre / $salarioBase) * 100
            : 0;

        require_once '../app/Views/configuracoes.php';
    }

    public function salvar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuarioId = current_user_id();
            $limparMoeda = function($valor) {
                $valor = str_replace('.', '', $valor);
                $valor = str_replace(',', '.', $valor);
                return (float) $valor;
            };

            $nome = trim($_POST['nome'] ?? '');
            $salario = $limparMoeda($_POST['salario_base'] ?? '0');
            $saldoInicial = $limparMoeda($_POST['saldo_inicial_mes'] ?? '0');
            $mesSelecionado = $_POST['mes'] ?? date('Y-m');

            if (!preg_match('/^\d{4}-\d{2}$/', $mesSelecionado)) {
                $mesSelecionado = date('Y-m');
            }

            if ($nome === '' || strlen($nome) > 100) {
                header('Location: ' . app_url('configuracoes') . '?erro=nome');
                exit;
            }

            $senhaAtual = (string) ($_POST['senha_atual'] ?? '');
            $novaSenha = (string) ($_POST['nova_senha'] ?? '');
            $confirmacao = (string) ($_POST['nova_senha_confirmacao'] ?? '');

            $this->pdo->beginTransaction();
            try {
                $stmt = $this->pdo->prepare(
                    "UPDATE usuarios
                     SET nome = ?, salario_base = ?, saldo_inicial_mes = ?, atualizado_em = CURRENT_TIMESTAMP
                     WHERE id = ?"
                );
                $stmt->execute([$nome, $salario, $saldoInicial, $usuarioId]);

                if ($senhaAtual !== '' || $novaSenha !== '' || $confirmacao !== '') {
                    $stmtPassword = $this->pdo->prepare("SELECT senha_hash FROM usuarios WHERE id = ? FOR UPDATE");
                    $stmtPassword->execute([$usuarioId]);
                    $hashAtual = (string) $stmtPassword->fetchColumn();

                    if (!password_verify($senhaAtual, $hashAtual)) {
                        throw new DomainException('senha_atual');
                    }
                    if (strlen($novaSenha) < 12 || strlen($novaSenha) > 255) {
                        throw new DomainException('nova_senha');
                    }
                    if (!hash_equals($novaSenha, $confirmacao)) {
                        throw new DomainException('confirmacao');
                    }

                    $stmtUpdatePassword = $this->pdo->prepare(
                        "UPDATE usuarios SET senha_hash = ?, atualizado_em = CURRENT_TIMESTAMP WHERE id = ?"
                    );
                    $stmtUpdatePassword->execute([password_hash($novaSenha, PASSWORD_DEFAULT), $usuarioId]);
                    session_regenerate_id(true);
                }

                $this->pdo->commit();
                $_SESSION['auth_user']['nome'] = $nome;
            } catch (DomainException $e) {
                $this->pdo->rollBack();
                header('Location: ' . app_url('configuracoes') . '?erro=' . urlencode($e->getMessage()) . '&mes=' . urlencode($mesSelecionado));
                exit;
            } catch (Throwable $e) {
                $this->pdo->rollBack();
                throw $e;
            }

            header('Location: ' . app_url('configuracoes') . '?sucesso=1&mes=' . urlencode($mesSelecionado));
            exit;
        }
    }
}
