<?php require_once __DIR__ . '/partials/header.php'; ?>

<div class="max-w-7xl mx-auto w-full">

    <?php if (isset($_GET['sucesso'])): ?>
    <div class="bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded shadow-sm mb-6" role="alert">
        <p class="font-bold text-sm">Sucesso!</p>
        <p class="text-sm">Ação realizada com sucesso no banco de dados.</p>
    </div>
    <?php endif; ?>

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-700"><?= $nomeMesAno ?></h2>
            <p class="text-xs text-slate-500 italic">Visão Geral da sua vida financeira</p>
        </div>
        
        <div class="flex flex-wrap gap-3 items-center w-full md:w-auto">
            <a href="/financeiro/public/index.php/nova-conta" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-md text-sm font-bold shadow-md transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                + Nova Transação
            </a>

            <form method="GET" class="m-0 flex gap-2">
                <input type="month" name="mes" value="<?= $mesReferencia ?>" 
                       class="border-gray-300 rounded-md shadow-sm p-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" 
                       onchange="this.form.submit()">
                
                <a href="/financeiro/public/index.php" title="Voltar para o mês atual" class="bg-slate-100 hover:bg-slate-200 p-2 rounded-md border border-slate-300 transition-colors">
                    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                </a>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-indigo-500">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Saldo Disponível</p>
            <p class="text-3xl font-black text-slate-800 mt-2">R$ <?= number_format($saldoDisponivel, 2, ',', '.') ?></p>
            <p class="text-[10px] text-slate-400 mt-2 italic">Salário + Saldo Inicial - Suas Despesas</p>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-emerald-500">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">A Receber (Amigos)</p>
            <p class="text-3xl font-black text-emerald-600 mt-2">R$ <?= number_format($aReceber, 2, ',', '.') ?></p>
            <p class="text-[10px] text-slate-400 mt-2 italic">Total acumulado que amigos te devem</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-rose-500">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Minhas Despesas</p>
            <p class="text-3xl font-black text-rose-600 mt-2">R$ <?= number_format($minhasDespesas, 2, ',', '.') ?></p>
            <p class="text-[10px] text-slate-400 mt-2 italic">Sua parte nas contas de <?= $nomeMesAno ?></p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4 bg-slate-50/50">
            <h3 class="text-lg font-bold text-slate-800">Lançamentos Recentes</h3>
            
            <form method="GET" class="flex w-full md:w-auto gap-2">
                <input type="hidden" name="mes" value="<?= $mesReferencia ?>">
                <div class="relative w-full md:w-80">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" 
                           placeholder="Buscar descrição, amigo ou categoria..." 
                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm shadow-sm">
                </div>
                <button type="submit" class="bg-slate-800 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-700 transition-colors shadow">
                    Filtrar
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Descrição / Detalhes</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Valor Total</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if(empty($transacoes)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400 italic">
                                Nenhuma transação encontrada para os filtros selecionados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($transacoes as $tr): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                    <?= date('d/m/Y', strtotime($tr['data_movimentacao'])) ?>
                                </td>
                                
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-slate-900"><?= htmlspecialchars($tr['descricao']) ?></div>
                                    <div class="flex flex-wrap gap-2 mt-1">
                                        <?php if($tr['categoria_nome']): ?>
                                            <span class="text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded uppercase font-bold border border-slate-200">
                                                <?= htmlspecialchars($tr['categoria_nome']) ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if($tr['cartao_nome']): ?>
                                            <span class="text-[10px] bg-blue-50 text-blue-600 px-2 py-0.5 rounded uppercase font-bold border border-blue-100">
                                                💳 <?= htmlspecialchars($tr['cartao_nome']) ?>
                                            </span>
                                        <?php endif; ?>

                                        <?php if($tr['amigos_nomes']): ?>
                                            <span class="text-[10px] bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded uppercase font-bold border border-indigo-100">
                                                👥 <?= htmlspecialchars($tr['amigos_nomes']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider <?= $tr['tipo'] === 'despesa' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' ?>">
                                        <?= $tr['tipo'] === 'despesa' ? 'Saída' : 'Entrada' ?>
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-black <?= $tr['tipo'] === 'despesa' ? 'text-rose-600' : 'text-emerald-600' ?>">
                                    <?= $tr['tipo'] === 'despesa' ? '-' : '+' ?> R$ <?= number_format($tr['valor_total'], 2, ',', '.') ?>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                    <a href="/financeiro/public/index.php/editar-transacao?id=<?= $tr['id'] ?>" 
                                       class="text-indigo-400 hover:text-indigo-700 transition-colors inline-block" title="Editar">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </a>

                                    <a href="/financeiro/public/index.php/deletar-transacao?id=<?= $tr['id'] ?>" 
                                       onclick="return confirm('Deseja realmente apagar esta conta? As dívidas dos amigos vinculados também serão removidas.')" 
                                       class="text-rose-400 hover:text-rose-700 transition-colors inline-block" title="Excluir">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>