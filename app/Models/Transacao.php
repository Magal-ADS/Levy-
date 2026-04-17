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
            $usuarioId = $_SESSION['usuario_id'] ?? null;
            $sqlTransacao = "INSERT INTO transacoes 
                (descricao, valor_total, tipo, data_movimentacao, mes_referencia, categoria_id, cartao_id, usuario_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                RETURNING id";
            
            $stmt = $this->pdo->prepare($sqlTransacao);
            $stmt->execute([
                $dados['descricao'],
                $dados['valor_total'],
                $dados['tipo'],
                $dados['data_movimentacao'],
                $dados['mes_referencia'],
                $dados['categoria_id'] ?? null,
                $dados['cartao_id'] ?? null,
                $usuarioId
            ]);

            // 3. Pega o ID da transação recém-criada
            $transacaoId = $stmt->fetchColumn();

            // 4. Prepara a inserção das divisões
            $sqlDivisao = "INSERT INTO divisoes_transacao 
                (transacao_id, pessoa_id, valor_divisao, status_pago, status_aceite) 
                VALUES (?, ?, ?, ?, ?)";
            
            $stmtDivisao = $this->pdo->prepare($sqlDivisao);
            $stmtPessoa = $this->pdo->prepare("SELECT vinculo_usuario_id FROM pessoas WHERE id = ?");

            // 5. Roda um loop para salvar cada pessoa atrelada a essa conta
            foreach ($divisoes as $divisao) {
                // SÓ SALVA NO BANCO SE O VALOR FOR MAIOR QUE ZERO
                if ($divisao['valor_divisao'] > 0) {
                    $pessoaId = $divisao['pessoa_id'] !== "" ? $divisao['pessoa_id'] : null;
                    $statusAceite = 'aceito';
                    if (!empty($pessoaId)) {
                        $stmtPessoa->execute([$pessoaId]);
                        $p = $stmtPessoa->fetch();
                        if ($p && !empty($p['vinculo_usuario_id'])) {
                            $statusAceite = 'pendente';
                        }
                    }

                    $stmtDivisao->execute([
                        $transacaoId,
                        $pessoaId,
                        $divisao['valor_divisao'],
                        $divisao['status_pago'] ?? 0,
                        $statusAceite
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
            $usuarioId = $_SESSION['usuario_id'] ?? null;
            $sqlEntrada = "INSERT INTO transacoes 
                (descricao, valor_total, tipo, data_movimentacao, mes_referencia, categoria_id, usuario_id) 
                VALUES (?, ?, 'receita', CURRENT_DATE, ?, NULL, ?)
                RETURNING id";
            
            $stmtEntrada = $this->pdo->prepare($sqlEntrada);
            $stmtEntrada->execute([
                "Recebimento: " . $divisao['nome_pessoa'] . " (" . $divisao['descricao'] . ")",
                $divisao['valor_divisao'],
                date('Y-m'), // Registra no mês atual
                $usuarioId
            ]);

            // 4. Registra a divisão dessa entrada para você (pessoa_id = NULL)
            $novaTransacaoId = $stmtEntrada->fetchColumn();
            $stmtDiv = $this->pdo->prepare("INSERT INTO divisoes_transacao (transacao_id, pessoa_id, valor_divisao, status_pago, status_aceite) VALUES (?, NULL, ?, 1, 'aceito')");
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

    /**
     * Buscar transações para um usuário considerando vínculo de pessoas (Shared Ledger)
     * Retorna transações onde:
     *  - t.usuario_id = :usuarioId (criado por mim)
     *  OR
     *  - existe uma divisão para uma pessoa que tem vinculo_usuario_id = :usuarioId
     * Além disso, expõe campos auxiliares para facilitar a apresentação (minha_divisao_valor, is_linked, usuario_nome)
     */
    public function buscarPorMes(int $usuarioId, string $mesReferencia, string $busca = '', $pessoaFilter = null) {
        $sql = "
            SELECT DISTINCT t.*, c.nome as categoria_nome, cr.nome as cartao_nome,
            (SELECT STRING_AGG(p.nome, ', ') FROM divisoes_transacao dt2 JOIN pessoas p ON dt2.pessoa_id = p.id WHERE dt2.transacao_id = t.id) as amigos_nomes,
            (SELECT dt3.valor_divisao FROM divisoes_transacao dt3 JOIN pessoas p3 ON dt3.pessoa_id = p3.id WHERE dt3.transacao_id = t.id AND p3.vinculo_usuario_id = :uid AND (dt3.status_aceite IS NULL OR dt3.status_aceite = 'aceito') LIMIT 1) AS minha_divisao_valor,
            (SELECT dt3.id FROM divisoes_transacao dt3 JOIN pessoas p3 ON dt3.pessoa_id = p3.id WHERE dt3.transacao_id = t.id AND p3.vinculo_usuario_id = :uid AND (dt3.status_aceite IS NULL OR dt3.status_aceite = 'aceito') LIMIT 1) AS minha_divisao_id,
            (SELECT u.nome FROM usuarios u WHERE u.id = t.usuario_id) AS usuario_nome
            FROM transacoes t
            LEFT JOIN categorias c ON t.categoria_id = c.id
            LEFT JOIN cartoes cr ON t.cartao_id = cr.id
            WHERE t.mes_referencia = :mes
            AND (
                t.usuario_id = :uid
                OR EXISTS (
                    SELECT 1 FROM divisoes_transacao dtx JOIN pessoas pxx ON dtx.pessoa_id = pxx.id
                    WHERE dtx.transacao_id = t.id AND pxx.vinculo_usuario_id = :uid AND (dtx.status_aceite IS NULL OR dtx.status_aceite = 'aceito')
                )
            )
        ";

        $params = [':mes' => $mesReferencia, ':uid' => $usuarioId];

        if (!empty($busca)) {
            $sql .= " AND (
                t.descricao ILIKE :b1
                OR EXISTS (
                    SELECT 1 FROM divisoes_transacao dt3 JOIN pessoas p2 ON dt3.pessoa_id = p2.id WHERE dt3.transacao_id = t.id AND p2.nome ILIKE :b2
                )
            )";
            $params[':b1'] = "%{$busca}%";
            $params[':b2'] = "%{$busca}%";
        }

        // Filtrar por pessoa_id caso informado (aplica-se somente quando a pessoa é específica)
        if (!empty($pessoaFilter) && is_numeric($pessoaFilter)) {
            $sql .= " AND EXISTS (SELECT 1 FROM divisoes_transacao dtf WHERE dtf.transacao_id = t.id AND dtf.pessoa_id = :pessoaFilter)";
            $params[':pessoaFilter'] = (int) $pessoaFilter;
        }

        $sql .= " ORDER BY t.data_movimentacao DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Normalizar retorno: definir tipo e valor de exibição dependendo da perspectiva
        foreach ($rows as &$r) {
            $r['is_linked'] = !empty($r['minha_divisao_id']);
            if ($r['is_linked']) {
                // Para o usuário vinculado, ele vê apenas a sua parte como DESPESA
                $r['display_tipo'] = 'despesa';
                $r['display_valor'] = (float) ($r['minha_divisao_valor'] ?? 0);
                $r['display_descricao'] = 'Dívida para com ' . ($r['usuario_nome'] ?? 'Usuário');
            } else {
                $r['display_tipo'] = $r['tipo'];
                $r['display_valor'] = (float) ($r['valor_total'] ?? 0);
                $r['display_descricao'] = $r['descricao'];
            }
        }

        return $rows;
    }
}
