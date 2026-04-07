<?php require_once __DIR__ . '/partials/header.php'; ?>

<div class="max-w-5xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Adicionar Nova Conta</h2>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <form action="/financeiro/public/index.php/salvar-transacao" method="POST" class="p-6 md:p-8" id="form-transacao">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="col-span-1 md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Descrição</label>
                    <input type="text" name="descricao" required placeholder="Ex: Jantar, Mercado, Jogo na Steam" class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Valor Total (R$)</label>
                    <input type="text" name="valor_total" required placeholder="0,00" oninput="mascaraMoeda(this)" class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500 font-bold" id="valor-total">
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
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Cartão / Forma de Pagto</label>
                    <select name="cartao_id" class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Dinheiro / PIX</option>
                        <?php foreach ($cartoes as $car): ?>
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
                    <input type="month" name="mes_referencia" value="<?= date('Y-m') ?>" required class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" id="mes-referencia">
                </div>
            </div>

            <div class="mb-6 rounded-xl border border-indigo-100 bg-indigo-50/60 p-4">
                <label class="inline-flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="compra_parcelada" value="1" id="compra-parcelada" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm font-semibold text-slate-800">Compra Parcelada?</span>
                </label>

                <div id="parcelamento-config" class="hidden mt-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Qtd de Parcelas</label>
                            <input type="number" name="qtd_parcelas" id="qtd-parcelas" min="2" step="1" value="2" class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Mês da 1ª Parcela</label>
                            <input type="month" name="mes_primeira_parcela" id="mes-primeira-parcela" value="<?= date('Y-m') ?>" class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <div>
                            <button type="button" id="gerar-parcelas" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-4 rounded-md transition-colors">
                                Gerar Estrutura de Parcelas
                            </button>
                        </div>
                    </div>

                    <p class="text-xs text-slate-500 mt-3">As parcelas serão geradas com todos os participantes definidos abaixo e com divisão igual por padrão.</p>
                </div>
            </div>

            <div id="bloco-divisao-unica">
                <hr class="my-6 border-gray-200">

                <div class="mb-6">
                    <h3 id="divisao-principal-titulo" class="text-lg font-bold text-indigo-600 mb-2">Divisão da Conta</h3>
                    <p id="divisao-principal-descricao" class="text-sm text-slate-500 mb-4 italic">Defina quanto cada um vai pagar. Se for tudo seu, deixe 0,00 nos amigos.</p>

                    <div id="divisoes-area" class="space-y-3">
                        <div class="flex items-center gap-4 bg-slate-50 p-3 rounded-md border border-slate-200">
                            <div class="flex-1">
                                <span class="block text-sm font-medium text-slate-700">Minha Parte</span>
                                <input type="hidden" name="divisoes[0][pessoa_id]" value="">
                            </div>
                            <div class="w-full md:w-1/3">
                                <input type="text" name="divisoes[0][valor_divisao]" value="0,00" required oninput="mascaraMoeda(this)" class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500 text-right font-medium">
                                <input type="hidden" name="divisoes[0][status_pago]" value="1">
                            </div>
                        </div>
                    </div>

                    <button type="button" onclick="adicionarDivisao()" id="btn-adicionar-amigo-global" class="mt-4 text-sm font-medium text-indigo-600 hover:text-indigo-500 flex items-center gap-1 bg-indigo-50 px-3 py-2 rounded-md transition-colors border border-indigo-100">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span id="btn-adicionar-amigo-global-label">Adicionar Amigo na Conta</span>
                    </button>
                </div>
            </div>

            <div id="parcelas-wrapper" class="hidden">
                <hr class="my-6 border-gray-200">

                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-indigo-600">Estrutura das Parcelas</h3>
                        <p class="text-sm text-slate-500 italic">Ajuste o rateio de cada parcela individualmente.</p>
                    </div>
                    <button type="button" id="dividir-igual-parcelas" class="text-sm font-medium text-emerald-700 hover:text-emerald-800 bg-emerald-50 px-4 py-2 rounded-md border border-emerald-100 transition-colors">
                        Dividir todas as parcelas igualmente entre os selecionados
                    </button>
                </div>

                <div id="parcelas-area" class="space-y-5"></div>
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
    const pessoas = <?= json_encode(
        $pessoas,
        JSON_UNESCAPED_UNICODE
        | JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
    ) ?>;
    let indexDivisao = 1;

    const compraParceladaCheckbox = document.getElementById('compra-parcelada');
    const parcelamentoConfig = document.getElementById('parcelamento-config');
    const parcelasWrapper = document.getElementById('parcelas-wrapper');
    const parcelasArea = document.getElementById('parcelas-area');
    const qtdParcelasInput = document.getElementById('qtd-parcelas');
    const mesPrimeiraParcelaInput = document.getElementById('mes-primeira-parcela');
    const mesReferenciaInput = document.getElementById('mes-referencia');
    const valorTotalInput = document.getElementById('valor-total');
    const divisaoPrincipalTitulo = document.getElementById('divisao-principal-titulo');
    const divisaoPrincipalDescricao = document.getElementById('divisao-principal-descricao');
    const btnAdicionarAmigoGlobalLabel = document.getElementById('btn-adicionar-amigo-global-label');

    compraParceladaCheckbox.addEventListener('change', toggleParcelamento);
    document.getElementById('gerar-parcelas').addEventListener('click', gerarEstruturaParcelas);
    document.getElementById('dividir-igual-parcelas').addEventListener('click', dividirTodasParcelasIgualmente);

    function toggleParcelamento() {
        const ativo = compraParceladaCheckbox.checked;
        parcelamentoConfig.classList.toggle('hidden', !ativo);
        parcelasWrapper.classList.toggle('hidden', !ativo || parcelasArea.children.length === 0);

        alternarRequiredDivisaoUnica(ativo);
        alternarRequiredParcelas(ativo);

        divisaoPrincipalTitulo.textContent = ativo ? 'Quem vai participar dessa compra?' : 'Divisão da Conta';
        divisaoPrincipalDescricao.textContent = ativo
            ? 'Adicione aqui todos os participantes da compra. Os valores serão definidos nas parcelas abaixo.'
            : 'Defina quanto cada um vai pagar. Se for tudo seu, deixe 0,00 nos amigos.';
        btnAdicionarAmigoGlobalLabel.textContent = ativo ? 'Adicionar Participante' : 'Adicionar Amigo na Conta';

        if (ativo) {
            mesPrimeiraParcelaInput.value = mesReferenciaInput.value || mesPrimeiraParcelaInput.value;
        }
    }

    function alternarRequiredDivisaoUnica(isParcelado) {
        document.querySelectorAll('#divisoes-area input[name*="[valor_divisao]"]').forEach((campo) => {
            campo.required = !isParcelado;
            const containerValor = campo.parentElement;
            if (containerValor) {
                containerValor.style.display = isParcelado ? 'none' : 'block';
            }
        });
    }

    function alternarRequiredParcelas(ativo) {
        document.querySelectorAll('#parcelas-area input[name*="[valor]"], #parcelas-area select[name*="[pessoa_id]"]').forEach((campo) => {
            if (campo.tagName === 'SELECT') {
                campo.required = ativo;
            } else if (campo.type !== 'hidden') {
                campo.required = ativo;
            }
        });
    }

    function adicionarDivisao() {
        const area = document.getElementById('divisoes-area');

        let opcoesHtml = '<option value="" disabled selected>Selecione a pessoa</option>';
        pessoas.forEach((pessoa) => {
            opcoesHtml += `<option value="${pessoa.id}">${escapeHtml(pessoa.nome)}</option>`;
        });

        const novaLinha = `
            <div class="flex items-center gap-4 bg-white p-3 rounded-md border border-slate-200" id="linha-${indexDivisao}">
                <div class="flex-1">
                    <select name="divisoes[${indexDivisao}][pessoa_id]" required class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                        ${opcoesHtml}
                    </select>
                </div>
                <div class="w-full md:w-1/3">
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
        
        alternarRequiredDivisaoUnica(compraParceladaCheckbox.checked);
    }

    function removerDivisao(id) {
        const linha = document.getElementById(`linha-${id}`);
        if (linha) {
            linha.remove();
        }
    }

    function obterParticipantesGlobais() {
        const participantes = [{
            pessoaId: '',
            nome: 'Minha Parte',
            statusPago: 1,
            tipo: 'mine'
        }];

        const selects = Array.from(document.querySelectorAll('#divisoes-area select[name*="[pessoa_id]"]'));
        const idsSelecionados = new Set();

        for (const select of selects) {
            const pessoaId = select.value;
            if (!pessoaId) {
                alert('Selecione todas as pessoas adicionadas antes de gerar as parcelas.');
                select.focus();
                return [];
            }

            if (idsSelecionados.has(pessoaId)) {
                alert('Há participantes repetidos na compra. Ajuste a lista antes de gerar as parcelas.');
                select.focus();
                return [];
            }

            const nome = select.options[select.selectedIndex]?.text || 'Participante';
            idsSelecionados.add(pessoaId);
            participantes.push({
                pessoaId,
                nome,
                statusPago: 0,
                tipo: 'friend'
            });
        }

        return participantes;
    }

    function gerarEstruturaParcelas() {
        const quantidade = parseInt(qtdParcelasInput.value, 10);
        const valorTotal = parseMoeda(valorTotalInput.value);
        const mesInicial = mesPrimeiraParcelaInput.value;
        const participantes = obterParticipantesGlobais();

        if (!quantidade || quantidade < 2) {
            alert('Informe uma quantidade de parcelas maior ou igual a 2.');
            qtdParcelasInput.focus();
            return;
        }

        if (!mesInicial) {
            alert('Informe o mês da 1ª parcela.');
            mesPrimeiraParcelaInput.focus();
            return;
        }

        if (valorTotal <= 0) {
            alert('Informe o valor total da compra antes de gerar as parcelas.');
            valorTotalInput.focus();
            return;
        }

        if (participantes.length === 0) {
            return;
        }

        parcelasArea.innerHTML = '';
        const valoresParcelas = splitAmount(valorTotal, quantidade);

        for (let parcelaIndex = 0; parcelaIndex < quantidade; parcelaIndex++) {
            parcelasArea.insertAdjacentHTML('beforeend', criarBlocoParcelaHtml(parcelaIndex, quantidade, valoresParcelas[parcelaIndex], mesInicial, participantes));
        }

        parcelasWrapper.classList.remove('hidden');
        alternarRequiredParcelas(true);
    }

    function criarBlocoParcelaHtml(parcelaIndex, quantidade, valorParcela, mesInicial, participantes) {
        const referencia = addMonthsToMonthString(mesInicial, parcelaIndex);
        const valorFormatado = formatCurrency(valorParcela);
        const valoresIndividuais = splitAmount(valorParcela, participantes.length);
        const linhasDivisao = participantes
            .map((participante, divisaoIndex) => criarLinhaParticipanteParcela(parcelaIndex, divisaoIndex, participante, valoresIndividuais[divisaoIndex]))
            .join('');

        return `
            <section class="rounded-xl border border-slate-200 shadow-sm overflow-hidden" data-parcela-index="${parcelaIndex}">
                <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <h4 class="font-semibold text-slate-800">Parcela ${parcelaIndex + 1}/${quantidade}</h4>
                        <p class="text-xs text-slate-500">Mês de referência: ${referencia}</p>
                    </div>
                    <div class="text-sm font-semibold text-indigo-700">Valor da parcela: R$ ${valorFormatado}</div>
                </div>

                <div class="p-4 space-y-4">
                    <input type="hidden" name="parcelas[${parcelaIndex}][numero]" value="${parcelaIndex + 1}">
                    <input type="hidden" name="parcelas[${parcelaIndex}][mes_referencia]" value="${referencia}">
                    <input type="hidden" name="parcelas[${parcelaIndex}][valor_total]" value="${valorFormatado}">

                    <div class="space-y-3" id="parcelas-${parcelaIndex}-divisoes">
                        ${linhasDivisao}
                    </div>

                    <div class="flex flex-col md:flex-row gap-3">
                        <button type="button" class="text-sm font-medium text-emerald-700 hover:text-emerald-800 bg-emerald-50 px-3 py-2 rounded-md border border-emerald-100 w-full md:w-auto" onclick="preencherDivisaoIgualParcela(${parcelaIndex})">
                            Dividir igualmente esta parcela
                        </button>
                    </div>
                </div>
            </section>
        `;
    }

    function criarLinhaParticipanteParcela(parcelaIndex, divisaoIndex, participante, valor) {
        const ehMinhaParte = participante.tipo === 'mine';
        const classeFundo = ehMinhaParte ? 'bg-slate-50' : 'bg-white';
        const titulo = ehMinhaParte ? 'Minha Parte' : escapeHtml(participante.nome);

        return `
            <div class="grid grid-cols-1 md:grid-cols-[1fr_180px_auto] gap-3 items-end rounded-md border border-slate-200 ${classeFundo} p-3 parcela-divisao" data-role="${participante.tipo}" data-divisao-index="${divisaoIndex}">
                <div>
                    <span class="block text-sm font-medium text-slate-700 mb-1">${titulo}</span>
                    <input type="hidden" name="parcelas[${parcelaIndex}][divisoes][${divisaoIndex}][pessoa_id]" value="${participante.pessoaId}">
                    <input type="hidden" name="parcelas[${parcelaIndex}][divisoes][${divisaoIndex}][status_pago]" value="${participante.statusPago}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Valor</label>
                    <input type="text" name="parcelas[${parcelaIndex}][divisoes][${divisaoIndex}][valor]" value="${formatCurrency(valor)}" required oninput="mascaraMoeda(this)" class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500 text-right ${ehMinhaParte ? 'font-medium' : ''} divisao-valor">
                </div>
                <button type="button" onclick="removerDivisaoParcela(this)" class="h-[42px] px-3 rounded-md border border-rose-100 bg-rose-50 text-rose-600 hover:text-rose-700 transition-colors" title="Remover da parcela">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </div>
        `;
    }

    function removerDivisaoParcela(botao) {
        const linha = botao.closest('.parcela-divisao');
        if (linha) {
            const section = linha.closest('section');
            const parcelaIndex = section.dataset.parcelaIndex;
            linha.remove();
            preencherDivisaoIgualParcela(parcelaIndex);
        }
    }

    function preencherDivisaoIgualParcela(parcelaIndex) {
        const container = document.getElementById(`parcelas-${parcelaIndex}-divisoes`);
        const linhas = Array.from(container.querySelectorAll('.parcela-divisao'));
        if (linhas.length === 0) {
            return;
        }

        const valorParcelaInput = document.querySelector(`input[name="parcelas[${parcelaIndex}][valor_total]"]`);
        const valorParcela = parseMoeda(valorParcelaInput.value);
        const valores = splitAmount(valorParcela, linhas.length);

        linhas.forEach((linha, idx) => {
            const inputValor = linha.querySelector('.divisao-valor');
            if (inputValor) {
                inputValor.value = formatCurrency(valores[idx]);
            }
        });
    }

    function dividirTodasParcelasIgualmente() {
        const secoes = Array.from(parcelasArea.querySelectorAll('[data-parcela-index]'));
        if (secoes.length === 0) {
            alert('Gere as parcelas primeiro.');
            return;
        }

        secoes.forEach((secao) => {
            preencherDivisaoIgualParcela(parseInt(secao.dataset.parcelaIndex, 10));
        });
    }

    function parseMoeda(valor) {
        if (!valor) {
            return 0;
        }

        const normalizado = valor.toString().replace(/\./g, '').replace(',', '.').replace(/[^\d.-]/g, '');
        const numero = parseFloat(normalizado);
        return Number.isNaN(numero) ? 0 : numero;
    }

    function formatCurrency(valor) {
        return Number(valor || 0).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function splitAmount(total, parts) {
        const centavosTotal = Math.round(total * 100);
        const base = Math.floor(centavosTotal / parts);
        const resto = centavosTotal % parts;
        const valores = [];

        for (let i = 0; i < parts; i++) {
            valores.push((base + (i < resto ? 1 : 0)) / 100);
        }

        return valores;
    }

    function addMonthsToMonthString(monthString, monthsToAdd) {
        const [year, month] = monthString.split('-').map(Number);
        const date = new Date(year, month - 1 + monthsToAdd, 1);
        const yyyy = date.getFullYear();
        const mm = String(date.getMonth() + 1).padStart(2, '0');
        return `${yyyy}-${mm}`;
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function mascaraMoeda(input) {
        let valor = input.value.replace(/\D/g, '');

        if (valor === '') {
            input.value = '';
            return;
        }

        valor = (parseInt(valor, 10) / 100).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        input.value = valor;
    }
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
