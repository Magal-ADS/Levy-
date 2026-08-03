# Levy

Aplicacao PHP com Apache rodando em Docker e conectando em PostgreSQL local.

## Pre-requisitos

- Docker Desktop instalado
- Docker Desktop em execucao

## Configuracao do Docker

O `docker-compose.yml` ja define tudo o que a stack precisa para subir.

Hoje ele sobe com:

```yaml
APP:
  PORTA: 8083

POSTGRES:
  HOST_INTERNO: db
  HOST_EXTERNO: localhost
  PORTA: 5433
  DATABASE: levy
  USER: postgres
  PASSWORD: postgres
```

## Subir o projeto com Docker

Na raiz do projeto, rode:

```bash
docker compose up --build
```

Depois acesse:

```text
http://localhost:8083
```

Banco local do Docker:

```text
localhost:5433
```

O PostgreSQL fica publicado apenas em `127.0.0.1`, portanto nao aceita conexoes
diretas de outros computadores da rede.

## Primeiro acesso e usuarios

Ao iniciar uma base que ainda nao possui credenciais, acesse:

```text
http://localhost:8083/index.php/setup
```

Cadastre o e-mail e uma senha de pelo menos 12 caracteres. Essa primeira conta
se torna administradora e recebe todos os dados que ja existiam antes da
migracao multiusuario.

Depois do login, administradores podem abrir **Usuarios** no menu para criar
novas contas. Cada conta possui categorias, cartoes, pessoas, contas fixas,
transacoes e recebimentos isolados. Novos usuarios recebem somente as categorias
iniciais e nao enxergam dados de outras contas.

As senhas da aplicacao nao devem ser iguais a senha do Linux ou do banco.

## Rodar em segundo plano

```bash
docker compose up --build -d
```

## Criar ou atualizar as tabelas

Depois que os containers subirem:

```bash
docker compose exec app php migrate.php
```

A migracao tambem e executada automaticamente antes do Apache a cada inicio do
container. Ela e idempotente e pode ser executada novamente com seguranca.

## Rodar seed de exemplo

```bash
docker compose exec app php seed.php
```

## Importar CSV do banco antigo

Coloque os arquivos CSV na raiz do projeto ou em uma pasta separada. Os nomes devem seguir as tabelas do banco:

```text
usuarios.csv
pessoas.csv
cartoes.csv
categorias.csv
transacoes.csv
divisoes_transacao.csv
contas_fixas.csv
```

Para importar preservando os IDs:

```bash
docker compose exec app php import_csv_seed.php --truncate
```

Se os CSVs estiverem em outra pasta:

```bash
docker compose exec app php import_csv_seed.php --dir=./csv --truncate
```

Observacoes:

- O script detecta automaticamente delimitador `;`, `,`, `tab` ou `|`.
- O CSV precisa ter cabecalho com os nomes exatos das colunas.
- O `--truncate` limpa as tabelas antes da carga.

## Importar a fatura legada de abril de 2026

O importador fixo exige o e-mail do usuário proprietário dos dados. Os IDs de
cartão e pessoas configurados no script também são validados antes da importação:

```bash
docker compose exec app php importar_fatura.php --user-email=usuario@exemplo.com
```

## Ver logs

```bash
docker compose logs -f
```

Para ver apenas os logs da aplicacao:

```bash
docker compose logs -f app
```

## Parar os containers

```bash
docker compose down
```

## Rebuild da imagem

Se alterar `Dockerfile`, configuracao do Apache ou dependencias da imagem:

```bash
docker compose build --no-cache
docker compose up -d
```

## Reiniciar a aplicacao

```bash
docker compose restart app
```

## Validar a configuracao do compose

```bash
docker compose config
```

## Entrar no container

```bash
docker compose exec app bash
```

## Entrar no PostgreSQL

```bash
docker compose exec db psql -U postgres -d levy
```

## Restaurar um backup em outro computador

O backup SQL inclui a estrutura e os dados do banco. No outro notebook, copie o
arquivo `.sql` para a raiz deste projeto e suba o Docker normalmente:

```bash
docker compose up -d
```

Os comandos abaixo **apagam e recriam** o banco `levy` antes da restauracao.
Pare a aplicacao para que ela nao mantenha conexoes abertas e copie o arquivo
para o container do PostgreSQL:

```bash
docker compose stop app
docker compose cp backup_levy_2026-08-02_15-27-43.sql db:/tmp/backup_levy.sql
```

Em seguida, recrie o banco e importe o arquivo:

```bash
docker compose exec -T db psql -U postgres -d postgres -c "DROP DATABASE IF EXISTS levy WITH (FORCE);"
docker compose exec -T db psql -U postgres -d postgres -c "CREATE DATABASE levy;"
docker compose exec -T db psql -U postgres -d levy -f /tmp/backup_levy.sql
docker compose start app
```

Para confirmar que a restauracao terminou corretamente:

```bash
docker compose exec db psql -U postgres -d levy -c "\\dt"
```

> Troque `backup_levy_2026-08-02_15-27-43.sql` pelo nome do arquivo que voce
> quiser restaurar. A limpeza do banco e irreversivel; use esses comandos
> apenas quando quiser substituir todos os dados do `levy` no notebook destino.

## Observacoes

- O Apache do container serve a pasta `public` como raiz da aplicacao.
- O projeto usa `mod_rewrite` com o arquivo `public/.htaccess`.
- As rotas foram ajustadas para funcionar tanto no XAMPP quanto no Docker.
- O banco roda em um container separado chamado `db`.
- Os dados do Postgres ficam persistidos no volume `levy_postgres_data`.
- O `docker-compose.yml` nao depende do arquivo `.env`.
- Se o comando falhar com erro de engine, abra o Docker Desktop antes de rodar os comandos.
