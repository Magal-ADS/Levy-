<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Levy Finance - Relatório de <?= htmlspecialchars($pessoa['nome']) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 32px; background: #f8fafc; color: #0f172a; font-family: DejaVu Sans, Arial, sans-serif; }
        .page { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 32px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #e2e8f0; padding-bottom: 18px; margin-bottom: 24px; }
        .brand { font-size: 28px; font-weight: 700; letter-spacing: -0.02em; }
        .brand span { color: #10b981; }
        .subtitle { margin-top: 6px; font-size: 13px; color: #64748b; }
        .meta { text-align: right; font-size: 12px; color: #475569; line-height: 1.6; }
        .summary { display: table; width: 100%; margin-bottom: 22px; }
        .summary-box { display: table-cell; width: 50%; background: #f8fafc; border: 1px solid #e2e8f0; padding: 14px 16px; }
        .summary-box + .summary-box { border-left: none; }
        .summary-label { font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; }
        .summary-value { margin-top: 8px; font-size: 24px; font-weight: 700; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: #f8fafc; color: #64748b; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; padding: 12px; border-bottom: 1px solid #cbd5e1; }
        tbody td { padding: 12px; font-size: 13px; border-bottom: 1px solid #e2e8f0; }
        .text-right { text-align: right; }
        .footer-total { margin-top: 22px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 14px; padding: 18px; }
        .footer-total .label { font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #047857; }
        .footer-total .value { margin-top: 8px; font-size: 28px; font-weight: 700; color: #065f46; }
        .print-hint { margin-top: 18px; font-size: 12px; color: #64748b; }
        @media print {
            body { padding: 0; background: #ffffff; }
            .page { border: none; border-radius: 0; padding: 0; }
            .print-hint { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="page">
        <div class="header">
            <div>
                <div class="brand">Levy <span>Finance</span></div>
                <div class="subtitle">Relatório detalhado por pessoa</div>
            </div>
            <div class="meta">
                <div><strong>Pessoa:</strong> <?= htmlspecialchars($pessoa['nome']) ?></div>
                <div><strong>Mês:</strong> <?= htmlspecialchars($nomeMesAno) ?></div>
                <div><strong>Emitido em:</strong> <?= date('d/m/Y H:i') ?></div>
            </div>
        </div>

        <div class="summary">
            <div class="summary-box">
                <div class="summary-label">Pessoa</div>
                <div class="summary-value"><?= htmlspecialchars($pessoa['nome']) ?></div>
            </div>
            <div class="summary-box">
                <div class="summary-label">Total geral a pagar</div>
                <div class="summary-value">R$ <?= number_format((float) $totalGeral, 2, ',', '.') ?></div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição da compra</th>
                    <th class="text-right">Valor total da nota</th>
                    <th class="text-right">Valor da parte dela</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($itens)): ?>
                    <tr>
                        <td colspan="4" style="padding:24px; text-align:center; color:#64748b;">Nenhum lançamento encontrado para o período selecionado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($itens as $item): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($item['data_movimentacao'])) ?></td>
                            <td><?= htmlspecialchars($item['descricao']) ?></td>
                            <td class="text-right">R$ <?= number_format((float) $item['valor_total'], 2, ',', '.') ?></td>
                            <td class="text-right">R$ <?= number_format((float) $item['valor_divisao'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="footer-total">
            <div class="label">Total geral a pagar no mês</div>
            <div class="value">R$ <?= number_format((float) $totalGeral, 2, ',', '.') ?></div>
        </div>

        <div class="print-hint">Se o download em PDF não ocorrer automaticamente, use a opção "Salvar como PDF" na janela de impressão.</div>
    </div>
</body>
</html>
