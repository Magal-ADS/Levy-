<?php
// app/Controllers/RecebimentoController.php
require_once __DIR__ . '/../Models/Transacao.php';

class RecebimentoController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        $mesReferencia = $_GET['mes'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $mesReferencia)) {
            $mesReferencia = date('Y-m');
        }

        $usuarioId = $_SESSION['usuario_id'] ?? 0;

        $sql = "SELECT dt.id as divisao_id, p.id as pessoa_id, p.nome as amigo_nome,
                       t.descricao, dt.valor_divisao, t.data_movimentacao, t.mes_referencia
                FROM divisoes_transacao dt
                JOIN transacoes t ON dt.transacao_id = t.id
                JOIN pessoas p ON dt.pessoa_id = p.id
                WHERE dt.status_pago = 0
                  AND dt.pessoa_id IS NOT NULL
                  AND t.mes_referencia = :mes
                  AND (t.usuario_id = :user_id_1 OR (p.vinculo_usuario_id = :user_id_2 AND dt.status_aceite = 'aceito'))
                ORDER BY p.nome ASC, t.data_movimentacao DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['mes' => $mesReferencia, 'user_id_1' => $usuarioId, 'user_id_2' => $usuarioId]);
        $resultados = $stmt->fetchAll();

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

            $pessoasAgrupadas[$pId]['total_devido'] += (float) $row['valor_divisao'];
            $totalGeral += (float) $row['valor_divisao'];
            $pessoasAgrupadas[$pId]['itens'][] = $row;
        }

        // Buscar minhas despesas (parte do usuário principal) para auditoria
        $sqlMinhas = "SELECT dt.id as divisao_id, t.id as transacao_id, t.descricao, dt.valor_divisao, t.data_movimentacao, t.mes_referencia
                      FROM divisoes_transacao dt
                      INNER JOIN transacoes t ON t.id = dt.transacao_id
                      WHERE dt.pessoa_id IS NULL
                        AND t.mes_referencia = :mes
                      ORDER BY t.data_movimentacao DESC";
        $stmtMinhas = $this->pdo->prepare($sqlMinhas);
        $stmtMinhas->execute(['mes' => $mesReferencia]);
        $itensMinhas = $stmtMinhas->fetchAll();

        $totalMinhas = 0;
        foreach ($itensMinhas as $it) {
            $totalMinhas += (float) $it['valor_divisao'];
        }

        $minhasDespesas = [
            'total' => $totalMinhas,
            'itens' => $itensMinhas
        ];

        require_once '../app/Views/recebimentos.php';
    }

    public function gerarPdfPessoa($pessoaId = null, $mes = null) {
        $pessoaId = $pessoaId ?? ($_GET['pessoa_id'] ?? null);
        $mesReferencia = $mes ?? ($_GET['mes'] ?? date('Y-m'));

        if (!$pessoaId || !preg_match('/^\d+$/', (string) $pessoaId)) {
            header('Location: /financeiro/public/index.php/recebimentos?erro=relatorio');
            exit;
        }

        if (!preg_match('/^\d{4}-\d{2}$/', $mesReferencia)) {
            $mesReferencia = date('Y-m');
        }

        $usuarioId = $_SESSION['usuario_id'] ?? 0;
        $dadosRelatorio = $this->buscarRelatorioPessoa((int) $pessoaId, $mesReferencia);
        if (!$dadosRelatorio['pessoa']) {
            header('Location: /financeiro/public/index.php/recebimentos?mes=' . urlencode($mesReferencia) . '&erro=relatorio');
            exit;
        }

        // Segurança: se essa pessoa estiver vinculada a um usuário diferente do atual, negar acesso
        if (!empty($dadosRelatorio['pessoa']['vinculo_usuario_id']) && (int)$dadosRelatorio['pessoa']['vinculo_usuario_id'] !== (int)$usuarioId) {
            header('Location: /financeiro/public/index.php/recebimentos?mes=' . urlencode($mesReferencia) . '&erro=permissao');
            exit;
        }

        $html = $this->renderizarHtmlRelatorioPessoa($dadosRelatorio, $mesReferencia);

        if (file_exists('../vendor/autoload.php')) {
            require_once '../vendor/autoload.php';
        }

        if (class_exists('\Dompdf\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf([
                'isRemoteEnabled' => true,
                'defaultPaperSize' => 'a4'
            ]);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream(
                'levy-finance-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($dadosRelatorio['pessoa']['nome'])) . '-' . $mesReferencia . '.pdf',
                ['Attachment' => true]
            );
            exit;
        }

        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        exit;
    }

    public function baixar() {
        $model = new Transacao($this->pdo);
        $mes = $_GET['mes'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $mes = date('Y-m');
        }

        if (isset($_GET['id'])) {
            $model->confirmarPagamentoAmigo($_GET['id']);
        } elseif (isset($_GET['pessoa_id'])) {
            $pessoaId = $_GET['pessoa_id'];

            $usuarioId = $_SESSION['usuario_id'] ?? 0;
            $stmt = $this->pdo->prepare("
                SELECT dt.id
                FROM divisoes_transacao dt
                JOIN transacoes t ON dt.transacao_id = t.id
                WHERE dt.pessoa_id = ? AND dt.status_pago = 0 AND t.mes_referencia = ? AND t.usuario_id = ?
            ");
            $stmt->execute([$pessoaId, $mes, $usuarioId]);
            $divisoesPendentes = $stmt->fetchAll();

            foreach ($divisoesPendentes as $div) {
                $model->confirmarPagamentoAmigo($div['id']);
            }
        }

        header("Location: /financeiro/public/index.php/recebimentos?mes={$mes}&sucesso=1");
        exit;
    }

    private function buscarRelatorioPessoa(int $pessoaId, string $mesReferencia): array {
        $stmtPessoa = $this->pdo->prepare("SELECT id, nome, vinculo_usuario_id FROM pessoas WHERE id = ?");
        $stmtPessoa->execute([$pessoaId]);
        $pessoa = $stmtPessoa->fetch();

        // Se a pessoa estiver vinculada a um usuário, só incluir divisões aceitas
        $sql = "SELECT
                    t.data_movimentacao,
                    t.descricao,
                    t.valor_total,
                    dt.valor_divisao,
                    t.mes_referencia
                FROM divisoes_transacao dt
                JOIN transacoes t ON t.id = dt.transacao_id
                WHERE dt.pessoa_id = ?
                  AND t.mes_referencia = ?";

        $params = [$pessoaId, $mesReferencia];
        if (!empty($pessoa['vinculo_usuario_id'])) {
            $sql .= " AND dt.status_aceite = 'aceito'";
        }

        $sql .= " ORDER BY t.data_movimentacao ASC, t.id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $itens = $stmt->fetchAll();

        $totalGeral = 0;
        foreach ($itens as $item) {
            $totalGeral += (float) $item['valor_divisao'];
        }

        return [
            'pessoa' => $pessoa,
            'itens' => $itens,
            'total_geral' => $totalGeral,
        ];
    }

    private function renderizarHtmlRelatorioPessoa(array $dadosRelatorio, string $mesReferencia): string {
        $mesesBr = [
            '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
            '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
            '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
        ];

        $partesData = explode('-', $mesReferencia);
        $nomeMesAno = $mesesBr[$partesData[1]] . ' de ' . $partesData[0];

        $pessoa = $dadosRelatorio['pessoa'];
        $itens = $dadosRelatorio['itens'];
        $totalGeral = $dadosRelatorio['total_geral'];

        ob_start();
        require '../app/Views/relatorio-pessoa-pdf.php';
        return ob_get_clean();
    }
}
