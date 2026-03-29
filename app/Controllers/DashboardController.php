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

        // ID fixo do usuário (Magal)
        $usuarioId = 1; 

        // 2. Pega dados do seu Perfil (Salário Base e Saldo Inicial)
        $stmt = $this->pdo->prepare("SELECT salario_base, saldo_inicial_mes FROM usuarios WHERE id = ?");
        $stmt->execute([$usuarioId]);
        $usuario = $stmt->fetch();
        
        $salario = $usuario['salario_base'] ?? 0;
        $saldoInicial = $usuario['saldo_inicial_mes'] ?? 0;

        // 3. Calcula "A Receber" (Dívidas de amigos pendentes - Global)
        $sqlReceber = "SELECT SUM(dt.valor_divisao) as total 
                       FROM divisoes_transacao dt 
                       JOIN transacoes t ON dt.transacao_id = t.id 
                       WHERE dt.status_pago = 0 
                       AND dt.pessoa_id IS NOT NULL";
        $stmtTotalReceber = $this->pdo->query($sqlReceber);
        $aReceber = $stmtTotalReceber->fetch()['total'] ?? 0;

        // 4. Calcula "Minhas Despesas" (A SUA parte das contas REAIS já lançadas no mês)
        $sqlDespesas = "SELECT SUM(dt.valor_divisao) as total 
                        FROM divisoes_transacao dt 
                        JOIN transacoes t ON dt.transacao_id = t.id 
                        WHERE dt.pessoa_id IS NULL 
                        AND t.tipo = 'despesa' 
                        AND t.mes_referencia = ?";
        $stmtMinhasDespesas = $this->pdo->prepare($sqlDespesas);
        $stmtMinhasDespesas->execute([$mesReferencia]);
        $minhasDespesas = $stmtMinhasDespesas->fetch()['total'] ?? 0;

        // 5. LÓGICA DE CONTAS FIXAS AUTOMÁTICAS (Comprometimento de Saldo)
        // Buscamos o valor de contas que são 'automatico' e estão ativas, 
        // mas que ainda NÃO possuem um lançamento correspondente na tabela de transações deste mês.
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

        // 6. Saldo Disponível Real
        // (Salário + Inicial) - (Despesas lançadas) - (Contas fixas automáticas pendentes)
        $saldoDisponivel = ($salario + $saldoInicial) - $minhasDespesas - $fixasComprometidas;

        // 7. BUSCA DAS TRANSAÇÕES PARA A TABELA
        $sqlLancamentos = "
            SELECT t.*, c.nome as categoria_nome, cr.nome as cartao_nome,
            (SELECT GROUP_CONCAT(p.nome SEPARATOR ', ') 
             FROM divisoes_transacao dt2 
             JOIN pessoas p ON dt2.pessoa_id = p.id 
             WHERE dt2.transacao_id = t.id) as amigos_nomes
            FROM transacoes t
            LEFT JOIN categorias c ON t.categoria_id = c.id
            LEFT JOIN cartoes cr ON t.cartao_id = cr.id
            WHERE t.mes_referencia = :mes";

        $params = [':mes' => $mesReferencia];

        if (!empty($busca)) {
            $sqlLancamentos .= " AND (
                t.descricao LIKE :b1 
                OR c.nome LIKE :b2 
                OR cr.nome LIKE :b3 
                OR EXISTS (
                    SELECT 1 FROM divisoes_transacao dt3 
                    JOIN pessoas p2 ON dt3.pessoa_id = p2.id 
                    WHERE dt3.transacao_id = t.id AND p2.nome LIKE :b4
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

        // 8. Envia todas as variáveis para a View
        require_once '../app/Views/dashboard.php';
    }
}