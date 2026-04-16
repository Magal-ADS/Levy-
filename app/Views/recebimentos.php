<?php require_once __DIR__ . '/partials/header.php'; ?>

<?php
function getCorAmigo($nome) {
    $nomeStr = strtolower($nome);
    if (strpos($nomeStr, 'anna') !== false) return ['bg' => 'bg-fuchsia-100', 'text' => 'text-fuchsia-600', 'border' => 'border-fuchsia-200'];
    if (strpos($nomeStr, 'pais') !== false) return ['bg' => 'bg-cyan-100', 'text' => 'text-cyan-700', 'border' => 'border-cyan-200'];
    if (strpos($nomeStr, 'lucio') !== false) return ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'border' => 'border-purple-200'];
    if (strpos($nomeStr, 'vera') !== false) return ['bg' => 'bg-red-100', 'text' => 'text-red-600', 'border' => 'border-red-200'];

    return ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600', 'border' => 'border-indigo-200'];
}
?>

<div class="max-w-7xl mx-auto w-full">
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-700">Dívidas de Amigos (A Receber)</h2>
            <p class="text-sm text-slate-500">Controle quem te deve no mês selecionado.</p>
        </div>
        
        <div class="flex flex-col sm:flex-row items-center gap-4 w-full md:w-auto">
            <form method="GET" action="/financeiro/public/index.php/recebimentos" class="w-full sm:w-auto">
                <input type="month" name="mes" value="<?= htmlspecialchars($mesReferencia) ?>" class="w-full sm:w-auto border-slate-200 rounded-lg text-sm text-slate-600 focus:ring-indigo-500 focus:border-indigo-500 px-4 py-2 cursor-pointer shadow-sm" onchange="this.form.submit()">
            </form>

            <div class="w-full sm:w-auto text-right bg-white px-4 py-2 rounded-lg shadow-sm border border-slate-200 flex sm:block justify-between items-center">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total no mês</span>
                <span class="text-xl font-black text-emerald-500">R$ <?= number_format($totalGeral ?? 0, 2, ',', '.') ?></span>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['sucesso'])): ?>
        <div class="bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 p-3 rounded-r-lg mb-6 shadow-sm flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span class="text-sm font-bold">Pagamento confirmado com sucesso! O valor já reflete no seu saldo disponível.</span>
        </div>
    <?php endif; ?>

    <div class="space-y-4">
        <?php if (empty($pessoasAgrupadas)): ?>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-10 text-center">
                <span class="text-4xl block mb-2">🎉</span>
                <p class="text-slate-500 font-bold">Ninguém te deve nada neste mês.</p>
            </div>
        <?php else: ?>

            <!-- Card: Minhas Despesas Detalhadas (Auditoria) -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div onclick="toggleSanfona('meus-gastos')" class="w-full flex flex-col md:flex-row justify-between items-start md:items-center p-5 bg-slate-50 hover:bg-slate-100 transition-colors cursor-pointer gap-4 md:gap-0">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-black text-lg border border-indigo-200">
                            M
                        </div>
                        <div class="text-left">
                            <h3 class="font-bold text-lg text-slate-700 leading-tight">Minhas Despesas Detalhadas (Auditoria)</h3>
                            <span class="text-[10px] uppercase font-bold text-slate-400"><?= count($minhasDespesas['itens']) ?> item(s)</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 w-full md:w-auto justify-end">
                        <span class="text-lg font-black text-slate-700 mr-2">R$ <?= number_format($minhasDespesas['total'], 2, ',', '.') ?></span>
                        <svg id="icone-meus-gastos" class="w-5 h-5 text-slate-400 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>

                <div id="meus-gastos" class="hidden border-t border-slate-200 bg-white">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Data / Mês</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Descrição da Conta</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Valor (sua parte)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($minhasDespesas['itens'] as $item): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-600"><?= date('d/m/Y', strtotime($item['data_movimentacao'])) ?></div>
                                        <div class="text-[9px] uppercase text-slate-400 font-bold">Ref: <?= $item['mes_referencia'] ?></div>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-slate-700"><?= htmlspecialchars($item['descricao']) ?></td>
                                    <td class="px-6 py-4 text-right font-black text-slate-600">R$ <?= number_format($item['valor_divisao'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php foreach ($pessoasAgrupadas as $pId => $pessoa): ?>
                <?php $cores = getCorAmigo($pessoa['nome']); ?>
                
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div onclick="toggleSanfona('amigo-<?= $pId ?>')" class="w-full flex flex-col md:flex-row justify-between items-start md:items-center p-5 bg-slate-50 hover:bg-slate-100 transition-colors cursor-pointer gap-4 md:gap-0">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full <?= $cores['bg'] ?> <?= $cores['text'] ?> flex items-center justify-center font-black text-lg border <?= $cores['border'] ?>">
                                <?= strtoupper(substr($pessoa['nome'], 0, 1)) ?>
                            </div>
                            <div class="text-left">
                                <h3 class="font-bold text-lg text-slate-700 leading-tight"><?= htmlspecialchars($pessoa['nome']) ?></h3>
                                <span class="text-[10px] uppercase font-bold text-slate-400"><?= count($pessoa['itens']) ?> conta(s) pendente(s)</span>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3 w-full md:w-auto justify-end">
                            <span class="text-lg font-black text-rose-500 mr-2">R$ <?= number_format($pessoa['total_devido'], 2, ',', '.') ?></span>
                            <a href="/financeiro/public/index.php/relatorio-pessoa?pessoa_id=<?= $pId ?>&mes=<?= urlencode($mesReferencia) ?>" onclick="event.stopPropagation()" target="_blank" class="inline-flex items-center justify-center rounded-lg border border-rose-200 bg-rose-50 p-2 text-rose-600 transition-colors hover:border-rose-300 hover:bg-rose-100" title="Gerar relatório em PDF">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M7 4h7l5 5v9a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"></path></svg>
                            </a>
                            <a href="/financeiro/public/index.php/baixar-recebimento?pessoa_id=<?= $pId ?>&mes=<?= $mesReferencia ?>" onclick="event.stopPropagation(); return confirm('Deseja dar baixa no valor TOTAL de R$ <?= number_format($pessoa['total_devido'], 2, ',', '.') ?> de <?= htmlspecialchars($pessoa['nome']) ?> referente a este mês?');" class="bg-emerald-500 hover:bg-emerald-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all shadow-sm flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Quitar Tudo
                            </a>
                            <svg id="icone-amigo-<?= $pId ?>" class="w-5 h-5 text-slate-400 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>

                    <div id="amigo-<?= $pId ?>" class="hidden border-t border-slate-200 bg-white">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Data / Mês</th>
                                    <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Descrição da Conta</th>
                                    <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Valor</th>
                                    <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Ação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($pessoa['itens'] as $item): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-slate-600"><?= date('d/m/Y', strtotime($item['data_movimentacao'])) ?></div>
                                            <div class="text-[9px] uppercase text-slate-400 font-bold">Ref: <?= $item['mes_referencia'] ?></div>
                                        </td>
                                        <td class="px-6 py-4 font-bold text-slate-700"><?= htmlspecialchars($item['descricao']) ?></td>
                                        <td class="px-6 py-4 text-right font-black text-slate-600">R$ <?= number_format($item['valor_divisao'], 2, ',', '.') ?></td>
                                        <td class="px-6 py-4 text-center">
                                            <a href="/financeiro/public/index.php/baixar-recebimento?id=<?= $item['divisao_id'] ?>&mes=<?= $mesReferencia ?>" onclick="return confirm('Confirmar o recebimento deste valor?')" class="inline-block bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white border border-emerald-200 hover:border-emerald-500 px-3 py-1.5 rounded-lg text-[10px] font-bold transition-all shadow-sm">
                                                Confirmar Pagamento
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleSanfona(id) {
        const conteudo = document.getElementById(id);
        const icone = document.getElementById('icone-' + id);
        conteudo.classList.toggle('hidden');
        if (conteudo.classList.contains('hidden')) {
            icone.classList.remove('rotate-180');
        } else {
            icone.classList.add('rotate-180');
        }
    }
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
