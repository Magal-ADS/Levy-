<?php

declare(strict_types=1);

// ==========================================================
// IMPORTADOR DA FATURA COMPLETA - ABRIL 2026
// ==========================================================

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/config/database.php';

$options = getopt('u:', ['user-email:']) ?: [];
$environmentUserEmail = getenv('IMPORT_USER_EMAIL');
$userEmailInput = $options['user-email'] ?? $options['u'] ?? ($environmentUserEmail !== false ? $environmentUserEmail : '');
$userEmail = strtolower(trim((string) $userEmailInput));
if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php importar_fatura.php --user-email=usuario@exemplo.com\n");
    exit(1);
}

$userStmt = $pdo->prepare(
    "SELECT id FROM usuarios
     WHERE LOWER(email) = ? AND ativo = 1 AND senha_hash IS NOT NULL
     LIMIT 1"
);
$userStmt->execute([$userEmail]);
$usuarioId = $userStmt->fetchColumn();
if ($usuarioId === false) {
    fwrite(STDERR, "Usuário ativo não encontrado para o e-mail informado.\n");
    exit(1);
}
$usuarioId = (int) $usuarioId;

// ==========================================================
// CONFIGURAÇÕES DE IMPORTAÇÃO
// ==========================================================
$cartaoId = 1; // Nubank PF
$mesReferencia = '2026-04';
$dataMovimentacao = '2026-04-10';

$idAnna = 1;
$idLucio = 2;
$idVera = 3;
$idPais = 4;
$idMagal = null; // NULL é a sua parte

$cartaoStmt = $pdo->prepare("SELECT 1 FROM cartoes WHERE id = ? AND usuario_id = ?");
$cartaoStmt->execute([$cartaoId, $usuarioId]);
if (!$cartaoStmt->fetchColumn()) {
    fwrite(STDERR, "O cartão {$cartaoId} não pertence ao usuário informado.\n");
    exit(1);
}

$personIds = array_values(array_filter([$idAnna, $idLucio, $idVera, $idPais], static fn ($id): bool => $id !== null));
$personPlaceholders = implode(', ', array_fill(0, count($personIds), '?'));
$personStmt = $pdo->prepare(
    "SELECT id FROM pessoas WHERE usuario_id = ? AND id IN ({$personPlaceholders})"
);
$personStmt->execute(array_merge([$usuarioId], $personIds));
$ownedPersonIds = array_map('intval', $personStmt->fetchAll(PDO::FETCH_COLUMN));
$missingPersonIds = array_values(array_diff($personIds, $ownedPersonIds));
if ($missingPersonIds !== []) {
    fwrite(STDERR, 'Pessoas ausentes ou pertencentes a outro usuário: ' . implode(', ', $missingPersonIds) . "\n");
    exit(1);
}

// ==========================================================
// LISTA COMPLETA DE GASTOS (TODAS AS IMAGENS)
// ==========================================================
$gastos = [
    // --- IMAGEM 1 ---
    ['descricao' => 'AUTO POSTO (MOTO)', 'valor' => 62.04, 'divisoes' => [['pessoa' => $idAnna, 'valor' => 31.02], ['pessoa' => $idMagal, 'valor' => 31.02]]],
    ['descricao' => 'IQUEGAMI (BANANA E LEITE)', 'valor' => 10.46, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 10.46]]],
    ['descricao' => 'DANADELA', 'valor' => 78.31, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 41.55], ['pessoa' => $idAnna, 'valor' => 36.76]]],
    ['descricao' => 'OCULOS', 'valor' => 60.00, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 60.00]]],
    ['descricao' => 'CELULAR MÃE', 'valor' => 141.58, 'divisoes' => [['pessoa' => $idPais, 'valor' => 141.58]]],
    ['descricao' => 'ELENA MARIA DA SILVA (SORVETE VÓ)', 'valor' => 34.18, 'divisoes' => [['pessoa' => $idAnna, 'valor' => 17.09], ['pessoa' => $idMagal, 'valor' => 17.09]]],
    ['descricao' => 'REGINALDO CARDOSO', 'valor' => 66.93, 'divisoes' => [['pessoa' => $idAnna, 'valor' => 33.46], ['pessoa' => $idMagal, 'valor' => 33.47]]],
    ['descricao' => 'DANIELLI COOKIE RIZZO', 'valor' => 14.90, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 14.90]]],
    ['descricao' => 'INSOCIAL', 'valor' => 10.78, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 10.78]]],
    ['descricao' => 'EMERSON (PNEU MOTO)', 'valor' => 47.76, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 47.76]]],
    ['descricao' => 'AUTO POSTO (CARRO)', 'valor' => 34.29, 'divisoes' => [['pessoa' => $idAnna, 'valor' => 17.14], ['pessoa' => $idMagal, 'valor' => 17.15]]],
    ['descricao' => 'DANIELLI RIZZO', 'valor' => 93.62, 'divisoes' => [['pessoa' => $idAnna, 'valor' => 46.81], ['pessoa' => $idMagal, 'valor' => 46.81]]],
    ['descricao' => 'CAFE DONA ALICA (PAMELA)', 'valor' => 50.25, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 50.25]]],
    ['descricao' => 'RALLY AUTO POSTO', 'valor' => 6.00, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 6.00]]],
    ['descricao' => 'SHIRLEY CACHORRO (FACUL)', 'valor' => 18.00, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 18.00]]],
    ['descricao' => 'CASA BALNCA (CAIXA E SACO)', 'valor' => 42.40, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 42.40]]],
    ['descricao' => 'AÇAI DIA QUE ANNA COMEU NO RIZZO', 'valor' => 44.04, 'divisoes' => [['pessoa' => $idAnna, 'valor' => 22.02], ['pessoa' => $idMagal, 'valor' => 22.02]]],
    ['descricao' => 'SERV BEM (CAFÉ DA MANHÃ)', 'valor' => 39.04, 'divisoes' => [['pessoa' => $idAnna, 'valor' => 19.52], ['pessoa' => $idMagal, 'valor' => 19.52]]],
    ['descricao' => 'DIEGO SCAVONI (SONHOS)', 'valor' => 14.00, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 14.00]]],
    ['descricao' => 'AMARELINHA', 'valor' => 50.13, 'divisoes' => [['pessoa' => $idAnna, 'valor' => 25.06], ['pessoa' => $idMagal, 'valor' => 25.07]]],
    ['descricao' => 'SACOLÃO SB', 'valor' => 162.47, 'divisoes' => [['pessoa' => $idPais, 'valor' => 120.00], ['pessoa' => $idMagal, 'valor' => 42.47]]],
    ['descricao' => 'AUTO POSTO BELA VISTA (CARRO)', 'valor' => 10.01, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 10.01]]],
    ['descricao' => 'SAVEGNAGO', 'valor' => 81.13, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 81.13]]],
    ['descricao' => 'DONA ALICE', 'valor' => 16.65, 'divisoes' => [['pessoa' => $idAnna, 'valor' => 16.65]]],
    ['descricao' => 'AMAZON PRIME', 'valor' => 19.90, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 19.90]]],
    ['descricao' => 'CARTÓRIO MÃE', 'valor' => 55.00, 'divisoes' => [['pessoa' => $idPais, 'valor' => 55.00]]],
    ['descricao' => 'INSOCIAL', 'valor' => 11.00, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 11.00]]],

    // --- IMAGEM 2 ---
    ['descricao' => 'NETFLIX', 'valor' => 20.90, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 20.90]]],
    ['descricao' => 'CELIA REGINA', 'valor' => 11.90, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 11.90]]],
    ['descricao' => 'SPOTIFY', 'valor' => 12.90, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 12.90]]],
    ['descricao' => 'DANIELI RIZZO', 'valor' => 40.80, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 40.80]]],
    ['descricao' => 'DENADELA (PRESENTE DANI)', 'valor' => 50.50, 'divisoes' => [['pessoa' => $idAnna, 'valor' => 25.25], ['pessoa' => $idMagal, 'valor' => 25.25]]],
    ['descricao' => 'SAVEGNAGO (FRALDA)', 'valor' => 42.90, 'divisoes' => [['pessoa' => $idAnna, 'valor' => 21.45], ['pessoa' => $idMagal, 'valor' => 21.45]]],
    ['descricao' => 'SERV BEM (CAFÉ DA MANHÃ BANDA)', 'valor' => 36.32, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 36.32]]],
    ['descricao' => 'SAVEGNAGO FRANGO', 'valor' => 156.80, 'divisoes' => [['pessoa' => $idPais, 'valor' => 100.00], ['pessoa' => $idMagal, 'valor' => 28.40], ['pessoa' => $idAnna, 'valor' => 28.40]]],
    ['descricao' => 'CAPINHA SHOPPE', 'valor' => 19.90, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 19.90]]],
    ['descricao' => 'ACADEMIA', 'valor' => 130.00, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 130.00]]],
    ['descricao' => 'COISA DOS GATOS', 'valor' => 34.00, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 34.00]]],
    ['descricao' => 'SAVEGNAGO (CHOCOLATE QUENTE)', 'valor' => 48.16, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 48.16]]],
    ['descricao' => 'POSTO CARRO FACULDADE', 'valor' => 35.00, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 35.00]]],
    ['descricao' => 'CAMISA MAXIMIZE', 'valor' => 153.90, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 153.90]]],
    ['descricao' => 'PAYGO LSILVA RESTAURA', 'valor' => 24.00, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 24.00]]],
    ['descricao' => 'DIEGO SCAVONI', 'valor' => 5.00, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 5.00]]],
    ['descricao' => 'AUTO POSTO BELA VISTA - PAIS', 'valor' => 50.00, 'divisoes' => [['pessoa' => $idPais, 'valor' => 50.00]]],
    ['descricao' => 'AUTO POSTO BEL VISTA', 'valor' => 20.00, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 20.00]]],
    ['descricao' => 'SERV BEM', 'valor' => 17.48, 'divisoes' => [['pessoa' => $idAnna, 'valor' => 8.74], ['pessoa' => $idMagal, 'valor' => 8.74]]],
    ['descricao' => 'FRANGO FRITO - AÇAI LACREME', 'valor' => 49.50, 'divisoes' => [['pessoa' => $idAnna, 'valor' => 24.75], ['pessoa' => $idMagal, 'valor' => 24.75]]],
    ['descricao' => 'DANIELLI - RIZZO', 'valor' => 13.44, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 13.44]]],
    ['descricao' => 'AMERELINHA -', 'valor' => 15.55, 'divisoes' => [['pessoa' => $idAnna, 'valor' => 7.77], ['pessoa' => $idMagal, 'valor' => 7.78]]],
    ['descricao' => 'DANIELLI RIZZO - ANNA E MAGAL', 'valor' => 80.36, 'divisoes' => [['pessoa' => $idAnna, 'valor' => 40.18], ['pessoa' => $idMagal, 'valor' => 40.18]]],
    ['descricao' => 'IOF', 'valor' => 0.81, 'divisoes' => [['pessoa' => $idPais, 'valor' => 0.81]]],
    ['descricao' => 'STEAM', 'valor' => 23.26, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 23.26]]],
    ['descricao' => 'SERV BEM', 'valor' => 10.00, 'divisoes' => [['pessoa' => $idLucio, 'valor' => 10.00]]],
    ['descricao' => 'BOM GOSTO - SALGADO', 'valor' => 34.00, 'divisoes' => [['pessoa' => $idMagal, 'valor' => 34.00]]],
];

// ==========================================================
// PROCESSO DE INSERÇÃO
// ==========================================================
echo "\nIniciando importação de 54 registros para $mesReferencia...\n";
$pdo->beginTransaction();

try {
    $stmtT = $pdo->prepare(
        "INSERT INTO transacoes
         (usuario_id, descricao, valor_total, tipo, data_movimentacao, mes_referencia, cartao_id)
         VALUES (?, ?, ?, 'despesa', ?, ?, ?)
         RETURNING id"
    );
    $stmtD = $pdo->prepare(
        "INSERT INTO divisoes_transacao
         (usuario_id, transacao_id, pessoa_id, valor_divisao, status_pago)
         VALUES (?, ?, ?, ?, 0)"
    );

    foreach ($gastos as $g) {
        $stmtT->execute([$usuarioId, $g['descricao'], $g['valor'], $dataMovimentacao, $mesReferencia, $cartaoId]);
        $tid = (int) $stmtT->fetchColumn();

        foreach ($g['divisoes'] as $d) {
            $stmtD->execute([$usuarioId, $tid, $d['pessoa'], $d['valor']]);
        }
        echo "✅ OK: {$g['descricao']}\n";
    }

    $pdo->commit();
    echo "\n🚀 TUDO PRONTO! 54 transações importadas com sucesso.\n";

} catch (Exception $e) {
    $pdo->rollBack();
    fwrite(STDERR, "❌ ERRO: " . $e->getMessage() . "\n");
    exit(1);
}
