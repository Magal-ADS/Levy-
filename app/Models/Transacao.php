<?php
// app/Models/Transacao.php

class Transacao {
    private $pdo;

    // Recebe a conexão com o banco quando a classe for instanciada
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Salva uma nova transação e suas divisões
     * Ignora divisões com valor igual a zero.
     */
    public function salvarTransacaoComDivisao($dados, $divisoes) {
        try {
            // 1. Inicia a transação (garante integridade dos dados)
            $this->pdo->beginTransaction();

            // 2. Prepara e insere a transação principal
            $sqlTransacao = "INSERT INTO transacoes 
                (descricao, valor_total, tipo, data_movimentacao, mes_referencia, categoria_id, cartao_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sqlTransacao);
            $stmt->execute([
                $dados['descricao'],
                $dados['valor_total'],
                $dados['tipo'],
                $dados['data_movimentacao'],
                $dados['mes_referencia'],
                $dados['categoria_id'] ?? null,
                $dados['cartao_id'] ?? null
            ]);

            // 3. Pega o ID da transação recém-criada
            $transacaoId = $this->pdo->lastInsertId();

            // 4. Prepara a inserção das divisões
            $sqlDivisao = "INSERT INTO divisoes_transacao 
                (transacao_id, pessoa_id, valor_divisao, status_pago) 
                VALUES (?, ?, ?, ?)";
            
            $stmtDivisao = $this->pdo->prepare($sqlDivisao);

            // 5. Roda um loop para salvar cada pessoa atrelada a essa conta
            foreach ($divisoes as $divisao) {
                // SÓ SALVA NO BANCO SE O VALOR FOR MAIOR QUE ZERO
                if ($divisao['valor_divisao'] > 0) {
                    $stmtDivisao->execute([
                        $transacaoId,
                        $divisao['pessoa_id'] !== "" ? $divisao['pessoa_id'] : null, 
                        $divisao['valor_divisao'],
                        $divisao['status_pago'] ?? 0   
                    ]);
                }
            }

            // 6. Confirma as inserções
            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            // Desfaz tudo se houver erro
            $this->pdo->rollBack();
            throw new Exception("Erro ao salvar transação: " . $e->getMessage());
        }
    }

    /**
     * Confirma que um amigo pagou a dívida:
     * 1. Atualiza o status da divisão para pago.
     * 2. Cria uma nova transação de ENTRADA para o usuário.
     */
    public function confirmarPagamentoAmigo($divisaoId) {
        try {
            $this->pdo->beginTransaction();

            // 1. Busca os detalhes da dívida e o nome da pessoa
            $sql = "SELECT dt.*, t.descricao, p.nome as nome_pessoa 
                    FROM divisoes_transacao dt 
                    JOIN transacoes t ON dt.transacao_id = t.id 
                    JOIN pessoas p ON dt.pessoa_id = p.id
                    WHERE dt.id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$divisaoId]);
            $divisao = $stmt->fetch();

            if (!$divisao) {
                throw new Exception("Dívida não encontrada.");
            }

            // 2. Marca a divisão original como paga (status = 1)
            $stmtUpdate = $this->pdo->prepare("UPDATE divisoes_transacao SET status_pago = 1 WHERE id = ?");
            $stmtUpdate->execute([$divisaoId]);

            // 3. Cria uma nova transação de RECEITA (Entrada) no seu saldo
            $sqlEntrada = "INSERT INTO transacoes 
                (descricao, valor_total, tipo, data_movimentacao, mes_referencia, categoria_id) 
                VALUES (?, ?, 'receita', CURDATE(), ?, NULL)";
            
            $stmtEntrada = $this->pdo->prepare($sqlEntrada);
            $stmtEntrada->execute([
                "Recebimento: " . $divisao['nome_pessoa'] . " (" . $divisao['descricao'] . ")",
                $divisao['valor_divisao'],
                date('Y-m') // Registra no mês atual
            ]);

            // 4. Registra a divisão dessa entrada para você (pessoa_id = NULL)
            $novaTransacaoId = $this->pdo->lastInsertId();
            $stmtDiv = $this->pdo->prepare("INSERT INTO divisoes_transacao (transacao_id, pessoa_id, valor_divisao, status_pago) VALUES (?, NULL, ?, 1)");
            $stmtDiv->execute([$novaTransacaoId, $divisao['valor_divisao']]);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Erro ao confirmar pagamento: " . $e->getMessage());
        }
    }

    /**
     * Método simples para apenas alterar o status de uma divisão
     */
    public function darBaixaEmDivisao($divisaoId) {
        $sql = "UPDATE divisoes_transacao SET status_pago = 1 WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$divisaoId]);
    }
}