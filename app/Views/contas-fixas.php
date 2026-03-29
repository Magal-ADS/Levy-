<?php require_once __DIR__ . '/partials/header.php'; ?>

<div class="max-w-7xl mx-auto w-full">
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-700">Contas Fixas & Assinaturas</h2>
            <p class="text-sm text-slate-500">Gerencie seus moldes de gastos recorrentes e dê baixa no mês atual.</p>
        </div>
        <div class="bg-white px-4 py-2 rounded-lg border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold text-slate-400 uppercase block leading-none mb-1">Mês de Referência</span>
            <span class="text-lg font-black text-indigo-600"><?= date('m/Y') ?></span>
        </div>
    </div>

    <?php if (isset($_GET['sucesso'])): ?>
        <div class="bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 p-3 rounded-r-lg mb-6 shadow-sm flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span class="text-sm font-bold">Ação realizada com sucesso!</span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 sticky top-8">
                <h3 class="font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <span class="bg-indigo-100 text-indigo-600 p-1.5 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    </span>
                    Novo Molde de Conta
                </h3>
                
                <form action="/financeiro/public/index.php/contas-fixas" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Descrição da Conta</label>
                        <input type="text" name="descricao" placeholder="Ex: Netflix, Internet, Aluguel..." required 
                               class="w-full border-slate-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Valor Estimado</label>
                            <input type="text" name="valor_estimado" oninput="mascaraMoeda(this)" placeholder="0,00" required 
                                   class="w-full border-slate-200 rounded-lg p-2.5 text-sm outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Dia Vencimento</label>
                            <input type="number" name="dia_vencimento" min="1" max="31" placeholder="10" required 
                                   class="w-full border-slate-200 rounded-lg p-2.5 text-sm outline-none focus:border-indigo-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Tipo de Pagamento</label>
                        <select name="tipo_pagamento" class="w-full border-slate-200 rounded-lg p-2.5 text-sm outline-none cursor-pointer">
                            <option value="manual">Pagamento Manual (Boleto/Pix)</option>
                            <option value="automatico">Débito Automático / Cartão</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Cartão Vinculado</label>
                        <select name="cartao_id" class="w-full border-slate-200 rounded-lg p-2.5 text-sm outline-none cursor-pointer">
                            <option value="">Nenhum (Dinheiro / Débito)</option>
                            <?php foreach($cartoes as $car): ?>
                                <option value="<?= $car['id'] ?>"><?= $car['nome'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-3 rounded-lg hover:bg-indigo-700 transition-all shadow-md active:scale-[0.98]">
                        Salvar Conta Fixa
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Dia</th>
                            <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Conta / Detalhes</th>
                            <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Valor</th>
                            <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                            <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <?php if(empty($contas)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400 italic text-sm">
                                    Nenhuma conta fixa cadastrada para monitoramento.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach($contas as $cf): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 text-sm font-black text-slate-400">
                                    <?= str_pad($cf['dia_vencimento'], 2, '0', STR_PAD_LEFT) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-slate-700"><?= htmlspecialchars($cf['descricao']) ?></div>
                                    <div class="text-[9px] text-slate-400 uppercase font-black tracking-tighter flex items-center gap-1 mt-0.5">
                                        <?= $cf['tipo_pagamento'] === 'automatico' ? '🤖 Automático' : '👤 Manual' ?>
                                        <?php if($cf['cartao_nome']): ?>
                                            <span class="text-indigo-300">•</span>
                                            <span>💳 <?= htmlspecialchars($cf['cartao_nome']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm font-black text-slate-700 italic whitespace-nowrap">
                                    R$ <?= number_format($cf['valor_estimado'], 2, ',', '.') ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if($cf['pago']): ?>
                                        <span class="bg-emerald-100 text-emerald-600 text-[9px] px-2 py-0.5 rounded font-black border border-emerald-200 inline-block">PAGO</span>
                                    <?php else: ?>
                                        <span class="bg-amber-50 text-amber-600 text-[9px] px-2 py-0.5 rounded font-black border border-amber-200 inline-block">PENDENTE</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                    <?php if(!$cf['pago']): ?>
                                        <a href="/financeiro/public/index.php/pagar-conta-fixa?id=<?= $cf['id'] ?>" 
                                           class="bg-slate-900 text-white hover:bg-indigo-600 px-3 py-1.5 rounded-lg text-[10px] font-bold transition-all shadow-sm inline-block">
                                            Baixar Pago
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="/financeiro/public/index.php/deletar-conta-fixa?id=<?= $cf['id'] ?>" 
                                       onclick="return confirm('Deseja remover este molde de conta fixa?')" 
                                       class="text-slate-300 hover:text-rose-500 transition-colors inline-block align-middle ml-2" title="Remover Molde">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function mascaraMoeda(input) {
        let valor = input.value.replace(/\D/g, '');
        if (valor === '') { input.value = ''; return; }
        valor = (parseInt(valor) / 100).toLocaleString('pt-BR', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
        });
        input.value = valor;
    }
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>