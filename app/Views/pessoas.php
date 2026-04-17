<?php require_once __DIR__ . '/partials/header.php'; ?>
<?php $mesAtual = $_GET['mes'] ?? date('Y-m'); ?>

<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Gerenciar Pessoas</h2>
    </div>

    <?php if (isset($_GET['sucesso'])): ?>
    <div class="bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded mb-6 shadow-sm">
        <p>Pessoa cadastrada com sucesso!</p>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="col-span-1">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-slate-800 mb-4">Nova Pessoa</h3>
                <form action="/financeiro/public/index.php/pessoas" method="POST">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nome</label>
                        <input type="text" name="nome" required placeholder="Ex: Gustavo" class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-1">E-mail do Usuário no Sistema (opcional)</label>
                        <input type="email" name="usuario_email" placeholder="email@exemplo.com" class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="text-xs text-slate-400 mt-1 italic">Se preenchido, associa essa pessoa a um usuário do sistema (o usuário receberá a transação compartilhada no seu dashboard).</p>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md transition-colors shadow">
                        Cadastrar
                    </button>
                </form>
            </div>
        </div>

        <div class="col-span-1 md:col-span-2">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-slate-800">Pessoas Cadastradas</h3>
                </div>
                <ul class="divide-y divide-gray-200">
                    <?php if(empty($pessoas)): ?>
                        <li class="px-6 py-4 text-slate-500 text-center">Nenhuma pessoa cadastrada ainda.</li>
                    <?php else: ?>
                        <?php foreach($pessoas as $pessoa): ?>
                            <li class="px-6 py-4 flex items-center justify-between gap-4 hover:bg-slate-50">
                                <div>
                                    <span class="font-medium text-slate-700"><?= htmlspecialchars($pessoa['nome']) ?></span>
                                    <p class="text-xs text-slate-400 mt-1">ID: <?= $pessoa['id'] ?>
                                    <?php if(!empty($pessoa['usuario_nome']) || !empty($pessoa['usuario_email'])): ?>
                                        <br>
                                        <span class="text-[11px] text-slate-500">Vinculado a: <?= htmlspecialchars($pessoa['usuario_nome'] ?? $pessoa['usuario_email']) ?><?= !empty($pessoa['usuario_email']) ? ' (' . htmlspecialchars($pessoa['usuario_email']) . ')' : '' ?></span>
                                    <?php endif; ?>
                                    </p>
                                </div>
                                <a href="/financeiro/public/index.php/relatorio-pessoa?pessoa_id=<?= $pessoa['id'] ?>&mes=<?= urlencode($mesAtual) ?>" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-600 transition-colors hover:border-rose-300 hover:bg-rose-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M7 4h7l5 5v9a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"></path></svg>
                                    PDF
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
