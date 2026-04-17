<?php
// app/Controllers/DashboardController.php

class DashboardController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        $mesReferencia = $_GET['mes'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $mesReferencia)) {
            $mesReferencia = date('Y-m');
        }

        $busca = isset($_GET['busca']) ? trim($_GET['busca']) : (isset($_GET['q']) ? trim($_GET['q']) : '');

        $mesesBr = [
            '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
            '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
            '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
        ];

        $partesData = explode('-', $mesReferencia);
        $nomeMesAno = $mesesBr[$partesData[1]] . ' de ' . $partesData[0];

        $usuarioId = $_SESSION['usuario_id'] ?? 0;

        $stmt = $this->pdo->prepare("SELECT saldo_inicial_mes FROM usuarios WHERE id = ?");
        $stmt->execute([$usuarioId]);
        $usuario = $stmt->fetch();
        $saldoInicial = (float) ($usuario['saldo_inicial_mes'] ?? 0);

        $sqlEntradas = "SELECT SUM(dt.valor_divisao) as total
                        FROM divisoes_transacao dt
                        JOIN transacoes t ON dt.transacao_id = t.id
                        WHERE dt.pessoa_id IS NULL
                          AND t.tipo = 'entrada'
                          AND t.mes_referencia = ?
                          AND (dt.status_aceite IS NULL OR dt.status_aceite = 'aceito')
                          AND t.usuario_id = ?";
        $stmtEntradas = $this->pdo->prepare($sqlEntradas);
        $stmtEntradas->execute([$mesReferencia, $usuarioId]);
        $entradasReais = (float) ($stmtEntradas->fetch()['total'] ?? 0);

        $sqlReceber = "SELECT SUM(dt.valor_divisao) as total
                       FROM divisoes_transacao dt
                       JOIN transacoes t ON dt.transacao_id = t.id
                       WHERE dt.status_pago = 0
                         AND dt.pessoa_id IS NOT NULL
                         AND t.mes_referencia = ?
                         AND (dt.status_aceite IS NULL OR dt.status_aceite = 'aceito')
                         AND t.usuario_id = ?";
        $stmtTotalReceber = $this->pdo->prepare($sqlReceber);
        $stmtTotalReceber->execute([$mesReferencia, $usuarioId]);
        $aReceber = (float) ($stmtTotalReceber->fetch()['total'] ?? 0);

        $sqlDespesas = "SELECT SUM(dt.valor_divisao) as total
                        FROM divisoes_transacao dt
                        JOIN transacoes t ON dt.transacao_id = t.id
                        WHERE dt.pessoa_id IS NULL
                          AND t.tipo = 'despesa'
                          AND t.mes_referencia = ?
                          AND (dt.status_aceite IS NULL OR dt.status_aceite = 'aceito')
                          AND t.usuario_id = ?";
        $stmtMinhasDespesas = $this->pdo->prepare($sqlDespesas);
        $stmtMinhasDespesas->execute([$mesReferencia, $usuarioId]);
        $minhasDespesas = (float) ($stmtMinhasDespesas->fetch()['total'] ?? 0);

        $sqlFixasAuto = "SELECT SUM(valor_estimado) as total
                         FROM contas_fixas
                         WHERE tipo_pagamento = 'automatico'
                           AND ativo = 1
                           AND descricao NOT IN (
                               SELECT descricao FROM transacoes WHERE mes_referencia = ? AND usuario_id = ?
                           ) AND usuario_id = ?";
        $stmtFixas = $this->pdo->prepare($sqlFixasAuto);
        $stmtFixas->execute([$mesReferencia, $usuarioId, $usuarioId]);
        $fixasComprometidas = (float) ($stmtFixas->fetch()['total'] ?? 0);

        $sqlGraficoCategorias = "SELECT
                                    COALESCE(c.nome, 'Sem categoria') AS categoria_nome,
                                    SUM(dt.valor_divisao) AS total
                                 FROM divisoes_transacao dt
                                 JOIN transacoes t ON dt.transacao_id = t.id
                                 LEFT JOIN categorias c ON t.categoria_id = c.id
                                 WHERE dt.pessoa_id IS NULL
                                   AND t.tipo = 'despesa'
                                   AND t.mes_referencia = ?
                                   AND (dt.status_aceite IS NULL OR dt.status_aceite = 'aceito')
                                 GROUP BY COALESCE(c.nome, 'Sem categoria')
                                 ORDER BY total DESC, categoria_nome ASC";
        $stmtGraficoCategorias = $this->pdo->prepare($sqlGraficoCategorias);
        $stmtGraficoCategorias->execute([$mesReferencia]);
        $gastosPorCategoria = $stmtGraficoCategorias->fetchAll();

        $coresGraficoBase = [
            '#6366F1',
            '#10B981',
            '#F59E0B',
            '#EF4444',
            '#06B6D4',
            '#8B5CF6',
            '#F97316',
            '#14B8A6',
            '#EC4899',
            '#84CC16'
        ];

        $graficoCategoriasLabels = [];
        $graficoCategoriasValores = [];
        $graficoCategoriasCores = [];

        foreach ($gastosPorCategoria as $index => $categoria) {
            $graficoCategoriasLabels[] = $categoria['categoria_nome'];
            $graficoCategoriasValores[] = (float) $categoria['total'];
            $graficoCategoriasCores[] = $coresGraficoBase[$index % count($coresGraficoBase)];
        }

        // carregar categorias e pessoas para o filtro
        $stmtCat = $this->pdo->prepare("SELECT id, nome FROM categorias WHERE usuario_id = ? ORDER BY nome ASC");
        $stmtCat->execute([$usuarioId]);
        $categorias = $stmtCat->fetchAll();

        $stmtPessoas = $this->pdo->prepare("SELECT id, nome FROM pessoas WHERE usuario_id = ? ORDER BY nome ASC");
        $stmtPessoas->execute([$usuarioId]);
        $pessoas = $stmtPessoas->fetchAll();

        $saldoDisponivel = ($saldoInicial + $entradasReais) - $minhasDespesas - $fixasComprometidas;

        // Listagem de lançamentos via model para respeitar Shared Ledger
        require_once __DIR__ . '/../../config/database.php';
        require_once __DIR__ . '/../Models/Transacao.php';
        $model = new Transacao($this->pdo);
        $pessoaFilter = isset($_GET['pessoa_id']) && is_numeric($_GET['pessoa_id']) ? $_GET['pessoa_id'] : null;
        $transacoes = $model->buscarPorMes($usuarioId, $mesReferencia, $busca, $pessoaFilter);

        // detectar requisição AJAX
        $isAjax = (isset($_GET['ajax']) && $_GET['ajax'] === '1') || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if ($isAjax) {
            // responder apenas as linhas da tabela
            require_once __DIR__ . '/../Views/partials/tabela_lancamentos.php';
            exit;
        }

        require_once '../app/Views/dashboard.php';
    }
}
