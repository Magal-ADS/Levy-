<?php
// app/Controllers/RecebimentoController.php
require_once __DIR__ . '/../Models/Transacao.php';

class RecebimentoController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        // Pega o mês da URL ou usa o mês atual como padrão
        $mesReferencia = $_GET['mes'] ?? date('Y-m');

        // Busca as divisões não pagas FILTRADAS PELO MÊS
        $sql = "SELECT dt.id as divisao_id, p.id as pessoa_id, p.nome as amigo_nome, 
                       t.descricao, dt.valor_divisao, t.data_movimentacao, t.mes_referencia
                FROM divisoes_transacao dt 
                JOIN transacoes t ON dt.transacao_id = t.id 
                JOIN pessoas p ON dt.pessoa_id = p.id 
                WHERE dt.status_pago = 0 
                  AND dt.pessoa_id IS NOT NULL 
                  AND t.mes_referencia = :mes
                ORDER BY p.nome ASC, t.data_movimentacao DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['mes' => $mesReferencia]);
        $resultados = $stmt->fetchAll();

        // Lógica de Agrupamento
        $pessoasAgrupadas = [];
        $totalGeral = 0;

        foreach ($resultados as $row) {
            $pId = $row['pessoa_id'];
            
            if (!isset($pessoasAgrupadas[$pId])) {
                $pessoasAgrupadas[$pId] = [
                    'nome' => $row['amigo_nome'],
                    'total_devido' => 0,
                    'itens' => []
                ];
            }
            
            $pessoasAgrupadas[$pId]['total_devido'] += $row['valor_divisao'];
            $totalGeral += $row['valor_divisao'];
            $pessoasAgrupadas[$pId]['itens'][] = $row;
        }

        require_once '../app/Views/recebimentos.php';
    }

    public function baixar() {
        $model = new Transacao($this->pdo);
        $mes = $_GET['mes'] ?? date('Y-m'); // Mantém o mês para o redirecionamento

        if (isset($_GET['id'])) {
            // Baixa unitária (uma conta só)
            $model->confirmarPagamentoAmigo($_GET['id']);
        } elseif (isset($_GET['pessoa_id'])) {
            // Baixa em lote (todas as contas da pessoa NO MÊS SELECIONADO)
            $pessoaId = $_GET['pessoa_id'];
            
            $stmt = $this->pdo->prepare("
                SELECT dt.id 
                FROM divisoes_transacao dt
                JOIN transacoes t ON dt.transacao_id = t.id
                WHERE dt.pessoa_id = ? AND dt.status_pago = 0 AND t.mes_referencia = ?
            ");
            $stmt->execute([$pessoaId, $mes]);
            $divisoesPendentes = $stmt->fetchAll();

            foreach ($divisoesPendentes as $div) {
                $model->confirmarPagamentoAmigo($div['id']);
            }
        }

        // Redireciona de volta para a tela mantendo o mês filtrado
        header("Location: /financeiro/public/index.php/recebimentos?mes={$mes}&sucesso=1");
        exit;
    }
}