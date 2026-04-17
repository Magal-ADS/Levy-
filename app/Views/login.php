<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login - Levy Finance</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 h-screen w-screen flex items-center justify-center">
  <div class="max-w-md w-full bg-white p-8 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-6 text-center">Entrar no Levy Finance</h1>
    <?php if (isset($_GET['erro'])): ?>
      <div class="mb-4 text-sm text-red-600">Credenciais inválidas. Tente novamente.</div>
    <?php endif; ?>
    <form action="/financeiro/public/index.php/autenticar" method="post" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">E-mail</label>
        <input type="email" name="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Senha</label>
        <input type="password" name="senha" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
      </div>
      <div>
        <button type="submit" class="w-full py-2 px-4 bg-blue-600 text-white rounded-md">Entrar</button>
      </div>
    </form>
  </div>
</body>
</html>