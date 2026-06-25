# Developer environment

SecureFlow uses a local Symfony backend, a MariaDB database through Docker, and a React dashboard built with Vite.

## Requirements

- PHP 8.4+
- Composer
- Symfony CLI
- Docker Desktop
- Node.js / npm
- Make

## Local setup

Copy the local environment example:

```bash
cp .env.local.example .env.local
```

Install dependencies:

```bash
make install
```

Start Docker services:

```bash
make start
```

Create the database, run migrations and load fixtures:

```bash
make db-reset
```

Start the Symfony server:

```bash
make serve
```

Start the Vite dev server in another terminal:

```bash
make front-dev
```

The application is then available at:

```text
http://127.0.0.1:8001/dashboard
```

## Test users

Development fixtures provide test users such as:

```text
admin@alpha.test / password
manager@alpha.test / password
user@beta.test / password
```

These users are useful to test JWT authentication and organization-based API scoping.

## Useful commands

```bash
make qa
```

Runs backend validation, PHPUnit tests and frontend build.

```bash
make test
```

Runs PHPUnit only.

```bash
make front-build
```

Builds the React/Vite assets.

```bash
make db-reset
```

Drops and recreates the database, runs migrations and loads fixtures.

## Docker note

The project standard local database is MariaDB through:

```text
docker-compose.yml
```

The Symfony-generated compose.yaml and compose.override.yaml files are not used by the Makefile.
