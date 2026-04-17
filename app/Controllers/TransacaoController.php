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

    private function normalizarDivisoes(array $divisoes, $valorPadrao = 0, $usarCampoValorParcela = false) {
        if (empty($divisoes)) {
            return [[
                'pessoa_id' => null,
                'valor_divisao' => (float) $valorPadrao,
                'status_pago' => 1
            ]];
        }

        $divisoesNormalizadas = [];

        foreach ($divisoes as $divisao) {
            $campoValor = $usarCampoValorParcela ? ($divisao['valor'] ?? 0) : ($divisao['valor_divisao'] ?? 0);
            $valor = $this->limparMoeda($campoValor);

            $divisoesNormalizadas[] = [
                'pessoa_id' => !empty($divisao['pessoa_id']) ? $divisao['pessoa_id'] : null,
                'valor_divisao' => $valor,
                'status_pago' => isset($divisao['status_pago']) ? (int) $divisao['status_pago'] : (!empty($divisao['pessoa_id']) ? 0 : 1)
            ];
        }

        $possuiValorPositivo = false;
        foreach ($divisoesNormalizadas as $divisao) {
            if ($divisao['valor_divisao'] > 0) {
                $possuiValorPositivo = true;
                break;
            }
        }

        if (!$possuiValorPositivo) {
            return [[
                'pessoa_id' => null,
                'valor_divisao' => (float) $valorPadrao,
                'status_pago' => 1
            ]];
        }

        return $divisoesNormalizadas;
    }

    private function inserirTransacao(array $dadosTransacao, int $usuarioId = 0) {
        $sql = "INSERT INTO transacoes
            (usuario_id, descricao, valor_total, tipo, data_movimentacao, mes_referencia, categoria_id, cartao_id, hash_parcelamento)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            RETURNING id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $usuarioId,
            $dadosTransacao['descricao'],
            $dadosTransacao['valor_total'],
            $dadosTransacao['tipo'],
            $dadosTransacao['data_movimentacao'],
            $dadosTransacao['mes_referencia'],
            $dadosTransacao['categoria_id'] ?? null,
            $dadosTransacao['cartao_id'] ?? null,
            $dadosTransacao['hash_parcelamento'] ?? null
        ]);

        return $stmt->fetchColumn();
    }

    private function inserirDivisoes($transacaoId, array $divisoes) {
        // Inserir também status_aceite: 'aceito' para sua parte ou pessoa local; 'pendente' se pessoa vinculada a usuário
        $sqlDiv = "INSERT INTO divisoes_transacao (transacao_id, pessoa_id, valor_divisao, status_pago, status_aceite) VALUES (?, ?, ?, ?, ?)";
        $stmtDiv = $this->pdo->prepare($sqlDiv);
        $stmtPessoa = $this->pdo->prepare("SELECT vinculo_usuario_id FROM pessoas WHERE id = ?");

        foreach ($divisoes as $divisao) {
            if (($divisao['valor_divisao'] ?? 0) <= 0) {
                continue;
            }

            $pessoaId = $divisao['pessoa_id'] ?? null;
            $statusAceite = 'aceito';

            if (!empty($pessoaId)) {
                $stmtPessoa->execute([$pessoaId]);
                $p = $stmtPessoa->fetch();
                if ($p && !empty($p['vinculo_usuario_id'])) {
                    $statusAceite = 'pendente';
                } else {
                    $statusAceite = 'aceito';
                }
            }

            $stmtDiv->execute([
                $transacaoId,
                $pessoaId,
                $divisao['valor_divisao'],
                $divisao['status_pago'],
                $statusAceite
            ]);
        }
    }

    private function somarDivisoes(array $divisoes) {
        $total = 0;
        foreach ($divisoes as $divisao) {
            $total += (float) ($divisao['valor_divisao'] ?? 0);
        }
        return round($total, 2);
    }

    private function adicionarMesReferencia($mesReferencia, $mesesParaAdicionar) {
        $data = DateTime::createFromFormat('!Y-m', $mesReferencia);
        if (!$data) {
            throw new Exception('MÃªs de referÃªncia invÃ¡lido para parcelamento.');
        }

        if ($mesesParaAdicionar > 0) {
            $data->modify('+' . $mesesParaAdicionar . ' month');
        }

        return $data->format('Y-m');
    }

    /**
     * TELA PRINCIPAL DE TRANSAÇÕES (Com Gráfico e Filtros)
     */
    public function index() {
        $mesReferencia = $_GET['mes'] ?? date('Y-m');
        $busca = isset($_GET['q']) ? trim($_GET['q']) : '';

        // 1. DADOS PARA A LISTAGEM (Tabela)
        // Substituído GROUP_CONCAT por STRING_AGG para compatibilidade com PostgreSQL
        // Usar o model Transacao para respeitar regras de Shared Ledger
        $usuarioId = $_SESSION['usuario_id'] ?? 0;
        $model = new Transacao($this->pdo);
        $pessoaFilter = isset($_GET['pessoa_id']) && is_numeric($_GET['pessoa_id']) ? $_GET['pessoa_id'] : null;
        $transacoes = $model->buscarPorMes($usuarioId, $mesReferencia, $busca, $pessoaFilter);

        // 2. DADOS PARA O GRÁFICO (Soma das SUAS despesas por categoria)
        $sqlGrafico = "
            SELECT c.nome as categoria, SUM(dt.valor_divisao) as total 
            FROM divisoes_transacao dt 
            JOIN transacoes t ON dt.transacao_id = t.id 
            JOIN categorias c ON t.categoria_id = c.id 
            WHERE t.mes_referencia = ? 
            AND dt.pessoa_id IS NULL 
            AND t.tipo = 'despesa'
            AND (dt.status_aceite IS NULL OR dt.status_aceite = 'aceito')
            AND t.usuario_id = ?
            GROUP BY c.nome 
            ORDER BY total DESC";
        
        $stmtG = $this->pdo->prepare($sqlGrafico);
        $stmtG->execute([$mesReferencia, $usuarioId]);
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
        $usuarioId = $_SESSION['usuario_id'] ?? 0;
        $stmtP = $this->pdo->prepare("SELECT id, nome FROM pessoas WHERE usuario_id = ? ORDER BY nome ASC");
        $stmtP->execute([$usuarioId]);
        $pessoas = $stmtP->fetchAll();

        $stmtC = $this->pdo->prepare("SELECT id, nome FROM categorias WHERE usuario_id = ? ORDER BY nome ASC");
        $stmtC->execute([$usuarioId]);
        $categorias = $stmtC->fetchAll();

        $stmtCr = $this->pdo->prepare("SELECT id, nome FROM cartoes WHERE usuario_id = ? ORDER BY nome ASC");
        $stmtCr->execute([$usuarioId]);
        $cartoes = $stmtCr->fetchAll();

        require_once '../app/Views/nova-transacao.php';
    }

    public function salvar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $mesReferencia = $_POST['mes_referencia'];

            if (!in_array($_POST['tipo'] ?? '', ['despesa', 'receita'], true)) {
                throw new Exception('Tipo de transação inválido.');
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['data_movimentacao'] ?? '')) {
                throw new Exception('Data de movimentação inválida.');
            }

            if (!preg_match('/^\d{4}-\d{2}$/', $mesReferencia ?? '')) {
                throw new Exception('Mês de referência inválido.');
            }

            $dadosTransacao = [
                'descricao'         => $_POST['descricao'],
                'valor_total'       => $this->limparMoeda($_POST['valor_total']), 
                'tipo'              => $_POST['tipo'],
                'data_movimentacao' => $_POST['data_movimentacao'],
                'mes_referencia'    => $mesReferencia, 
                'categoria_id'      => !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null,
                'cartao_id'         => !empty($_POST['cartao_id']) ? $_POST['cartao_id'] : null
            ];

            try {
                $this->pdo->beginTransaction();

                $isParcelado = !empty($_POST['compra_parcelada']);

                if ($isParcelado) {
                    $qtdParcelas = max(2, (int) ($_POST['qtd_parcelas'] ?? 0));
                    $mesPrimeiraParcela = $_POST['mes_primeira_parcela'] ?? '';
                    $parcelas = $_POST['parcelas'] ?? [];
                    $hash = uniqid('parc_');

                    if (empty($mesPrimeiraParcela)) {
                        throw new Exception('Informe o mÃªs da 1Âª parcela.');
                    }

                    if (count($parcelas) !== $qtdParcelas) {
                        throw new Exception('A estrutura das parcelas estÃ¡ incompleta. Gere novamente as parcelas antes de salvar.');
                    }

                    foreach ($parcelas as $indice => $parcela) {
                        $numeroParcela = $indice + 1;
                        $valorParcela = $this->limparMoeda($parcela['valor_total'] ?? 0);
                        $divisoesParcela = $this->normalizarDivisoes($parcela['divisoes'] ?? [], $valorParcela, true);
                        $somaDivisoes = $this->somarDivisoes($divisoesParcela);

                        if (abs($somaDivisoes - round($valorParcela, 2)) > 0.01) {
                            throw new Exception("A soma das divisÃµes da parcela {$numeroParcela} nÃ£o bate com o valor da parcela.");
                        }

                        $mesParcela = $this->adicionarMesReferencia($mesPrimeiraParcela, $indice);
                        $descricaoParcela = $dadosTransacao['descricao'] . " ({$numeroParcela}/{$qtdParcelas})";

                        $transacaoId = $this->inserirTransacao([
                            'descricao' => $descricaoParcela,
                            'valor_total' => $valorParcela,
                            'tipo' => $dadosTransacao['tipo'],
                            'data_movimentacao' => $dadosTransacao['data_movimentacao'],
                            'mes_referencia' => $mesParcela,
                            'categoria_id' => $dadosTransacao['categoria_id'],
                            'cartao_id' => $dadosTransacao['cartao_id'],
                            'hash_parcelamento' => $hash
                        ], $usuarioId);

                        $this->inserirDivisoes($transacaoId, $divisoesParcela);
                    }
                } else {
                    $divisoes = $this->normalizarDivisoes($_POST['divisoes'] ?? [], $dadosTransacao['valor_total']);
                    $somaDivisoes = $this->somarDivisoes($divisoes);

                    if (abs($somaDivisoes - round($dadosTransacao['valor_total'], 2)) > 0.01) {
                        throw new Exception('A soma das divisÃµes precisa ser igual ao valor total da transaÃ§Ã£o.');
                    }

                    $transacaoId = $this->inserirTransacao($dadosTransacao + ['hash_parcelamento' => null], $usuarioId);
                    $this->inserirDivisoes($transacaoId, $divisoes);
                }

                $this->pdo->commit();
                header("Location: /financeiro/public/index.php?mes={$mesReferencia}&sucesso=1");
                exit;
            } catch (Exception $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                echo "Erro ao salvar: " . $e->getMessage();
            }
        }
    }

    // --- MÉTODOS DE EDIÇÃO ---

    public function editar() {
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: /financeiro/public/index.php'); exit; }

        $usuarioId = $_SESSION['usuario_id'] ?? 0;
        $stmt = $this->pdo->prepare("SELECT * FROM transacoes WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$id, $usuarioId]);
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
            if (empty($divisoes) || count($divisoes) === 0) {
                $divisoes = [
                    0 => [
                        'pessoa_id' => null,
                        'valor_divisao' => $dadosTransacao['valor_total'],
                        'status_pago' => 0
                    ]
                ];
            }
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
                // Reutiliza lógica de inserção que já define status_aceite corretamente
                $this->inserirDivisoes($id, $divisoes);
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
            $usuarioId = $_SESSION['usuario_id'] ?? 0;
            $this->pdo->prepare("DELETE FROM divisoes_transacao WHERE transacao_id = ?")->execute([$id]);
            $stmtDel = $this->pdo->prepare("DELETE FROM transacoes WHERE id = ? AND usuario_id = ?");
            $stmtDel->execute([$id, $usuarioId]);
        }
        $redirect = '/financeiro/public/index.php?sucesso=1';

        if (!empty($_SERVER['HTTP_REFERER'])) {
            $path = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH) ?? '';
            $query = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY) ?? '';

            if (str_starts_with($path, '/financeiro/public/index.php')) {
                $redirect = $path . ($query !== '' ? '?' . $query : '');
            }
        }

        header('Location: ' . $redirect);
        exit;
    }
}
