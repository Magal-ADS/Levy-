<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Levy - Controle Financeiro</title>
    
    <link rel="manifest" href="/financeiro/public/manifest.json">
    <meta name="theme-color" content="#4f46e5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="https://cdn-icons-png.flaticon.com/512/5501/5501375.png">

    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        /* Ajuste para evitar o "pulo" visual no mobile antes do Tailwind carregar */
        [id="sidebar"] { transition: transform 0.3s ease-in-out; }
        
        /* Customização da scrollbar da sidebar */
        #sidebar-nav::-webkit-scrollbar { width: 4px; }
        #sidebar-nav::-webkit-scrollbar-track { background: transparent; }
        #sidebar-nav::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-800 flex h-screen overflow-hidden">

    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden md:hidden" onclick="toggleSidebar()"></div>

    <aside id="sidebar" class="bg-slate-900 text-white w-64 flex-shrink-0 fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 z-30 flex flex-col shadow-2xl md:shadow-none">
        
        <div class="h-16 flex items-center px-6 border-b border-slate-700 bg-slate-900">
            <h1 class="text-xl font-bold tracking-wider text-emerald-400 flex items-center gap-2">
                <span>💸</span> Levy
            </h1>
        </div>

        <nav id="sidebar-nav" class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
            
            <a href="/financeiro/public/index.php" class="flex items-center px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg transition-colors group">
                <span class="flex items-center gap-3">
                    <svg class="w-5 h-5 group-hover:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Dashboard
                </span>
            </a>

            <a href="/financeiro/public/index.php/transacoes" class="flex items-center px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg transition-colors group">
                <span class="flex items-center gap-3">
                    <svg class="w-5 h-5 group-hover:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                    Relatórios / Gráficos
                </span>
            </a>

            <a href="/financeiro/public/index.php/contas-fixas" class="flex items-center px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg transition-colors group bg-slate-800/30">
                <span class="flex items-center gap-3">
                    <svg class="w-5 h-5 group-hover:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Contas Fixas
                </span>
            </a>

            <a href="/financeiro/public/index.php/nova-conta" class="flex items-center px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg transition-colors group">
                <span class="flex items-center gap-3">
                    <svg class="w-5 h-5 group-hover:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Nova Conta
                </span>
            </a>

            <a href="/financeiro/public/index.php/recebimentos" class="flex items-center px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg transition-colors group">
                <span class="flex items-center gap-3">
                    <svg class="w-5 h-5 group-hover:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Recebimentos
                </span>
            </a>

            <div class="pt-6 pb-2">
                <p class="text-[10px] uppercase text-slate-500 font-bold px-4 tracking-widest">Cadastros Base</p>
            </div>

            <a href="/financeiro/public/index.php/categorias" class="flex items-center px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg transition-colors group">
                <span class="flex items-center gap-3">
                    <svg class="w-5 h-5 group-hover:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    Categorias
                </span>
            </a>

            <a href="/financeiro/public/index.php/cartoes" class="flex items-center px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg transition-colors group">
                <span class="flex items-center gap-3">
                    <svg class="w-5 h-5 group-hover:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    Cartões
                </span>
            </a>

            <a href="/financeiro/public/index.php/pessoas" class="flex items-center px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg transition-colors group">
                <span class="flex items-center gap-3">
                    <svg class="w-5 h-5 group-hover:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Amigos
                </span>
            </a>

            <div class="pt-6 pb-2 border-t border-slate-800 mt-4">
                <a href="/financeiro/public/index.php/configuracoes" class="flex items-center px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg transition-colors group">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5 group-hover:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37a1.724 1.724 0 002.572-1.065z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        Configurações
                    </span>
                </a>
            </div>
        </nav>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden w-full">
        
        <header class="md:hidden bg-slate-900 text-white h-16 flex items-center justify-between px-4 z-10 shadow-md">
            <h1 class="text-lg font-bold text-emerald-400">💸 Levy</h1>
            <button onclick="toggleSidebar()" class="text-slate-300 hover:text-white focus:outline-none p-2">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-100 p-4 md:p-8">

    <script>
        // Função para abrir/fechar sidebar no mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        // Registro do Service Worker (PWA)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/financeiro/public/sw.js')
                    .then(reg => console.log('PWA: Service Worker ativo!', reg.scope))
                    .catch(err => console.log('PWA: Erro ao registrar SW:', err));
            });
        }
    </script>