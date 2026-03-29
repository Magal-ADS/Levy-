<?php
// app/Controllers/ContaFixaController.php

class ContaFixaController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Auxiliar para transformar "1.500,50" em 1500.50
     */
    private function limparMoeda($valor) {
        if (empty($valor)) return 0;
        if (is_numeric($valor)) return (float) $valor;
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
        return (float) $valor;
    }

    /**
     * Lista os moldes de contas fixas e verifica o status de pagamento no mês atual
     */
    public function index() {
        // O sistema sempre olha para o mês atual para saber o que já foi "baixado"
        $mesReferencia = date('Y-m');

        $contas = $this->pdo->query("
            SELECT cf.*, c.nome as categoria_nome, cr.nome as cartao_nome 
            FROM contas_fixas cf 
            LEFT JOIN categorias c ON cf.categoria_id = c.id 
            LEFT JOIN cartoes cr ON cf.cartao_id = cr.id 
            WHERE cf.ativo = 1
            ORDER BY cf.dia_vencimento ASC
        ")->fetchAll();

        // CORREÇÃO DO BUG: Usamos a chave $key para evitar que o PHP sobrescreva os dados na memória
        foreach ($contas as $key => $cf) {
            $stmt = $this->pdo->prepare("SELECT id FROM transacoes WHERE descricao = ? AND mes_referencia = ?");
            $stmt->execute([$cf['descricao'], $mesReferencia]);
            $contas[$key]['pago'] = $stmt->fetch() ? true : false;
        }

        $categorias = $this->pdo->query("SELECT id, nome FROM categorias ORDER BY nome ASC")->fetchAll();
        $cartoes = $this->pdo->query("SELECT id, nome FROM cartoes ORDER BY nome ASC")->fetchAll();

        require_once '../app/Views/contas-fixas.php';
    }

    /**
     * Salva um novo molde de conta fixa
     */
    public function salvar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sql = "INSERT INTO contas_fixas (descricao, valor_estimado, dia_vencimento, categoria_id, cartao_id, tipo_pagamento) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $_POST['descricao'],
                $this->limparMoeda($_POST['valor_estimado']),
                $_POST['dia_vencimento'],
                !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null,
                !empty($_POST['cartao_id']) ? $_POST['cartao_id'] : null,
                $_POST['tipo_pagamento']
            ]);

            header('Location: /financeiro/public/index.php/contas-fixas?sucesso=1');
            exit;
        }
    }

    /**
     * "Dá baixa" em uma conta fixa, transformando-a em uma transação real no mês atual
     */
    public function pagar() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /financeiro/public/index.php/contas-fixas');
            exit;
        }

        $mesReferencia = date('Y-m');

        // Busca o "molde" da conta fixa
        $stmt = $this->pdo->prepare("SELECT * FROM contas_fixas WHERE id = ?");
        $stmt->execute([$id]);
        $conta = $stmt->fetch();

        if (!$conta) {
            header('Location: /financeiro/public/index.php/contas-fixas');
            exit;
        }

        try {
            $this->pdo->beginTransaction();

            // Monta a data de movimentação baseada no dia de vencimento e mês/ano atuais
            $dataMovimentacao = date('Y-m-') . str_pad($conta['dia_vencimento'], 2, '0', STR_PAD_LEFT);
            
            // 1. Insere a transação principal
            $sqlT = "INSERT INTO transacoes (descricao, valor_total, tipo, data_movimentacao, mes_referencia, categoria_id, cartao_id) 
                     VALUES (?, ?, 'despesa', ?, ?, ?, ?)";
            $stmtT = $this->pdo->prepare($sqlT);
            $stmtT->execute([
                $conta['descricao'], 
                $conta['valor_estimado'], 
                $dataMovimentacao, 
                $mesReferencia, 
                $conta['categoria_id'], 
                $conta['cartao_id']
            ]);

            $transacaoId = $this->pdo->lastInsertId();

            // 2. Registra a sua parte (Magal/Levy) na tabela de divisões
            // pessoa_id = NULL significa que a conta é sua
            $this->pdo->prepare("INSERT INTO divisoes_transacao (transacao_id, pessoa_id, valor_divisao, status_pago) VALUES (?, NULL, ?, 1)")
                      ->execute([$transacaoId, $conta['valor_estimado']]);

            $this->pdo->commit();
            header("Location: /financeiro/public/index.php/contas-fixas?sucesso=1");
            exit;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            echo "Erro ao processar pagamento: " . $e->getMessage();
        }
    }

    /**
     * Desativa um molde de conta fixa (Soft Delete)
     */
    public function deletar() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            // Apenas desativamos para manter a integridade de lançamentos antigos
            $this->pdo->prepare("UPDATE contas_fixas SET ativo = 0 WHERE id = ?")->execute([$id]);
        }
        header('Location: /financeiro/public/index.php/contas-fixas?sucesso=1');
        exit;
    }
}