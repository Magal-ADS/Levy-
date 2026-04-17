<?php require_once __DIR__ . '/partials/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Contas Encaminhadas</h2>
    </div>

    <?php if (empty($solicitacoes)): ?>
        <div class="bg-white rounded-lg shadow p-6 text-center text-slate-500">Nenhuma solicitação pendente.</div>
    <?php else: ?>
        <div class="grid grid-cols-1 gap-4">
            <?php foreach($solicitacoes as $s): ?>
                <div class="bg-white rounded-lg shadow p-4 flex justify-between items-center">
                    <div>
                        <div class="text-sm text-slate-600">Enviado por: <strong><?= htmlspecialchars($s['remetente_usuario_nome'] ?? $s['remetente_nome']) ?></strong></div>
                        <div class="font-bold text-lg text-slate-800 mt-1"><?= htmlspecialchars($s['descricao']) ?></div>
                        <div class="text-sm text-slate-500">Valor da sua parte: R$ <?= number_format($s['valor_divisao'], 2, ',', '.') ?> • <?= date('d/m/Y', strtotime($s['data_movimentacao'])) ?></div>
                    </div>
                    <div class="flex gap-2">
                        <a href="/financeiro/public/index.php/solicitacoes/aceitar?id=<?= $s['divisao_id'] ?>" class="bg-emerald-500 hover:bg-emerald-600 text-white px-3 py-2 rounded">Aceitar</a>
                        <a href="/financeiro/public/index.php/solicitacoes/recusar?id=<?= $s['divisao_id'] ?>" class="bg-rose-400 hover:bg-rose-500 text-white px-3 py-2 rounded">Recusar</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
