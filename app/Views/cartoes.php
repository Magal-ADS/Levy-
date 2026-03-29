<?php require_once __DIR__ . '/partials/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <h2 class="text-2xl font-bold text-slate-800 mb-6">Meus Cartões</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6 border-t-4 border-indigo-500">
            <h3 class="font-bold text-slate-700 mb-4 text-lg">Novo Cartão</h3>
            <form action="/financeiro/public/index.php/cartoes" method="POST">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-600 mb-1">Nome do Cartão</label>
                    <input type="text" name="nome" required placeholder="Ex: Nubank, Inter, Itaú" 
                           class="w-full border-gray-300 border rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 rounded-md transition-colors shadow">
                    Cadastrar
                </button>
            </form>
        </div>

        <div class="col-span-2 bg-white rounded-lg shadow overflow-hidden">
            <div class="bg-slate-50 px-6 py-3 border-b border-slate-200">
                <h3 class="font-bold text-slate-700">Cartões Cadastrados</h3>
            </div>
            <ul class="divide-y divide-slate-100">
                <?php if (empty($cartoes)): ?>
                    <li class="px-6 py-10 text-center text-slate-400 italic">Nenhum cartão cadastrado.</li>
                <?php else: ?>
                    <?php foreach($cartoes as $c): ?>
                        <li class="px-6 py-4 flex justify-between items-center hover:bg-slate-50 transition-colors">
                            <span class="font-medium text-slate-700"><?= htmlspecialchars($c['nome']) ?></span>
                            <span class="bg-slate-100 text-slate-400 text-[10px] px-2 py-1 rounded-full font-bold uppercase tracking-tighter">ID: <?= $c['id'] ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>