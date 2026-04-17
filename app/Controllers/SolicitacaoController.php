<?php
// app/Controllers/SolicitacaoController.php

class SolicitacaoController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        $usuarioId = $_SESSION['usuario_id'] ?? 0;

        $sql = "SELECT dt.id as divisao_id, t.id as transacao_id, t.descricao, dt.valor_divisao, t.data_movimentacao, p.nome as remetente_nome, u.nome as remetente_usuario_nome, t.usuario_id
                FROM divisoes_transacao dt
                JOIN transacoes t ON dt.transacao_id = t.id
                JOIN pessoas p ON dt.pessoa_id = p.id
                LEFT JOIN usuarios u ON t.usuario_id = u.id
                WHERE p.vinculo_usuario_id = :uid
                  AND dt.status_aceite = 'pendente'
                ORDER BY t.data_movimentacao DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $usuarioId]);
        $solicitacoes = $stmt->fetchAll();

        require_once '../app/Views/solicitacoes.php';
    }

    public function aceitar() {
        $divisaoId = $_GET['id'] ?? null;
        if (!$divisaoId) {
            header('Location: /financeiro/public/index.php/solicitacoes');
            exit;
        }

        // Só aceitar se essa divisão realmente pertence a uma pessoa vinculada ao usuário logado
        $usuarioId = $_SESSION['usuario_id'] ?? 0;
        $stmt = $this->pdo->prepare("UPDATE divisoes_transacao SET status_aceite = 'aceito' WHERE id = ? AND EXISTS (SELECT 1 FROM pessoas p WHERE p.id = divisoes_transacao.pessoa_id AND p.vinculo_usuario_id = ?)");
        $stmt->execute([$divisaoId, $usuarioId]);

        header('Location: /financeiro/public/index.php/solicitacoes?sucesso=1');
        exit;
    }

    public function recusar() {
        $divisaoId = $_GET['id'] ?? null;
        if (!$divisaoId) {
            header('Location: /financeiro/public/index.php/solicitacoes');
            exit;
        }

        // Só recusar se essa divisão realmente pertence a uma pessoa vinculada ao usuário logado
        $usuarioId = $_SESSION['usuario_id'] ?? 0;
        $stmt = $this->pdo->prepare("UPDATE divisoes_transacao SET status_aceite = 'recusado' WHERE id = ? AND EXISTS (SELECT 1 FROM pessoas p WHERE p.id = divisoes_transacao.pessoa_id AND p.vinculo_usuario_id = ?)");
        $stmt->execute([$divisaoId, $usuarioId]);

        header('Location: /financeiro/public/index.php/solicitacoes?sucesso=1');
        exit;
    }
}
