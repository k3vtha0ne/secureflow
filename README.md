# SecureFlow

SecureFlow is a technical portfolio project built around a secured SaaS-style document management platform.

It demonstrates backend architecture, API security, organization-based data isolation, business rules, tests, and a small React dashboard consuming a JWT-secured Symfony API.

## Stack

- PHP 8.4+
- Symfony 8.1
- API Platform
- Doctrine ORM
- MariaDB
- LexikJWTAuthenticationBundle
- PHPUnit
- React
- Vite
- Docker Compose
- Make

## Main features

- Secured document and campaign API resources.
- JWT authentication.
- Organization-based API scoping.
- Cross-tenant access protection.
- Domain services for business rules.
- Lightweight application layer with Commands and Queries.
- Unit tests for business rules and application handlers.
- Functional API tests.
- React dashboard consuming the secured API.

## Requirements

- PHP 8.4+
- Composer
- Symfony CLI
- Docker Desktop
- Node.js / npm
- Make

## Local installation

```bash
cp .env.local.example .env.local
make install
make start
make db-reset
make serve
```

In another terminal:

```bash
make front-dev
```

Open:

```text
http://127.0.0.1:8001/dashboard
```

## Test users

After loading fixtures:

```text
admin@alpha.test / password
manager@alpha.test / password
user@beta.test / password
```

## Quality checks

Run the full local quality check:

```bash
make qa
```

This runs:

- Doctrine schema validation;
- Symfony container lint;
- PHPUnit tests;
- React/Vite production build.

## Useful commands

```bash
make help
```

Displays available project commands.

```bash
make test
```

Runs PHPUnit.

```bash
make front-build
```

Builds frontend assets.

```bash
make db-reset
```

Recreates the database and loads fixtures.

## Documentation

Additional documentation is available in:

```text
docs/
```

Key files:

- docs/application-use-cases.md
- docs/developer-environment.md
- docs/docker.md
