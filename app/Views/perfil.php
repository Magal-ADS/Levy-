<?php
// app/Views/perfil.php
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Meu Perfil - Levy Finance</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="max-w-4xl mx-auto py-12 px-4">
    <h1 class="text-2xl font-semibold mb-6">Meu Perfil</h1>

    <?php if (isset($_GET['sucesso'])): ?>
      <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800">Dados atualizados com sucesso.</div>
    <?php endif; ?>
    <?php if (isset($_GET['erro'])): ?>
      <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800">Preencha nome e e-mail corretamente.</div>
    <?php endif; ?>

    <form action="/financeiro/public/index.php/atualizar-perfil" method="post" class="space-y-6 bg-white p-6 rounded shadow">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Nome</label>
          <input type="text" name="nome" value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">E-mail</label>
          <input type="email" name="email" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2" />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Salário Base</label>
          <input type="number" step="0.01" name="salario_base" value="<?= htmlspecialchars($usuario['salario_base'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Saldo Inicial do Mês</label>
          <input type="number" step="0.01" name="saldo_inicial_mes" value="<?= htmlspecialchars($usuario['saldo_inicial_mes'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2" />
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Nova Senha <small class="text-sm text-gray-500">(Deixe em branco para manter a senha atual)</small></label>
        <input type="password" name="nova_senha" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2" />
      </div>

      <div class="flex justify-end">
        <button type="submit" class="py-2 px-4 bg-blue-600 text-white rounded">Salvar Alterações</button>
      </div>
    </form>
  </div>
</body>
</html>