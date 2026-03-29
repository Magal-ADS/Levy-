<?php require_once __DIR__ . '/partials/header.php'; ?>

<div class="max-w-3xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Adicionar Nova Conta</h2>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <form action="/financeiro/public/index.php/salvar-transacao" method="POST" class="p-6 md:p-8">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="col-span-1 md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Descrição</label>
                    <input type="text" name="descricao" required placeholder="Ex: Jantar, Mercado, Jogo na Steam" class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Valor Total (R$)</label>
                    <input type="text" name="valor_total" required placeholder="0,00" oninput="mascaraMoeda(this)" class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500 font-bold">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tipo</label>
                    <select name="tipo" required class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500 font-medium">
                        <option value="despesa">Saída (Despesa)</option>
                        <option value="receita">Entrada (Receita)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Categoria</label>
                    <select name="categoria_id" class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Nenhuma Categoria</option>
                        <?php foreach($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Cartão / Forma de Pagto</label>
                    <select name="cartao_id" class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Dinheiro / PIX</option>
                        <?php foreach($cartoes as $car): ?>
                            <option value="<?= $car['id'] ?>"><?= htmlspecialchars($car['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Data da Compra</label>
                    <input type="date" name="data_movimentacao" value="<?= date('Y-m-d') ?>" required class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Mês de Referência</label>
                    <input type="month" name="mes_referencia" value="<?= date('Y-m') ?>" required class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <hr class="my-6 border-gray-200">

            <div class="mb-6">
                <h3 class="text-lg font-medium text-slate-800 mb-2 font-bold text-indigo-600">Divisão da Conta</h3>
                <p class="text-sm text-slate-500 mb-4 italic">Defina quanto cada um vai pagar. Se for tudo seu, deixe 0,00 nos amigos.</p>
                
                <div id="divisoes-area" class="space-y-3">
                    <div class="flex items-center gap-4 bg-slate-50 p-3 rounded-md border border-slate-200">
                        <div class="flex-1">
                            <span class="block text-sm font-medium text-slate-700">Minha Parte</span>
                            <input type="hidden" name="divisoes[0][pessoa_id]" value="">
                        </div>
                        <div class="w-1/3">
                            <input type="text" name="divisoes[0][valor_divisao]" value="0,00" required oninput="mascaraMoeda(this)" class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500 text-right font-medium">
                            <input type="hidden" name="divisoes[0][status_pago]" value="1">
                        </div>
                    </div>
                </div>

                <button type="button" onclick="adicionarDivisao()" class="mt-4 text-sm font-medium text-indigo-600 hover:text-indigo-500 flex items-center gap-1 bg-indigo-50 px-3 py-2 rounded-md transition-colors border border-indigo-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Adicionar Amigo na Conta
                </button>
            </div>

            <div class="flex justify-end pt-4">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-md transition-colors shadow-lg">
                    Salvar Transação
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Converte os dados das pessoas vindos do PHP para o JS
    const pessoas = <?= json_encode($pessoas) ?>;
    let indexDivisao = 1; 

    function adicionarDivisao() {
        const area = document.getElementById('divisoes-area');
        
        let opcoesHtml = '<option value="" disabled selected>Selecione a pessoa</option>';
        pessoas.forEach(pessoa => {
            opcoesHtml += `<option value="${pessoa.id}">${pessoa.nome}</option>`;
        });

        const novaLinha = `
            <div class="flex items-center gap-4 bg-white p-3 rounded-md border border-slate-200" id="linha-${indexDivisao}">
                <div class="flex-1">
                    <select name="divisoes[${indexDivisao}][pessoa_id]" required class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                        ${opcoesHtml}
                    </select>
                </div>
                <div class="w-1/3">
                    <input type="text" name="divisoes[${indexDivisao}][valor_divisao]" placeholder="0,00" required oninput="mascaraMoeda(this)" class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500 text-right">
                    <input type="hidden" name="divisoes[${indexDivisao}][status_pago]" value="0">
                </div>
                <button type="button" onclick="removerDivisao(${indexDivisao})" class="text-rose-500 hover:text-rose-700 transition-colors" title="Remover Amigo">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </div>
        `;
        
        area.insertAdjacentHTML('beforeend', novaLinha);
        indexDivisao++;
    }

    function removerDivisao(id) {
        document.getElementById(`linha-${id}`).remove();
    }

    // Função para aplicar a máscara de moeda (R$ 0,00)
    function mascaraMoeda(input) {
        let valor = input.value.replace(/\D/g, ''); // Remove tudo o que não for número
        
        if (valor === '') {
            input.value = '';
            return;
        }
        
        // Converte para formato de moeda local (pt-BR)
        valor = (parseInt(valor) / 100).toLocaleString('pt-BR', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
        });
        input.value = valor;
    }
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>