<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar - Levy</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('assets/app.css')) ?>">
</head>
<body class="min-h-screen bg-slate-950 flex items-center justify-center p-4">
    <main class="w-full max-w-md rounded-2xl bg-white p-8 shadow-2xl">
        <div class="mb-8 text-center">
            <div class="text-4xl mb-3">💸</div>
            <h1 class="text-2xl font-bold text-slate-900">Entrar no Levy</h1>
            <p class="mt-2 text-sm text-slate-500">Acesse somente suas movimentações financeiras.</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                <?= $error === 'bloqueado'
                    ? 'Muitas tentativas. Aguarde 15 minutos e tente novamente.'
                    : 'E-mail ou senha inválidos.' ?>
            </div>
        <?php endif; ?>

        <form action="<?= htmlspecialchars(app_url('login')) ?>" method="POST" class="space-y-5">
            <?= csrf_field() ?>
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">E-mail</label>
                <input id="email" name="email" type="email" autocomplete="username" required autofocus
                       class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200">
            </div>
            <div>
                <label for="senha" class="mb-1 block text-sm font-medium text-slate-700">Senha</label>
                <input id="senha" name="senha" type="password" autocomplete="current-password" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200">
            </div>
            <button class="w-full rounded-lg bg-emerald-500 px-4 py-3 font-semibold text-white transition hover:bg-emerald-600">
                Entrar
            </button>
        </form>
    </main>
</body>
</html>
