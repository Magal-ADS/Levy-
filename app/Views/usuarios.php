<?php $pageTitle = 'Usuários'; require __DIR__ . '/partials/header.php'; ?>

<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-900">Usuários</h2>
        <p class="mt-1 text-sm text-slate-500">Cada usuário possui movimentações e cadastros completamente separados.</p>
    </div>

    <?php if ($error): ?>
        <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            Não foi possível concluir. Confira o e-mail, a confirmação e use uma senha com pelo menos 12 caracteres.
        </div>
    <?php elseif (!empty($_GET['sucesso'])): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">Alteração realizada com sucesso.</div>
    <?php endif; ?>

    <section class="rounded-xl bg-white p-6 shadow-sm">
        <h3 class="mb-5 text-lg font-semibold">Criar usuário</h3>
        <form action="<?= htmlspecialchars(app_url('usuarios')) ?>" method="POST" class="grid gap-4 md:grid-cols-2">
            <?= csrf_field() ?>
            <input name="nome" required maxlength="100" placeholder="Nome" autocomplete="off" class="rounded-lg border border-slate-300 px-4 py-3">
            <input name="email" required type="email" maxlength="255" placeholder="E-mail" autocomplete="off" class="rounded-lg border border-slate-300 px-4 py-3">
            <input name="senha" required type="password" minlength="12" maxlength="255" placeholder="Senha temporária (12+ caracteres)" autocomplete="new-password" class="rounded-lg border border-slate-300 px-4 py-3">
            <input name="senha_confirmacao" required type="password" minlength="12" maxlength="255" placeholder="Confirmar senha" autocomplete="new-password" class="rounded-lg border border-slate-300 px-4 py-3">
            <button class="md:col-span-2 rounded-lg bg-emerald-500 px-5 py-3 font-semibold text-white hover:bg-emerald-600">Criar usuário</button>
        </form>
    </section>

    <section class="overflow-hidden rounded-xl bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600"><tr><th class="p-4">Nome</th><th class="p-4">E-mail</th><th class="p-4">Perfil</th><th class="p-4">Status</th><th class="p-4 text-right">Ação</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td class="p-4 font-medium text-slate-900"><?= htmlspecialchars($usuario['nome']) ?></td>
                        <td class="p-4 text-slate-600"><?= htmlspecialchars($usuario['email']) ?></td>
                        <td class="p-4"><?= (int) $usuario['is_admin'] === 1 ? 'Administrador' : 'Usuário' ?></td>
                        <td class="p-4"><?= (int) $usuario['ativo'] === 1 ? 'Ativo' : 'Inativo' ?></td>
                        <td class="p-4 text-right">
                            <?php if ((int) $usuario['id'] !== current_user_id() && (int) $usuario['is_admin'] !== 1): ?>
                                <form action="<?= htmlspecialchars(app_url('alternar-status-usuario')) ?>" method="POST" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">
                                    <button class="rounded-lg border border-slate-300 px-3 py-1.5 hover:bg-slate-50"><?= (int) $usuario['ativo'] === 1 ? 'Desativar' : 'Ativar' ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
