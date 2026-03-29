<?php require_once __DIR__ . '/partials/header.php'; ?>

<div class="max-w-3xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Editar Transação #<?= $transacao['id'] ?></h2>
        <a href="/financeiro/public/index.php" class="text-sm text-slate-500 hover:text-slate-700 underline">Voltar</a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <form action="/financeiro/public/index.php/atualizar-transacao" method="POST" class="p-6 md:p-8">
            <input type="hidden" name="id" value="<?= $transacao['id'] ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="col-span-1 md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Descrição</label>
                    <input type="text" name="descricao" value="<?= htmlspecialchars($transacao['descricao']) ?>" required class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Valor Total (R$)</label>
                    <input type="text" name="valor_total" value="<?= number_format($transacao['valor_total'], 2, ',', '.') ?>" required oninput="mascaraMoeda(this)" class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500 font-bold">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tipo</label>
                    <select name="tipo" required class="w-full border-gray-300 border rounded-md shadow-sm p-2">
                        <option value="despesa" <?= $transacao['tipo'] == 'despesa' ? 'selected' : '' ?>>Saída (Despesa)</option>
                        <option value="receita" <?= $transacao['tipo'] == 'receita' ? 'selected' : '' ?>>Entrada (Receita)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Categoria</label>
                    <select name="categoria_id" class="w-full border-gray-300 border rounded-md shadow-sm p-2">
                        <option value="">Nenhuma Categoria</option>
                        <?php foreach($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $transacao['categoria_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Cartão / Forma</label>
                    <select name="cartao_id" class="w-full border-gray-300 border rounded-md shadow-sm p-2">
                        <option value="">Dinheiro / PIX</option>
                        <?php foreach($cartoes as $car): ?>
                            <option value="<?= $car['id'] ?>" <?= $transacao['cartao_id'] == $car['id'] ? 'selected' : '' ?>><?= htmlspecialchars($car['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Data da Compra</label>
                    <input type="date" name="data_movimentacao" value="<?= $transacao['data_movimentacao'] ?>" required class="w-full border-gray-300 border rounded-md shadow-sm p-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Mês de Referência</label>
                    <input type="month" name="mes_referencia" value="<?= $transacao['mes_referencia'] ?>" required class="w-full border-gray-300 border rounded-md shadow-sm p-2">
                </div>
            </div>

            <hr class="my-6 border-gray-200">

            <div class="mb-6">
                <h3 class="text-lg font-bold text-indigo-600 mb-4">Divisão da Conta</h3>
                
                <div id="divisoes-area" class="space-y-3">
                    <?php 
                    $i = 0;
                    foreach($divisoesAtuais as $div): 
                        $isMe = is_null($div['pessoa_id']);
                    ?>
                        <div class="flex items-center gap-4 <?= $isMe ? 'bg-slate-50' : 'bg-white' ?> p-3 rounded-md border border-slate-200" id="linha-<?= $i ?>">
                            <div class="flex-1">
                                <?php if($isMe): ?>
                                    <span class="block text-sm font-medium text-slate-700">Minha Parte</span>
                                    <input type="hidden" name="divisoes[<?= $i ?>][pessoa_id]" value="">
                                <?php else: ?>
                                    <select name="divisoes[<?= $i ?>][pessoa_id]" required class="w-full border-gray-300 border rounded-md p-2">
                                        <?php foreach($pessoas as $p): ?>
                                            <option value="<?= $p['id'] ?>" <?= $div['pessoa_id'] == $p['id'] ? 'selected' : '' ?>><?= $p['nome'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                            <div class="w-1/3">
                                <input type="text" name="divisoes[<?= $i ?>][valor_divisao]" value="<?= number_format($div['valor_divisao'], 2, ',', '.') ?>" required oninput="mascaraMoeda(this)" class="w-full border-gray-300 border rounded-md p-2 text-right">
                                <input type="hidden" name="divisoes[<?= $i ?>][status_pago]" value="<?= $div['status_pago'] ?>">
                            </div>
                            <?php if(!$isMe): ?>
                                <button type="button" onclick="removerDivisao(<?= $i ?>)" class="text-rose-500 p-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php 
                        $i++;
                    endforeach; 
                    ?>
                </div>

                <button type="button" onclick="adicionarDivisao()" class="mt-4 text-sm font-medium text-indigo-600 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Adicionar Amigo
                </button>
            </div>

            <div class="flex justify-end pt-4">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-md transition-all shadow-lg">
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const pessoas = <?= json_encode($pessoas) ?>;
    let indexDivisao = <?= $i ?>; // Começa de onde parou no loop PHP

    function adicionarDivisao() {
        const area = document.getElementById('divisoes-area');
        let opcoesHtml = '<option value="" disabled selected>Selecione</option>';
        pessoas.forEach(p => { opcoesHtml += `<option value="${p.id}">${p.nome}</option>`; });

        const novaLinha = `
            <div class="flex items-center gap-4 bg-white p-3 rounded-md border border-slate-200" id="linha-${indexDivisao}">
                <div class="flex-1">
                    <select name="divisoes[${indexDivisao}][pessoa_id]" required class="w-full border-gray-300 border rounded-md p-2">
                        ${opcoesHtml}
                    </select>
                </div>
                <div class="w-1/3">
                    <input type="text" name="divisoes[${indexDivisao}][valor_divisao]" placeholder="0,00" required oninput="mascaraMoeda(this)" class="w-full border-gray-300 border rounded-md p-2 text-right">
                    <input type="hidden" name="divisoes[${indexDivisao}][status_pago]" value="0">
                </div>
                <button type="button" onclick="removerDivisao(${indexDivisao})" class="text-rose-500 p-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </div>
        `;
        area.insertAdjacentHTML('beforeend', novaLinha);
        indexDivisao++;
    }

    function removerDivisao(id) { document.getElementById(`linha-${id}`).remove(); }

    function mascaraMoeda(input) {
        let valor = input.value.replace(/\D/g, '');
        if (valor === '') { input.value = ''; return; }
        valor = (parseInt(valor) / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        input.value = valor;
    }
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>