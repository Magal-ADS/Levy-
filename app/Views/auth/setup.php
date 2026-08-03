<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuração inicial - Levy</title>
    <link rel="icon" href="<?= htmlspecialchars(asset_url('favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('assets/app.css')) ?>">
</head>
<body class="min-h-screen bg-slate-950 flex items-center justify-center p-4">
    <main class="w-full max-w-lg rounded-2xl bg-white p-8 shadow-2xl">
        <div class="mb-7">
            <div class="text-4xl mb-3">🔐</div>
            <h1 class="text-2xl font-bold text-slate-900">Proteja seus dados atuais</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">Crie o acesso do proprietário. As movimentações existentes continuarão vinculadas a esta conta.</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                Confira os dados. A senha deve ter pelo menos 12 caracteres e as confirmações precisam ser iguais.
            </div>
        <?php endif; ?>

        <form action="<?= htmlspecialchars(app_url('setup')) ?>" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label for="nome" class="mb-1 block text-sm font-medium text-slate-700">Seu nome</label>
                <input id="nome" name="nome" maxlength="100" required autofocus autocomplete="name"
                       class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200">
            </div>
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">E-mail</label>
                <input id="email" name="email" type="email" maxlength="255" required autocomplete="username"
                       class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200">
            </div>
            <div>
                <label for="senha" class="mb-1 block text-sm font-medium text-slate-700">Senha</label>
                <input id="senha" name="senha" type="password" minlength="12" maxlength="255" required autocomplete="new-password"
                       class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200">
                <p class="mt-1 text-xs text-slate-500">Use pelo menos 12 caracteres e não reutilize a senha do Linux.</p>
            </div>
            <div>
                <label for="senha_confirmacao" class="mb-1 block text-sm font-medium text-slate-700">Confirmar senha</label>
                <input id="senha_confirmacao" name="senha_confirmacao" type="password" minlength="12" maxlength="255" required autocomplete="new-password"
                       class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200">
            </div>
            <button class="mt-2 w-full rounded-lg bg-emerald-500 px-4 py-3 font-semibold text-white transition hover:bg-emerald-600">
                Criar acesso e continuar
            </button>
        </form>
    </main>
</body>
</html>
