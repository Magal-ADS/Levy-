<?php
// app/Controllers/RecebimentoController.php
require_once __DIR__ . '/../Models/Transacao.php';

class RecebimentoController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        // Busca todas as divisões de amigos que ainda NÃO foram pagas
        $sql = "SELECT dt.id as divisao_id, dt.valor_divisao, t.descricao, t.data_movimentacao, p.nome as nome_pessoa 
                FROM divisoes_transacao dt 
                JOIN transacoes t ON dt.transacao_id = t.id 
                JOIN pessoas p ON dt.pessoa_id = p.id 
                WHERE dt.status_pago = 0 
                ORDER BY t.data_movimentacao DESC";
        $pendentes = $this->pdo->query($sql)->fetchAll();

        require_once '../app/Views/recebimentos.php';
    }

    public function baixar() {
        if (isset($_GET['id'])) {
            $model = new Transacao($this->pdo);
            $model->confirmarPagamentoAmigo($_GET['id']);
        }
        header('Location: /financeiro/public/index.php/recebimentos?sucesso=1');
        exit;
    }
}