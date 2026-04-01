<?php $pageTitle = 'Perfil & Análise'; ?>
<?php require_once __DIR__ . '/partials/header.php'; ?>

<div class="max-w-7xl mx-auto">
    <div class="mb-8">
        <h2 class="text-3xl font-bold tracking-tight text-slate-900">Perfil &amp; Análise</h2>
        <p class="mt-2 text-slate-500">Atualize seus dados principais e acompanhe quanto do seu salário está sendo consumido em cada categoria.</p>
    </div>

    <?php if (isset($_GET['sucesso'])): ?>
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700 shadow-sm">
            Perfil atualizado com sucesso.
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
            <div class="mb-6">
                <h3 class="text-xl font-semibold text-slate-900">Meu Perfil</h3>
                <p class="mt-1 text-sm text-slate-500">Essas informações servem de base para a leitura da sua saúde financeira no sistema.</p>
            </div>

            <form action="/financeiro/public/index.php/salvar-configuracoes" method="POST">
                <input type="hidden" name="mes" value="<?= htmlspecialchars($mesSelecionado) ?>">

                <div class="grid grid-cols-1 gap-5">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Seu Nome</label>
                        <input type="text" name="nome" value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Salário Base (Mensal)</label>
                        <input type="text" name="salario_base" value="<?= number_format((float) ($usuario['salario_base'] ?? 0), 2, ',', '.') ?>" oninput="mascaraMoeda(this)" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Saldo Inicial</label>
                        <input type="text" name="saldo_inicial_mes" value="<?= number_format((float) ($usuario['saldo_inicial_mes'] ?? 0), 2, ',', '.') ?>" oninput="mascaraMoeda(this)" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <p class="mt-2 text-xs text-slate-400">Esse valor representa o que sobrou do mês anterior e complementa sua base de saldo disponível.</p>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 font-medium text-white shadow-sm transition-colors hover:bg-indigo-700">
                            Salvar Alterações
                        </button>
                    </div>
                </div>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-slate-900">Análise do Mês</h3>
                    <p class="mt-1 text-sm text-slate-500">Veja o impacto de cada categoria sobre o seu salário base de R$ <?= number_format($salarioBase, 2, ',', '.') ?>.</p>
                </div>

                <form method="GET" action="/financeiro/public/index.php/configuracoes" class="w-full md:w-auto">
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Mês de referência</label>
                    <input type="month" name="mes" value="<?= htmlspecialchars($mesSelecionado) ?>" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 md:w-52">
                </form>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total gasto no mês</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">R$ <?= number_format($totalDespesasMes, 2, ',', '.') ?></p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Comprometimento do salário</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">
                        <?= $salarioBase > 0 ? number_format(($totalDespesasMes / $salarioBase) * 100, 1, ',', '.') : '0,0' ?>%
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Saldo livre</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-500">R$ <?= number_format($saldoLivre, 2, ',', '.') ?></p>
                    <p class="mt-1 text-sm text-slate-500">
                        <?= number_format($percentualSaldoLivre, 1, ',', '.') ?>% do salário disponível
                    </p>
                </div>
            </div>

            <?php if (empty($analiseCategorias)): ?>
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                    <p class="font-medium text-slate-700">Nenhuma despesa sua foi encontrada para <?= htmlspecialchars($mesSelecionado) ?>.</p>
                    <p class="mt-1 text-sm text-slate-500">Quando houver lançamentos do tipo despesa vinculados a você, a distribuição por categoria aparecerá aqui.</p>
                </div>
            <?php else: ?>
                <div class="space-y-5">
                    <?php foreach ($analiseCategorias as $categoria): ?>
                        <?php
                            $percentual = (float) $categoria['percentual_salario'];
                            $larguraBarra = max(0, min($percentual, 100));
                        ?>
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="mb-3 flex items-start justify-between gap-4">
                                <div>
                                    <h4 class="text-base font-semibold text-slate-900"><?= htmlspecialchars($categoria['categoria_nome']) ?></h4>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Você gastou <span class="font-semibold text-slate-700"><?= number_format($percentual, 1, ',', '.') ?>%</span>
                                        do seu salário com <span class="font-semibold text-slate-700"><?= htmlspecialchars($categoria['categoria_nome']) ?></span>
                                        (R$ <?= number_format((float) $categoria['total_gasto'], 2, ',', '.') ?>).
                                    </p>
                                </div>
                                <div class="whitespace-nowrap text-right">
                                    <p class="text-sm font-semibold text-slate-900">R$ <?= number_format((float) $categoria['total_gasto'], 2, ',', '.') ?></p>
                                    <p class="text-xs text-slate-500"><?= number_format($percentual, 1, ',', '.') ?> do salário</p>
                                </div>
                            </div>

                            <div class="h-3 w-full overflow-hidden rounded-full bg-slate-200">
                                <div class="h-3 rounded-full bg-indigo-600 transition-all duration-500" style="width: <?= number_format($larguraBarra, 2, '.', '') ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<script>
    function mascaraMoeda(input) {
        let valor = input.value.replace(/\D/g, '');
        if (valor === '') { input.value = ''; return; }
        valor = (parseInt(valor, 10) / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        input.value = valor;
    }
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
