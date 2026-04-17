<?php
// outputs only <tr> rows for $transacoes
if (empty($transacoes)): ?>
    <tr>
        <td colspan="5" class="px-6 py-12 text-center text-slate-400 italic">Nenhuma transação encontrada para os filtros selecionados.</td>
    </tr>
<?php else:
    foreach($transacoes as $tr): ?>
        <tr class="hover:bg-slate-50/80 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?= date('d/m/Y', strtotime($tr['data_movimentacao'])) ?></td>
            <td class="px-6 py-4">
                <div class="text-sm font-bold text-slate-900"><?= htmlspecialchars($tr['display_descricao'] ?? $tr['descricao']) ?></div>
                <div class="flex flex-wrap gap-2 mt-1">
                    <?php if($tr['categoria_nome']): ?>
                        <span class="text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded uppercase font-bold border border-slate-200"><?= htmlspecialchars($tr['categoria_nome']) ?></span>
                    <?php endif; ?>
                    <?php if($tr['cartao_nome']): ?>
                        <span class="text-[10px] bg-blue-50 text-blue-600 px-2 py-0.5 rounded uppercase font-bold border border-blue-100">💳 <?= htmlspecialchars($tr['cartao_nome']) ?></span>
                    <?php endif; ?>
                    <?php if($tr['amigos_nomes']): ?>
                        <span class="text-[10px] bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded uppercase font-bold border border-indigo-100">👥 <?= htmlspecialchars($tr['amigos_nomes']) ?></span>
                    <?php endif; ?>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider <?= ($tr['display_tipo'] ?? $tr['tipo']) === 'despesa' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' ?>"><?= ($tr['display_tipo'] ?? $tr['tipo']) === 'despesa' ? 'Saída' : 'Entrada' ?></span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-black <?= ($tr['display_tipo'] ?? $tr['tipo']) === 'despesa' ? 'text-rose-600' : 'text-emerald-600' ?>"><?= ($tr['display_tipo'] ?? $tr['tipo']) === 'despesa' ? '-' : '+' ?> R$ <?= number_format(($tr['display_valor'] ?? $tr['valor_total']), 2, ',', '.') ?></td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                <a href="/financeiro/public/index.php/editar-transacao?id=<?= $tr['id'] ?>" class="text-indigo-400 hover:text-indigo-700 transition-colors inline-block" title="Editar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                </a>
                <a href="/financeiro/public/index.php/deletar-transacao?id=<?= $tr['id'] ?>" onclick="return confirm('Deseja realmente apagar esta conta? As dívidas dos amigos vinculados também serão removidas.')" class="text-rose-400 hover:text-rose-700 transition-colors inline-block" title="Excluir">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </a>
            </td>
        </tr>
    <?php endforeach;
endif;
