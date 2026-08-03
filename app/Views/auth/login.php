<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#020617">
    <title>Entrar - Levy</title>
    <link rel="icon" href="<?= htmlspecialchars(asset_url('favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('assets/app.css')) ?>">
</head>
<body class="min-h-screen bg-slate-950 text-slate-900 antialiased">
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden p-4 sm:p-6 lg:p-10">
        <div aria-hidden="true" class="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-emerald-500/20 blur-3xl sm:h-96 sm:w-96"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -bottom-32 -right-24 h-80 w-80 rounded-full bg-indigo-500/20 blur-3xl sm:h-[30rem] sm:w-[30rem]"></div>

        <main class="relative grid w-full max-w-6xl overflow-hidden rounded-3xl border border-white/10 bg-white shadow-2xl shadow-black/40 lg:min-h-[680px] lg:grid-cols-[0.9fr_1.1fr]">
            <section class="relative hidden overflow-hidden bg-slate-900 p-12 text-white lg:flex lg:flex-col lg:justify-between">
                <div aria-hidden="true" class="absolute inset-0 bg-gradient-to-br from-emerald-400/20 via-transparent to-indigo-500/20"></div>
                <div aria-hidden="true" class="absolute -right-24 top-24 h-64 w-64 rounded-full border border-emerald-300/20"></div>
                <div aria-hidden="true" class="absolute -right-12 top-36 h-64 w-64 rounded-full border border-emerald-300/10"></div>

                <div class="relative flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-400 text-xl shadow-lg shadow-emerald-500/20">💸</span>
                    <span class="text-xl font-bold tracking-tight">Levy</span>
                </div>

                <div class="relative max-w-md">
                    <p class="mb-5 inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-emerald-300">
                        Finanças sem complicação
                    </p>
                    <h1 class="text-4xl font-bold leading-tight tracking-tight xl:text-5xl">
                        Clareza para cuidar melhor do seu dinheiro.
                    </h1>
                    <p class="mt-6 max-w-sm text-base leading-7 text-slate-300">
                        Acompanhe suas contas, organize os gastos e entenda seu mês em um só lugar.
                    </p>
                </div>

                <div class="relative flex items-center gap-3 text-sm text-slate-400">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/5">
                        <svg class="h-4 w-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </span>
                    Seus dados ficam separados e protegidos.
                </div>
            </section>

            <section class="flex items-center bg-white px-5 py-8 sm:px-10 sm:py-12 lg:px-16 xl:px-24">
                <div class="mx-auto w-full max-w-md">
                    <div class="mb-8 flex items-center gap-3 lg:hidden">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-500 text-xl shadow-lg shadow-emerald-500/20">💸</span>
                        <span class="text-xl font-bold tracking-tight text-slate-900">Levy</span>
                    </div>

                    <div class="mb-8 sm:mb-10">
                        <p class="mb-2 text-sm font-semibold text-emerald-600">Bem-vindo de volta</p>
                        <h2 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Entre na sua conta</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-500 sm:text-base">Use seu e-mail e senha para acessar suas movimentações.</p>
                    </div>

                    <?php if ($error): ?>
                        <div role="alert" aria-live="polite" class="mb-6 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-2.99L13.74 4a2 2 0 00-3.48 0L3.33 16.01A2 2 0 005.07 19z"></path>
                            </svg>
                            <span><?= $error === 'bloqueado'
                                ? 'Muitas tentativas. Aguarde 15 minutos e tente novamente.'
                                : 'E-mail ou senha inválidos.' ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="<?= htmlspecialchars(app_url('login')) ?>" method="POST" class="space-y-5">
                        <?= csrf_field() ?>
                        <div>
                            <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">E-mail</label>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8m-16 10h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                <input id="email" name="email" type="email" autocomplete="username" required autofocus placeholder="voce@exemplo.com"
                                       class="w-full rounded-xl border border-slate-300 bg-slate-50 py-3.5 pl-12 pr-4 text-base text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                            </div>
                        </div>

                        <div>
                            <label for="senha" class="mb-2 block text-sm font-semibold text-slate-700">Senha</label>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                <input id="senha" name="senha" type="password" autocomplete="current-password" required placeholder="Digite sua senha"
                                       class="w-full rounded-xl border border-slate-300 bg-slate-50 py-3.5 pl-12 pr-12 text-base text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                                <button type="button" id="toggle-password" class="absolute right-2 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-200 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500" aria-label="Mostrar senha" aria-pressed="false">
                                    <svg id="eye-open" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c-1.5 4-4.5 6-9 6s-7.5-2-9-6c1.5-4 4.5-6 9-6s7.5 2 9 6z"></path>
                                    </svg>
                                    <svg id="eye-closed" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.6 10.6A2 2 0 0012 14a2 2 0 001.4-.6M9.9 5.1A10 10 0 0112 5c4.5 0 7.5 3 9 7a11.8 11.8 0 01-2.1 3.5M6.2 6.2A11.7 11.7 0 003 12c1.5 4 4.5 7 9 7 1.4 0 2.7-.3 3.8-.8"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <button class="group flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-3.5 font-semibold text-white shadow-lg shadow-slate-900/10 transition hover:bg-emerald-600 hover:shadow-emerald-600/20 focus:outline-none focus:ring-4 focus:ring-emerald-500/20 active:translate-y-px">
                            Entrar
                            <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </button>
                    </form>

                    <p class="mt-8 text-center text-xs leading-5 text-slate-400">
                        Acesso protegido por sessão segura e limite de tentativas.
                    </p>
                </div>
            </section>
        </main>
    </div>

    <script>
        const togglePassword = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('senha');
        const eyeOpen = document.getElementById('eye-open');
        const eyeClosed = document.getElementById('eye-closed');

        togglePassword.addEventListener('click', () => {
            const passwordVisible = passwordInput.type === 'text';
            passwordInput.type = passwordVisible ? 'password' : 'text';
            togglePassword.setAttribute('aria-label', passwordVisible ? 'Mostrar senha' : 'Ocultar senha');
            togglePassword.setAttribute('aria-pressed', passwordVisible ? 'false' : 'true');
            eyeOpen.classList.toggle('hidden', !passwordVisible);
            eyeClosed.classList.toggle('hidden', passwordVisible);
        });
    </script>
</body>
</html>
