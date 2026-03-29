<?php require_once __DIR__ . '/partials/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold text-slate-800 mb-6">Configurações de Perfil</h2>

    <?php if (isset($_GET['sucesso'])): ?>
        <div class="bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded mb-6 shadow-sm">
            Configurações atualizadas com sucesso!
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow p-6 md:p-8">
        <form action="/financeiro/public/index.php/salvar-configuracoes" method="POST">
            <div class="grid grid-cols-1 gap-6">
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Seu Nome</label>
                    <input type="text" value="<?= $usuario['nome'] ?>" disabled class="w-full bg-slate-50 border-gray-300 border rounded-md p-2 text-slate-500 cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Salário Base (Mensal)</label>
                    <input type="text" name="salario_base" value="<?= number_format($usuario['salario_base'], 2, ',', '.') ?>" oninput="mascaraMoeda(this)" class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Saldo Inicial (O que sobrou do mês passado)</label>
                    <input type="text" name="saldo_inicial_mes" value="<?= number_format($usuario['saldo_inicial_mes'], 2, ',', '.') ?>" oninput="mascaraMoeda(this)" class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="text-xs text-slate-400 mt-1">Esse valor será somado ao seu salário no cálculo do saldo disponível.</p>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md transition-colors shadow">
                        Salvar Alterações
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function mascaraMoeda(input) {
        let valor = input.value.replace(/\D/g, '');
        if (valor === '') { input.value = ''; return; }
        valor = (parseInt(valor) / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        input.value = valor;
    }
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>