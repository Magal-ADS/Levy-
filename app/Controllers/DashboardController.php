<?php
// app/Controllers/DashboardController.php

class DashboardController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        // 1. Filtros de busca e mês
        $mesReferencia = $_GET['mes'] ?? date('Y-m');
        $busca = isset($_GET['q']) ? trim($_GET['q']) : ''; 

        // Tradução dos meses para Português
        $mesesBr = [
            '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
            '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
            '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
        ];

        $partesData = explode('-', $mesReferencia);
        $nomeMesAno = $mesesBr[$partesData[1]] . " de " . $partesData[0];

        $usuarioId = 1; 

        // 2. Pega o Saldo Inicial
        $stmt = $this->pdo->prepare("SELECT saldo_inicial_mes FROM usuarios WHERE id = ?");
        $stmt->execute([$usuarioId]);
        $usuario = $stmt->fetch();
        
        $saldoInicial = $usuario['saldo_inicial_mes'] ?? 0;

        // 3. Entradas Reais (Filtrado por mês)
        $sqlEntradas = "SELECT SUM(dt.valor_divisao) as total 
                        FROM divisoes_transacao dt 
                        JOIN transacoes t ON dt.transacao_id = t.id 
                        WHERE dt.pessoa_id IS NULL 
                        AND t.tipo = 'entrada' 
                        AND t.mes_referencia = ?";
        $stmtEntradas = $this->pdo->prepare($sqlEntradas);
        $stmtEntradas->execute([$mesReferencia]);
        $entradasReais = $stmtEntradas->fetch()['total'] ?? 0;

        // 4. A Receber (Filtrado por mês)
        $sqlReceber = "SELECT SUM(dt.valor_divisao) as total 
                       FROM divisoes_transacao dt 
                       JOIN transacoes t ON dt.transacao_id = t.id 
                       WHERE dt.status_pago = 0 
                       AND dt.pessoa_id IS NOT NULL 
                       AND t.mes_referencia = ?";
        $stmtTotalReceber = $this->pdo->prepare($sqlReceber);
        $stmtTotalReceber->execute([$mesReferencia]);
        $aReceber = $stmtTotalReceber->fetch()['total'] ?? 0;

        // 5. Minhas Despesas (Filtrado por mês)
        $sqlDespesas = "SELECT SUM(dt.valor_divisao) as total 
                        FROM divisoes_transacao dt 
                        JOIN transacoes t ON dt.transacao_id = t.id 
                        WHERE dt.pessoa_id IS NULL 
                        AND t.tipo = 'despesa' 
                        AND t.mes_referencia = ?";
        $stmtMinhasDespesas = $this->pdo->prepare($sqlDespesas);
        $stmtMinhasDespesas->execute([$mesReferencia]);
        $minhasDespesas = $stmtMinhasDespesas->fetch()['total'] ?? 0;

        // 6. Contas Fixas Automáticas Pendentes (Filtrado por mês)
        $sqlFixasAuto = "SELECT SUM(valor_estimado) as total 
                         FROM contas_fixas 
                         WHERE tipo_pagamento = 'automatico' 
                         AND ativo = 1 
                         AND descricao NOT IN (
                             SELECT descricao FROM transacoes WHERE mes_referencia = ?
                         )";
        $stmtFixas = $this->pdo->prepare($sqlFixasAuto);
        $stmtFixas->execute([$mesReferencia]);
        $fixasComprometidas = $stmtFixas->fetch()['total'] ?? 0;

        // 7. Saldo Disponível Real
        $saldoDisponivel = ($saldoInicial + $entradasReais) - $minhasDespesas - $fixasComprometidas;

        // 8. Busca das Transações para a Tabela (Substituído GROUP_CONCAT por STRING_AGG)
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

        if (!empty($busca)) {
            // Substituído LIKE por ILIKE para o Postgres ignorar maiúsculas/minúsculas
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
            
            $termoBusca = "%$busca%";
            $params[':b1'] = $termoBusca;
            $params[':b2'] = $termoBusca;
            $params[':b3'] = $termoBusca;
            $params[':b4'] = $termoBusca;
        }

        $sqlLancamentos .= " ORDER BY t.data_movimentacao DESC";

        $stmtLista = $this->pdo->prepare($sqlLancamentos);
        $stmtLista->execute($params);
        $transacoes = $stmtLista->fetchAll();

        // 9. Envia todas as variáveis para a View
        require_once '../app/Views/dashboard.php';
    }
}