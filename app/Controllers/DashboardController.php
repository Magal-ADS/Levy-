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

        $busca = isset($_GET['q']) ? trim($_GET['q']) : '';

        $mesesBr = [
            '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
            '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
            '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
        ];

        $partesData = explode('-', $mesReferencia);
        $nomeMesAno = $mesesBr[$partesData[1]] . ' de ' . $partesData[0];

        $usuarioId = 1;

        $stmt = $this->pdo->prepare("SELECT saldo_inicial_mes FROM usuarios WHERE id = ?");
        $stmt->execute([$usuarioId]);
        $usuario = $stmt->fetch();
        $saldoInicial = (float) ($usuario['saldo_inicial_mes'] ?? 0);

        $sqlEntradas = "SELECT SUM(dt.valor_divisao) as total
                        FROM divisoes_transacao dt
                        JOIN transacoes t ON dt.transacao_id = t.id
                        WHERE dt.pessoa_id IS NULL
                          AND t.tipo = 'entrada'
                          AND t.mes_referencia = ?";
        $stmtEntradas = $this->pdo->prepare($sqlEntradas);
        $stmtEntradas->execute([$mesReferencia]);
        $entradasReais = (float) ($stmtEntradas->fetch()['total'] ?? 0);

        $sqlReceber = "SELECT SUM(dt.valor_divisao) as total
                       FROM divisoes_transacao dt
                       JOIN transacoes t ON dt.transacao_id = t.id
                       WHERE dt.status_pago = 0
                         AND dt.pessoa_id IS NOT NULL
                         AND t.mes_referencia = ?";
        $stmtTotalReceber = $this->pdo->prepare($sqlReceber);
        $stmtTotalReceber->execute([$mesReferencia]);
        $aReceber = (float) ($stmtTotalReceber->fetch()['total'] ?? 0);

        $sqlDespesas = "SELECT SUM(dt.valor_divisao) as total
                        FROM divisoes_transacao dt
                        JOIN transacoes t ON dt.transacao_id = t.id
                        WHERE dt.pessoa_id IS NULL
                          AND t.tipo = 'despesa'
                          AND t.mes_referencia = ?";
        $stmtMinhasDespesas = $this->pdo->prepare($sqlDespesas);
        $stmtMinhasDespesas->execute([$mesReferencia]);
        $minhasDespesas = (float) ($stmtMinhasDespesas->fetch()['total'] ?? 0);

        $sqlFixasAuto = "SELECT SUM(valor_estimado) as total
                         FROM contas_fixas
                         WHERE tipo_pagamento = 'automatico'
                           AND ativo = 1
                           AND descricao NOT IN (
                               SELECT descricao FROM transacoes WHERE mes_referencia = ?
                           )";
        $stmtFixas = $this->pdo->prepare($sqlFixasAuto);
        $stmtFixas->execute([$mesReferencia]);
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

        $saldoDisponivel = ($saldoInicial + $entradasReais) - $minhasDespesas - $fixasComprometidas;

        $sqlLancamentos = "
            SELECT t.*, c.nome as categoria_nome, cr.nome as cartao_nome,
            (SELECT STRING_AGG(p.nome, ', ')
             FROM divisoes_transacao dt2
             JOIN pessoas p ON dt2.pessoa_id = p.id
             WHERE dt2.transacao_id = t.id) as amigos_nomes
            FROM transacoes t
            LEFT JOIN categorias c ON t.categoria_id = c.id
            LEFT JOIN cartoes cr ON t.cartao_id = cr.id
            WHERE t.mes_referencia = :mes";

        $params = [':mes' => $mesReferencia];

        if ($busca !== '') {
            $sqlLancamentos .= " AND (
                t.descricao ILIKE :b1
                OR c.nome ILIKE :b2
                OR cr.nome ILIKE :b3
                OR EXISTS (
                    SELECT 1 FROM divisoes_transacao dt3
                    JOIN pessoas p2 ON dt3.pessoa_id = p2.id
                    WHERE dt3.transacao_id = t.id AND p2.nome ILIKE :b4
                )
            )";

            $termoBusca = '%' . $busca . '%';
            $params[':b1'] = $termoBusca;
            $params[':b2'] = $termoBusca;
            $params[':b3'] = $termoBusca;
            $params[':b4'] = $termoBusca;
        }

        $sqlLancamentos .= " ORDER BY t.data_movimentacao DESC";

        $stmtLista = $this->pdo->prepare($sqlLancamentos);
        $stmtLista->execute($params);
        $transacoes = $stmtLista->fetchAll();

        require_once '../app/Views/dashboard.php';
    }
}
