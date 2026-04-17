<?php require_once __DIR__ . '/partials/header.php'; ?>

<div class="max-w-7xl mx-auto w-full">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-700">Relatório de Transações</h2>
            <p class="text-sm text-slate-500 italic"><?= $nomeMesAno ?></p>
        </div>
        
        <form method="GET" class="flex gap-2">
            <input type="month" name="mes" value="<?= $mesReferencia ?>" 
                   onchange="this.form.submit()" 
                   class="border-gray-300 rounded-md shadow-sm p-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-md col-span-1 border border-slate-100 flex flex-col items-center">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6">Gastos por Categoria (Minha Parte)</h3>
            
            <?php if(empty($dadosGrafico)): ?>
                <div class="flex-1 flex flex-col items-center justify-center text-slate-300 py-10">
                    <svg class="w-16 h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    <p class="text-sm italic">Sem dados para o gráfico</p>
                </div>
            <?php else: ?>
                <div class="relative w-full" style="max-width: 280px;">
                    <canvas id="meuGrafico"></canvas>
                </div>
            <?php endif; ?>
        </div>

        <div class="lg:col-span-2 bg-white rounded-lg shadow-md overflow-hidden border border-slate-100">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <h3 class="font-bold text-slate-700 text-sm uppercase">Detalhamento do Mês</h3>
                <span class="text-[10px] bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-bold">
                    <?= count($transacoes) ?> LANÇAMENTOS
                </span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase">Data</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase">Descrição</th>
                            <th class="px-4 py-3 text-right text-[10px] font-bold text-slate-500 uppercase">Valor Total</th>
                            <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <?php foreach($transacoes as $tr): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-4 text-slate-500"><?= date('d/m', strtotime($tr['data_movimentacao'])) ?></td>
                                <td class="px-4 py-4">
                                    <div class="font-bold text-slate-800"><?= htmlspecialchars($tr['display_descricao'] ?? $tr['descricao']) ?></div>
                                    <div class="text-[9px] text-slate-400 uppercase"><?= $tr['categoria_nome'] ?> • <?= $tr['cartao_nome'] ?? 'PIX/DINHEIRO' ?></div>
                                </td>
                                <td class="px-4 py-4 text-right font-black <?= ($tr['display_tipo'] ?? $tr['tipo']) == 'despesa' ? 'text-rose-500' : 'text-emerald-500' ?>">
                                    R$ <?= number_format(($tr['display_valor'] ?? $tr['valor_total']), 2, ',', '.') ?>
                                </td>
                                <td class="px-4 py-4 text-center space-x-2">
                                    <a href="/financeiro/public/index.php/editar-transacao?id=<?= $tr['id'] ?>" class="text-indigo-400 hover:text-indigo-600"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg></a>
                                    <a href="/financeiro/public/index.php/deletar-transacao?id=<?= $tr['id'] ?>" onclick="return confirm('Apagar?')" class="text-rose-300 hover:text-rose-500"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    <?php if(!empty($dadosGrafico)): ?>
    const ctx = document.getElementById('meuGrafico').getContext('2d');
    const dados = <?= json_encode($dadosGrafico) ?>;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: dados.map(d => d.categoria),
            datasets: [{
                label: 'R$',
                data: dados.map(d => d.total),
                backgroundColor: [
                    '#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4'
                ],
                hoverOffset: 10,
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            cutout: '70%',
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, font: { size: 10, weight: 'bold' }, padding: 15 } }
            }
        }
    });
    <?php endif; ?>
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>