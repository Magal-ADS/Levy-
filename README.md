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

## Rodar em segundo plano

```bash
docker compose up --build -d
```

## Criar as tabelas

Depois que os containers subirem:

```bash
docker compose exec app php migrate.php
```

## Rodar seed de exemplo

```bash
docker compose exec app php seed.php
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

## Observacoes

- O Apache do container serve a pasta `public` como raiz da aplicacao.
- O projeto usa `mod_rewrite` com o arquivo `public/.htaccess`.
- As rotas foram ajustadas para funcionar tanto no XAMPP quanto no Docker.
- O banco roda em um container separado chamado `db`.
- Os dados do Postgres ficam persistidos no volume `levy_postgres_data`.
- O `docker-compose.yml` nao depende do arquivo `.env`.
- Se o comando falhar com erro de engine, abra o Docker Desktop antes de rodar os comandos.
