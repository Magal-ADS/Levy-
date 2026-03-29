<?php require_once __DIR__ . '/partials/header.php'; ?>
<div class="max-w-4xl mx-auto">
    <h2 class="text-2xl font-bold text-slate-800 mb-6">Categorias</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-medium mb-4">Nova Categoria</h3>
            <form action="/financeiro/public/index.php/categorias" method="POST">
                <input type="text" name="nome" placeholder="Ex: Alimentação" class="w-full border rounded-md p-2 mb-4">
                <button class="w-full bg-indigo-600 text-white py-2 rounded-md">Cadastrar</button>
            </form>
        </div>
        <div class="col-span-2 bg-white rounded-lg shadow overflow-hidden">
            <ul class="divide-y">
                <?php foreach($categorias as $c): ?>
                    <li class="px-6 py-4 flex justify-between"><?= $c['nome'] ?> <span class="text-xs text-slate-400">ID: <?= $c['id'] ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>