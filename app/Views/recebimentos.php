<?php require_once __DIR__ . '/partials/header.php'; ?>

<div class="max-w-5xl mx-auto">
    <h2 class="text-2xl font-bold text-slate-800 mb-6">Dívidas de Amigos (A Receber)</h2>

    <?php if (isset($_GET['sucesso'])): ?>
        <div class="bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded mb-6 shadow-sm">
            Pagamento confirmado! O valor foi adicionado ao seu saldo como entrada.
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Amigo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Descrição da Conta</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Valor</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">Ação</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($pendentes)): ?>
                    <tr><td colspan="4" class="px-6 py-10 text-center text-slate-500">Ninguém te deve nada no momento! 🙌</td></tr>
                <?php else: ?>
                    <?php foreach ($pendentes as $item): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-bold text-indigo-600"><?= $item['nome_pessoa'] ?></td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                <?= $item['descricao'] ?> <br>
                                <span class="text-xs text-slate-400"><?= date('d/m/Y', strtotime($item['data_movimentacao'])) ?></span>
                            </td>
                            <td class="px-6 py-4 font-bold text-slate-800">R$ <?= number_format($item['valor_divisao'], 2, ',', '.') ?></td>
                            <td class="px-6 py-4 text-right">
                                <a href="/financeiro/public/index.php/baixar-recebimento?id=<?= $item['divisao_id'] ?>" 
                                   class="bg-emerald-500 hover:bg-emerald-600 text-white px-3 py-1 rounded text-xs font-bold transition-colors">
                                    Confirmar Pagamento
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>