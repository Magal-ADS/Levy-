<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

function addConstraintIfMissing(PDO $pdo, string $table, string $name, string $definition): void
{
    $sql = sprintf(
        "DO \$migration\$ BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_constraint
                WHERE conname = %s AND conrelid = %s::regclass
            ) THEN
                ALTER TABLE %s ADD CONSTRAINT %s %s;
            END IF;
        END \$migration\$;",
        $pdo->quote($name),
        $pdo->quote($table),
        $table,
        $name,
        $definition
    );

    $pdo->exec($sql);
}

function logMigration(string $message): void
{
    echo $message . PHP_EOL;
}

try {
    $pdo->beginTransaction();

    $tables = [
        'usuarios' => "
            CREATE TABLE IF NOT EXISTS usuarios (
                id SERIAL PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                salario_base DECIMAL(10, 2) DEFAULT 0.00,
                saldo_inicial_mes DECIMAL(10, 2) DEFAULT 0.00,
                email VARCHAR(255),
                senha_hash VARCHAR(255),
                ativo SMALLINT NOT NULL DEFAULT 1,
                is_admin SMALLINT NOT NULL DEFAULT 0,
                criado_em TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ",
        'pessoas' => "
            CREATE TABLE IF NOT EXISTS pessoas (
                id SERIAL PRIMARY KEY,
                nome VARCHAR(100) NOT NULL
            )
        ",
        'cartoes' => "
            CREATE TABLE IF NOT EXISTS cartoes (
                id SERIAL PRIMARY KEY,
                nome_cartao VARCHAR(50),
                nome VARCHAR(100) NOT NULL
            )
        ",
        'categorias' => "
            CREATE TABLE IF NOT EXISTS categorias (
                id SERIAL PRIMARY KEY,
                nome VARCHAR(50) NOT NULL,
                tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('receita', 'despesa'))
            )
        ",
        'transacoes' => "
            CREATE TABLE IF NOT EXISTS transacoes (
                id SERIAL PRIMARY KEY,
                descricao VARCHAR(255) NOT NULL,
                valor_total DECIMAL(10, 2) NOT NULL,
                tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('receita', 'despesa')),
                data_movimentacao DATE NOT NULL,
                mes_referencia VARCHAR(7) NOT NULL,
                categoria_id INT REFERENCES categorias(id) ON DELETE SET NULL,
                cartao_id INT REFERENCES cartoes(id) ON DELETE SET NULL,
                hash_parcelamento VARCHAR(50) NULL
            )
        ",
        'divisoes_transacao' => "
            CREATE TABLE IF NOT EXISTS divisoes_transacao (
                id SERIAL PRIMARY KEY,
                transacao_id INT NOT NULL REFERENCES transacoes(id) ON DELETE CASCADE,
                pessoa_id INT REFERENCES pessoas(id) ON DELETE SET NULL,
                valor_divisao DECIMAL(10, 2) NOT NULL,
                status_pago SMALLINT DEFAULT 0
            )
        ",
        'contas_fixas' => "
            CREATE TABLE IF NOT EXISTS contas_fixas (
                id SERIAL PRIMARY KEY,
                descricao VARCHAR(255) NOT NULL,
                valor_estimado DECIMAL(10, 2) NOT NULL,
                dia_vencimento INT NOT NULL,
                categoria_id INT REFERENCES categorias(id) ON DELETE SET NULL,
                cartao_id INT REFERENCES cartoes(id) ON DELETE SET NULL,
                tipo_pagamento VARCHAR(20) DEFAULT 'manual' CHECK (tipo_pagamento IN ('automatico', 'manual')),
                ativo SMALLINT DEFAULT 1
            )
        ",
        'tentativas_login' => "
            CREATE TABLE IF NOT EXISTS tentativas_login (
                chave CHAR(64) PRIMARY KEY,
                tentativas INT NOT NULL DEFAULT 0,
                primeira_tentativa TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
                bloqueado_ate TIMESTAMPTZ,
                atualizado_em TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ",
    ];

    foreach ($tables as $name => $sql) {
        $pdo->exec($sql);
        logMigration("[OK] Tabela: {$name}");
    }

    // Colunas de autenticação para bancos criados antes do modo multiusuário.
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS email VARCHAR(255)");
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS senha_hash VARCHAR(255)");
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS ativo SMALLINT NOT NULL DEFAULT 1");
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS is_admin SMALLINT NOT NULL DEFAULT 0");
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS criado_em TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP");
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS atualizado_em TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP");

    $ownerId = $pdo->query("SELECT id FROM usuarios ORDER BY id ASC LIMIT 1")->fetchColumn();
    if (!$ownerId) {
        $ownerId = $pdo->query(
            "INSERT INTO usuarios (nome, salario_base, saldo_inicial_mes, is_admin)
             VALUES ('Levy', 2300.00, 0.00, 1)
             RETURNING id"
        )->fetchColumn();
        logMigration('[OK] Usuário proprietário inicial criado');
    }
    $ownerId = (int) $ownerId;
    $pdo->prepare("UPDATE usuarios SET is_admin = 1 WHERE id = ?")->execute([$ownerId]);

    $ownedTables = ['pessoas', 'cartoes', 'categorias', 'transacoes', 'divisoes_transacao', 'contas_fixas'];
    foreach ($ownedTables as $table) {
        $pdo->exec("ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS usuario_id INT");
    }

    // Primeiro preserva o dono das divisões pela transação. O fallback cobre bases legadas.
    foreach (['pessoas', 'cartoes', 'categorias', 'transacoes', 'contas_fixas'] as $table) {
        $stmt = $pdo->prepare("UPDATE {$table} SET usuario_id = ? WHERE usuario_id IS NULL");
        $stmt->execute([$ownerId]);
    }
    $pdo->exec(
        "UPDATE divisoes_transacao dt
         SET usuario_id = t.usuario_id
         FROM transacoes t
         WHERE dt.transacao_id = t.id AND dt.usuario_id IS NULL"
    );
    $stmt = $pdo->prepare("UPDATE divisoes_transacao SET usuario_id = ? WHERE usuario_id IS NULL");
    $stmt->execute([$ownerId]);

    foreach ($ownedTables as $table) {
        addConstraintIfMissing(
            $pdo,
            $table,
            "fk_{$table}_usuario",
            'FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT'
        );
        $pdo->exec("ALTER TABLE {$table} ALTER COLUMN usuario_id SET NOT NULL");
    }

    // As chaves compostas impedem que um registro aponte para cadastros de outro usuário.
    foreach (['categorias', 'cartoes', 'pessoas', 'transacoes'] as $table) {
        addConstraintIfMissing($pdo, $table, "uq_{$table}_id_usuario", 'UNIQUE (id, usuario_id)');
    }
    addConstraintIfMissing(
        $pdo,
        'transacoes',
        'fk_transacoes_categoria_usuario',
        'FOREIGN KEY (categoria_id, usuario_id) REFERENCES categorias(id, usuario_id)'
    );
    addConstraintIfMissing(
        $pdo,
        'transacoes',
        'fk_transacoes_cartao_usuario',
        'FOREIGN KEY (cartao_id, usuario_id) REFERENCES cartoes(id, usuario_id)'
    );
    addConstraintIfMissing(
        $pdo,
        'contas_fixas',
        'fk_contas_fixas_categoria_usuario',
        'FOREIGN KEY (categoria_id, usuario_id) REFERENCES categorias(id, usuario_id)'
    );
    addConstraintIfMissing(
        $pdo,
        'contas_fixas',
        'fk_contas_fixas_cartao_usuario',
        'FOREIGN KEY (cartao_id, usuario_id) REFERENCES cartoes(id, usuario_id)'
    );
    addConstraintIfMissing(
        $pdo,
        'divisoes_transacao',
        'fk_divisoes_transacao_usuario',
        'FOREIGN KEY (transacao_id, usuario_id) REFERENCES transacoes(id, usuario_id) ON DELETE CASCADE'
    );
    addConstraintIfMissing(
        $pdo,
        'divisoes_transacao',
        'fk_divisoes_pessoa_usuario',
        'FOREIGN KEY (pessoa_id, usuario_id) REFERENCES pessoas(id, usuario_id)'
    );

    $indexes = [
        'uq_usuarios_email' => "CREATE UNIQUE INDEX IF NOT EXISTS uq_usuarios_email ON usuarios (LOWER(email)) WHERE email IS NOT NULL",
        'idx_transacoes_usuario_mes_data' => "CREATE INDEX IF NOT EXISTS idx_transacoes_usuario_mes_data ON transacoes (usuario_id, mes_referencia, data_movimentacao DESC)",
        'idx_divisoes_usuario_transacao' => "CREATE INDEX IF NOT EXISTS idx_divisoes_usuario_transacao ON divisoes_transacao (usuario_id, transacao_id)",
        'idx_divisoes_usuario_pessoa_status' => "CREATE INDEX IF NOT EXISTS idx_divisoes_usuario_pessoa_status ON divisoes_transacao (usuario_id, pessoa_id, status_pago)",
        'idx_categorias_usuario_nome' => "CREATE INDEX IF NOT EXISTS idx_categorias_usuario_nome ON categorias (usuario_id, nome)",
        'idx_cartoes_usuario_nome' => "CREATE INDEX IF NOT EXISTS idx_cartoes_usuario_nome ON cartoes (usuario_id, nome)",
        'idx_pessoas_usuario_nome' => "CREATE INDEX IF NOT EXISTS idx_pessoas_usuario_nome ON pessoas (usuario_id, nome)",
        'idx_contas_fixas_usuario_ativo' => "CREATE INDEX IF NOT EXISTS idx_contas_fixas_usuario_ativo ON contas_fixas (usuario_id, ativo, dia_vencimento)",
        'idx_tentativas_login_atualizado' => "CREATE INDEX IF NOT EXISTS idx_tentativas_login_atualizado ON tentativas_login (atualizado_em)",
    ];
    foreach ($indexes as $name => $sql) {
        $pdo->exec($sql);
        logMigration("[OK] Índice: {$name}");
    }

    $countCategories = $pdo->prepare("SELECT COUNT(*) FROM categorias WHERE usuario_id = ?");
    $countCategories->execute([$ownerId]);
    if ((int) $countCategories->fetchColumn() === 0) {
        $seed = $pdo->prepare(
            "INSERT INTO categorias (usuario_id, nome, tipo) VALUES
             (?, 'Alimentação', 'despesa'),
             (?, 'Moradia', 'despesa'),
             (?, 'Salário', 'receita'),
             (?, 'Lazer', 'despesa')"
        );
        $seed->execute([$ownerId, $ownerId, $ownerId, $ownerId]);
        logMigration('[OK] Categorias iniciais criadas');
    }

    $pdo->commit();
    logMigration('✅ Migração multiusuário concluída com sucesso.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, '[ERRO] Migração cancelada e revertida: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
