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

        $usuarioId = current_user_id();

        $stmt = $this->pdo->prepare("SELECT saldo_inicial_mes FROM usuarios WHERE id = ?");
        $stmt->execute([$usuarioId]);
        $usuario = $stmt->fetch();
        $saldoInicial = (float) ($usuario['saldo_inicial_mes'] ?? 0);

        $sqlResumo = "SELECT
                          COALESCE(SUM(dt.valor_divisao) FILTER (
                              WHERE dt.pessoa_id IS NULL AND t.tipo = 'receita'
                          ), 0) AS entradas_reais,
                          COALESCE(SUM(dt.valor_divisao) FILTER (
                              WHERE dt.status_pago = 0 AND dt.pessoa_id IS NOT NULL
                          ), 0) AS total_a_receber,
                          COALESCE(SUM(dt.valor_divisao) FILTER (
                              WHERE dt.pessoa_id IS NULL AND t.tipo = 'despesa'
                          ), 0) AS minhas_despesas
                      FROM transacoes t
                      JOIN divisoes_transacao dt ON dt.transacao_id = t.id
                      WHERE t.usuario_id = ?
                        AND t.mes_referencia = ?";
        $stmtResumo = $this->pdo->prepare($sqlResumo);
        $stmtResumo->execute([$usuarioId, $mesReferencia]);
        $resumo = $stmtResumo->fetch();

        $entradasReais = (float) ($resumo['entradas_reais'] ?? 0);
        $aReceber = (float) ($resumo['total_a_receber'] ?? 0);
        $minhasDespesas = (float) ($resumo['minhas_despesas'] ?? 0);

        $sqlFixasAuto = "SELECT SUM(valor_estimado) as total
                         FROM contas_fixas
                         WHERE usuario_id = ?
                           AND tipo_pagamento = 'automatico'
                           AND ativo = 1
                           AND descricao NOT IN (
                               SELECT descricao FROM transacoes WHERE usuario_id = ? AND mes_referencia = ?
                           )";
        $stmtFixas = $this->pdo->prepare($sqlFixasAuto);
        $stmtFixas->execute([$usuarioId, $usuarioId, $mesReferencia]);
        $fixasComprometidas = (float) ($stmtFixas->fetch()['total'] ?? 0);

        $sqlGraficoCategorias = "SELECT
                                    COALESCE(c.nome, 'Sem categoria') AS categoria_nome,
                                    SUM(dt.valor_divisao) AS total
                                 FROM divisoes_transacao dt
                                 JOIN transacoes t ON dt.transacao_id = t.id
                                 LEFT JOIN categorias c ON t.categoria_id = c.id
                                 WHERE dt.pessoa_id IS NULL
                                   AND t.usuario_id = ?
                                   AND t.tipo = 'despesa'
                                   AND t.mes_referencia = ?
                                 GROUP BY COALESCE(c.nome, 'Sem categoria')
                                 ORDER BY total DESC, categoria_nome ASC";
        $stmtGraficoCategorias = $this->pdo->prepare($sqlGraficoCategorias);
        $stmtGraficoCategorias->execute([$usuarioId, $mesReferencia]);
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

        $sqlLancamentos = "
            SELECT t.*, c.nome as categoria_nome, cr.nome as cartao_nome,
                   divisao_resumo.amigos_nomes
            FROM transacoes t
            LEFT JOIN categorias c ON t.categoria_id = c.id
            LEFT JOIN cartoes cr ON t.cartao_id = cr.id
            LEFT JOIN (
                SELECT dt.transacao_id,
                       STRING_AGG(p.nome, ', ' ORDER BY p.nome)
                           FILTER (WHERE p.id IS NOT NULL) AS amigos_nomes
                FROM divisoes_transacao dt
                LEFT JOIN pessoas p ON dt.pessoa_id = p.id
                WHERE dt.usuario_id = :usuario_divisoes
                GROUP BY dt.transacao_id
            ) divisao_resumo ON divisao_resumo.transacao_id = t.id
            WHERE t.usuario_id = :usuario_id
              AND t.mes_referencia = :mes";

        $params = [
            ':usuario_id' => $usuarioId,
            ':usuario_divisoes' => $usuarioId,
            ':mes' => $mesReferencia,
        ];

        if ($busca !== '') {
            $sqlLancamentos .= " AND (
                t.descricao ILIKE :b1
                OR REPLACE(t.valor_total::TEXT, '.', ',') ILIKE :b1
                OR t.valor_total::TEXT ILIKE :b1
                OR c.nome ILIKE :b2
                OR cr.nome ILIKE :b3
                OR divisao_resumo.amigos_nomes ILIKE :b4
            )";

            $termoBusca = '%' . $busca . '%';
            $params[':b1'] = $termoBusca;
            $params[':b2'] = $termoBusca;
            $params[':b3'] = $termoBusca;
            $params[':b4'] = $termoBusca;
        }

        // aplicar filtro por categoria se informado
        if (isset($_GET['categoria_id']) && $_GET['categoria_id'] !== '') {
            $sqlLancamentos .= " AND t.categoria_id = :categoria_id";
            $params[':categoria_id'] = (int) $_GET['categoria_id'];
        }

        // aplicar filtro por pessoa (mine -> minhas divisões / vazio -> todos / id numérico -> amigo específico)
        if (isset($_GET['pessoa_id']) && $_GET['pessoa_id'] !== '') {
            $p = $_GET['pessoa_id'];
            if ($p === 'mine' || $p === '0') {
                $sqlLancamentos .= " AND EXISTS (
                    SELECT 1 FROM divisoes_transacao dt_filtro
                    WHERE dt_filtro.transacao_id = t.id
                      AND dt_filtro.usuario_id = :usuario_filtro
                      AND dt_filtro.pessoa_id IS NULL
                )";
                $params[':usuario_filtro'] = $usuarioId;
            } elseif (is_numeric($p)) {
                $sqlLancamentos .= " AND EXISTS (
                    SELECT 1 FROM divisoes_transacao dt_filtro
                    WHERE dt_filtro.transacao_id = t.id
                      AND dt_filtro.usuario_id = :usuario_filtro
                      AND dt_filtro.pessoa_id = :pessoa_id
                )";
                $params[':pessoa_id'] = (int) $p;
                $params[':usuario_filtro'] = $usuarioId;
            }
        }

        $sqlLancamentos .= " ORDER BY t.data_movimentacao DESC";

        $stmtLista = $this->pdo->prepare($sqlLancamentos);
        $stmtLista->execute($params);
        $transacoes = $stmtLista->fetchAll();

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
