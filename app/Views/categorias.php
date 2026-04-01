<?php require_once __DIR__ . '/partials/header.php'; ?>

<div class="max-w-5xl mx-auto">
    <h2 class="text-2xl font-bold text-slate-800 mb-6">Categorias</h2>

    <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] === 'criada'): ?>
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700 shadow-sm">
            Categoria criada com sucesso.
        </div>
    <?php elseif (isset($_GET['sucesso']) && $_GET['sucesso'] === 'atualizada'): ?>
        <div class="mb-6 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sky-700 shadow-sm">
            Categoria atualizada com sucesso.
        </div>
    <?php elseif (isset($_GET['sucesso']) && $_GET['sucesso'] === 'deletada'): ?>
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-700 shadow-sm">
            Categoria removida com sucesso.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['erro']) && $_GET['erro'] === 'tipo'): ?>
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700 shadow-sm">
            Selecione um tipo válido para a categoria: <strong>receita</strong> ou <strong>despesa</strong>.
        </div>
    <?php elseif (isset($_GET['erro']) && $_GET['erro'] === 'nome'): ?>
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700 shadow-sm">
            Informe um nome para a categoria antes de salvar.
        </div>
    <?php elseif (isset($_GET['erro']) && $_GET['erro'] === 'vinculo'): ?>
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700 shadow-sm">
            Essa categoria não pode ser removida porque já está vinculada a lançamentos.
        </div>
    <?php elseif (isset($_GET['erro']) && $_GET['erro'] === 'id'): ?>
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700 shadow-sm">
            Não foi possível identificar a categoria para edição.
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Nova Categoria</h3>

            <form action="/financeiro/public/index.php/categorias" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nome</label>
                    <input type="text" name="nome" placeholder="Ex: Alimentação" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <fieldset>
                    <legend class="block text-sm font-medium text-slate-700 mb-2">Tipo</legend>
                    <div class="grid grid-cols-1 gap-3">
                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 cursor-pointer hover:border-emerald-300 hover:bg-emerald-50 transition-colors">
                            <input type="radio" name="tipo" value="receita" required class="h-4 w-4 border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span>
                                <span class="block text-sm font-semibold text-slate-800">Receita</span>
                                <span class="block text-xs text-slate-500">Categorias para entradas e ganhos.</span>
                            </span>
                        </label>

                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 cursor-pointer hover:border-rose-300 hover:bg-rose-50 transition-colors">
                            <input type="radio" name="tipo" value="despesa" required class="h-4 w-4 border-slate-300 text-rose-600 focus:ring-rose-500">
                            <span>
                                <span class="block text-sm font-semibold text-slate-800">Despesa</span>
                                <span class="block text-xs text-slate-500">Categorias para saídas e custos.</span>
                            </span>
                        </label>
                    </div>
                </fieldset>

                <button class="w-full rounded-lg bg-indigo-600 text-white py-2.5 font-medium shadow-sm transition-colors hover:bg-indigo-700">
                    Cadastrar
                </button>
            </form>
        </div>

        <div class="col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="text-lg font-semibold text-slate-800">Categorias Cadastradas</h3>
            </div>

            <ul class="divide-y divide-slate-200">
                <?php if (empty($categorias)): ?>
                    <li class="px-6 py-6 text-center text-slate-500">Nenhuma categoria cadastrada ainda.</li>
                <?php else: ?>
                    <?php foreach ($categorias as $c): ?>
                        <li class="px-6 py-4 flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <p class="font-medium text-slate-800"><?= htmlspecialchars($c['nome']) ?></p>
                                <div class="mt-1 flex items-center gap-3">
                                    <span class="text-xs text-slate-400">ID: <?= $c['id'] ?></span>
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-wide <?= ($c['tipo'] ?? '') === 'receita' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' ?>">
                                        <?= htmlspecialchars($c['tipo'] ?? 'sem tipo') ?>
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button" onclick="abrirModalCategoria('modal-categoria-<?= $c['id'] ?>')" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700">
                                    Editar
                                </button>
                                <a href="/financeiro/public/index.php/deletar-categoria?id=<?= $c['id'] ?>" onclick="return confirm('Deseja realmente excluir esta categoria?')" class="inline-flex items-center justify-center rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-medium text-rose-600 transition-colors hover:border-rose-300 hover:bg-rose-100">
                                    Excluir
                                </a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php if (!empty($categorias)): ?>
    <?php foreach ($categorias as $c): ?>
        <div id="modal-categoria-<?= $c['id'] ?>" class="fixed inset-0 z-40 hidden">
            <div class="absolute inset-0 bg-slate-900/60" onclick="fecharModalCategoria('modal-categoria-<?= $c['id'] ?>')"></div>

            <div class="relative z-50 flex min-h-full items-center justify-center p-4">
                <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-2xl">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Editar Categoria</h3>
                            <p class="text-sm text-slate-500 mt-1">Atualize o nome e o tipo da categoria selecionada.</p>
                        </div>
                        <button type="button" onclick="fecharModalCategoria('modal-categoria-<?= $c['id'] ?>')" class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <form action="/financeiro/public/index.php/atualizar-categoria" method="POST" class="px-6 py-5 space-y-5">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Nome</label>
                            <input type="text" name="nome" value="<?= htmlspecialchars($c['nome']) ?>" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <fieldset>
                            <legend class="block text-sm font-medium text-slate-700 mb-2">Tipo</legend>
                            <div class="grid grid-cols-1 gap-3">
                                <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 cursor-pointer hover:border-emerald-300 hover:bg-emerald-50 transition-colors">
                                    <input type="radio" name="tipo" value="receita" <?= ($c['tipo'] ?? '') === 'receita' ? 'checked' : '' ?> required class="h-4 w-4 border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-800">Receita</span>
                                        <span class="block text-xs text-slate-500">Categorias para entradas e ganhos.</span>
                                    </span>
                                </label>

                                <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 cursor-pointer hover:border-rose-300 hover:bg-rose-50 transition-colors">
                                    <input type="radio" name="tipo" value="despesa" <?= ($c['tipo'] ?? '') === 'despesa' ? 'checked' : '' ?> required class="h-4 w-4 border-slate-300 text-rose-600 focus:ring-rose-500">
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-800">Despesa</span>
                                        <span class="block text-xs text-slate-500">Categorias para saídas e custos.</span>
                                    </span>
                                </label>
                            </div>
                        </fieldset>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="button" onclick="fecharModalCategoria('modal-categoria-<?= $c['id'] ?>')" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50">
                                Cancelar
                            </button>
                            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700">
                                Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
    function abrirModalCategoria(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function fecharModalCategoria(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
