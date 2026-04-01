<?php
// app/Controllers/ConfigController.php

class ConfigController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        $mesSelecionado = $_GET['mes'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $mesSelecionado)) {
            $mesSelecionado = date('Y-m');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE id = 1");
        $stmt->execute();
        $usuario = $stmt->fetch();

        $salarioBase = (float) ($usuario['salario_base'] ?? 0);

        $sqlAnalise = "SELECT
                           COALESCE(c.nome, 'Sem categoria') AS categoria_nome,
                           SUM(dt.valor_divisao) AS total_gasto
                       FROM divisoes_transacao dt
                       INNER JOIN transacoes t ON t.id = dt.transacao_id
                       LEFT JOIN categorias c ON c.id = t.categoria_id
                       WHERE dt.pessoa_id IS NULL
                         AND t.tipo = 'despesa'
                         AND t.mes_referencia = ?
                       GROUP BY COALESCE(c.nome, 'Sem categoria')
                       ORDER BY total_gasto DESC, categoria_nome ASC";

        $stmtAnalise = $this->pdo->prepare($sqlAnalise);
        $stmtAnalise->execute([$mesSelecionado]);
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

            $stmt = $this->pdo->prepare("UPDATE usuarios SET nome = ?, salario_base = ?, saldo_inicial_mes = ? WHERE id = 1");
            $stmt->execute([$nome, $salario, $saldoInicial]);

            header('Location: /financeiro/public/index.php/configuracoes?sucesso=1&mes=' . urlencode($mesSelecionado));
            exit;
        }
    }
}
