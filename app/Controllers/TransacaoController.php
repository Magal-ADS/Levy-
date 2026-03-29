<?php
// app/Controllers/TransacaoController.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/Transacao.php';

class TransacaoController {
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
     * TELA PRINCIPAL DE TRANSAÇÕES (Com Gráfico e Filtros)
     */
    public function index() {
        $mesReferencia = $_GET['mes'] ?? date('Y-m');
        $busca = isset($_GET['q']) ? trim($_GET['q']) : '';

        // 1. DADOS PARA A LISTAGEM (Tabela)
        $sqlListagem = "
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
            $sqlListagem .= " AND (
                t.descricao LIKE :b1 
                OR c.nome LIKE :b2 
                OR cr.nome LIKE :b3 
                OR EXISTS (
                    SELECT 1 FROM divisoes_transacao dt3 
                    JOIN pessoas p2 ON dt3.pessoa_id = p2.id 
                    WHERE dt3.transacao_id = t.id AND p2.nome LIKE :b4
                )
            )";
            $termo = "%$busca%";
            $params[':b1'] = $termo; $params[':b2'] = $termo; 
            $params[':b3'] = $termo; $params[':b4'] = $termo;
        }

        $sqlListagem .= " ORDER BY t.data_movimentacao DESC";
        $stmtLista = $this->pdo->prepare($sqlListagem);
        $stmtLista->execute($params);
        $transacoes = $stmtLista->fetchAll();

        // 2. DADOS PARA O GRÁFICO (Soma das SUAS despesas por categoria)
        $sqlGrafico = "
            SELECT c.nome as categoria, SUM(dt.valor_divisao) as total 
            FROM divisoes_transacao dt 
            JOIN transacoes t ON dt.transacao_id = t.id 
            JOIN categorias c ON t.categoria_id = c.id 
            WHERE t.mes_referencia = ? 
            AND dt.pessoa_id IS NULL 
            AND t.tipo = 'despesa'
            GROUP BY c.nome 
            ORDER BY total DESC";
        
        $stmtG = $this->pdo->prepare($sqlGrafico);
        $stmtG->execute([$mesReferencia]);
        $dadosGrafico = $stmtG->fetchAll();

        // Variável para o título em português
        $mesesBr = [
            '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
            '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
            '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
        ];
        $partes = explode('-', $mesReferencia);
        $nomeMesAno = $mesesBr[$partes[1]] . " de " . $partes[0];

        require_once '../app/Views/transacoes.php';
    }

    // --- MÉTODOS DE CRIAÇÃO ---

    public function nova() {
        $pessoas = $this->pdo->query("SELECT id, nome FROM pessoas ORDER BY nome ASC")->fetchAll();
        $categorias = $this->pdo->query("SELECT id, nome FROM categorias ORDER BY nome ASC")->fetchAll();
        $cartoes = $this->pdo->query("SELECT id, nome FROM cartoes ORDER BY nome ASC")->fetchAll();

        require_once '../app/Views/nova-transacao.php';
    }

    public function salvar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $mesReferencia = $_POST['mes_referencia'];
            $dadosTransacao = [
                'descricao'         => $_POST['descricao'],
                'valor_total'       => $this->limparMoeda($_POST['valor_total']), 
                'tipo'              => $_POST['tipo'],
                'data_movimentacao' => $_POST['data_movimentacao'],
                'mes_referencia'    => $mesReferencia, 
                'categoria_id'      => !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null,
                'cartao_id'         => !empty($_POST['cartao_id']) ? $_POST['cartao_id'] : null
            ];

            $divisoes = isset($_POST['divisoes']) ? $_POST['divisoes'] : [];
            foreach ($divisoes as $key => $divisao) {
                $divisoes[$key]['valor_divisao'] = $this->limparMoeda($divisao['valor_divisao']);
            }

            $transacaoModel = new Transacao($this->pdo);

            try {
                $transacaoModel->salvarTransacaoComDivisao($dadosTransacao, $divisoes);
                header("Location: /financeiro/public/index.php?mes={$mesReferencia}&sucesso=1");
                exit;
            } catch (Exception $e) {
                echo "Erro ao salvar: " . $e->getMessage();
            }
        }
    }

    // --- MÉTODOS DE EDIÇÃO ---

    public function editar() {
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: /financeiro/public/index.php'); exit; }

        $stmt = $this->pdo->prepare("SELECT * FROM transacoes WHERE id = ?");
        $stmt->execute([$id]);
        $transacao = $stmt->fetch();

        $stmtDiv = $this->pdo->prepare("SELECT * FROM divisoes_transacao WHERE transacao_id = ?");
        $stmtDiv->execute([$id]);
        $divisoesAtuais = $stmtDiv->fetchAll();

        $pessoas = $this->pdo->query("SELECT id, nome FROM pessoas ORDER BY nome ASC")->fetchAll();
        $categorias = $this->pdo->query("SELECT id, nome FROM categorias ORDER BY nome ASC")->fetchAll();
        $cartoes = $this->pdo->query("SELECT id, nome FROM cartoes ORDER BY nome ASC")->fetchAll();

        require_once '../app/Views/editar-transacao.php';
    }

    public function atualizar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $mesReferencia = $_POST['mes_referencia'];
            
            $dadosTransacao = [
                'descricao'         => $_POST['descricao'],
                'valor_total'       => $this->limparMoeda($_POST['valor_total']), 
                'tipo'              => $_POST['tipo'],
                'data_movimentacao' => $_POST['data_movimentacao'],
                'mes_referencia'    => $mesReferencia, 
                'categoria_id'      => !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null,
                'cartao_id'         => !empty($_POST['cartao_id']) ? $_POST['cartao_id'] : null
            ];

            $divisoes = isset($_POST['divisoes']) ? $_POST['divisoes'] : [];
            foreach ($divisoes as $key => $divisao) {
                $divisoes[$key]['valor_divisao'] = $this->limparMoeda($divisao['valor_divisao']);
            }

            try {
                $this->pdo->beginTransaction();
                $sql = "UPDATE transacoes SET descricao = ?, valor_total = ?, tipo = ?, data_movimentacao = ?, mes_referencia = ?, categoria_id = ?, cartao_id = ? WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    $dadosTransacao['descricao'], $dadosTransacao['valor_total'], $dadosTransacao['tipo'],
                    $dadosTransacao['data_movimentacao'], $dadosTransacao['mes_referencia'],
                    $dadosTransacao['categoria_id'], $dadosTransacao['cartao_id'], $id
                ]);

                $this->pdo->prepare("DELETE FROM divisoes_transacao WHERE transacao_id = ?")->execute([$id]);
                $sqlDiv = "INSERT INTO divisoes_transacao (transacao_id, pessoa_id, valor_divisao, status_pago) VALUES (?, ?, ?, ?)";
                $stmtDiv = $this->pdo->prepare($sqlDiv);
                foreach ($divisoes as $div) {
                    $stmtDiv->execute([
                        $id, !empty($div['pessoa_id']) ? $div['pessoa_id'] : null,
                        $div['valor_divisao'], $div['status_pago']
                    ]);
                }
                $this->pdo->commit();
                header("Location: /financeiro/public/index.php?mes={$mesReferencia}&sucesso=1");
                exit;
            } catch (Exception $e) {
                $this->pdo->rollBack();
                echo "Erro ao atualizar: " . $e->getMessage();
            }
        }
    }

    // --- MÉTODO DE EXCLUSÃO ---

    public function deletar() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $this->pdo->prepare("DELETE FROM divisoes_transacao WHERE transacao_id = ?")->execute([$id]);
            $this->pdo->prepare("DELETE FROM transacoes WHERE id = ?")->execute([$id]);
        }
        $redirect = $_SERVER['HTTP_REFERER'] ?? '/financeiro/public/index.php?sucesso=1';
        header("Location: $redirect");
        exit;
    }
}