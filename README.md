# SecureFlow

[![CI](https://github.com/k3vtha0ne/secureflow/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/k3vtha0ne/secureflow/actions/workflows/ci.yml)

SecureFlow is a technical portfolio project built around a secured SaaS-style document management platform.

It demonstrates backend architecture, API security, organization-based data isolation, business rules, automated tests, static analysis, continuous integration and a small React dashboard consuming a JWT-secured Symfony API.

## Project purpose

SecureFlow is designed to show how to structure a modern Symfony API beyond basic CRUD.

The project focuses on:

* secured API resources;
* multi-tenant data isolation;
* explicit business rules;
* testable application logic;
* defensive access control;
* automated quality checks;
* a simple fullstack flow with React and JWT authentication.

The goal is not to maximize feature count. The goal is to make technical decisions visible and readable.

## Stack

* PHP 8.4+
* Symfony 8.1
* API Platform
* Doctrine ORM
* MariaDB
* LexikJWTAuthenticationBundle
* PHPUnit
* PHPStan
* React
* Vite
* Docker Compose
* GitHub Actions
* Make

## Main features

* JWT authentication.
* Secured document and campaign API resources.
* Organization-based API scoping.
* Cross-tenant access protection.
* Symfony voters for object-level permissions.
* Domain services for reusable business rules.
* Lightweight application layer with Commands and Queries.
* Unit tests for business rules and application handlers.
* Functional API tests for authentication and secured resources.
* React dashboard consuming the secured API.
* CI pipeline running backend validation, static analysis, tests and frontend build.

## Architecture highlights

SecureFlow uses a simple layered structure:

```text
src/
├── Entity
├── Repository
├── Service
├── Application
├── Security
├── ApiPlatform
└── Controller
```

Key ideas:

* `Entity` contains the Doctrine model.
* `Service` contains reusable business rules.
* `Application` contains explicit Commands and Queries.
* `Security/Voter` contains object-level permission checks.
* `ApiPlatform/Doctrine/Extension` applies organization scoping at query level.
* `Controller` stays minimal.

The project intentionally avoids unnecessary abstractions. The architecture is explicit enough to be readable, but not over-engineered.

## Security model

SecureFlow uses a defensive security model with several layers:

1. JWT authentication protects API routes.
2. API Platform resources expose secured read operations.
3. `OrganizationScopeExtension` restricts tenant-owned resources at Doctrine query level.
4. Symfony voters protect loaded objects.
5. Business access rules are delegated to dedicated services.

In simple terms:

```text
one organization = one isolated workspace
```

A user from one organization must not be able to read documents or campaigns owned by another organization.

## Quality pipeline

Run the full local quality check with:

```bash
make qa
```

This runs:

* Doctrine schema validation;
* Symfony container lint;
* PHPStan static analysis;
* PHPUnit tests;
* React/Vite production build.

The GitHub Actions CI runs equivalent checks on pushed branches and pull requests.

Current local test suite:

```text
57 tests, 124 assertions
```

## Requirements

* PHP 8.4+
* Composer
* Symfony CLI
* Docker Desktop
* Node.js / npm
* Make

## Local installation

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

In another terminal, start the Vite dev server:

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

These users are useful to test JWT authentication and organization-based API scoping.

## Useful commands

```bash
make help
```

Displays available project commands.

```bash
make qa
```

Runs the full local quality pipeline.

```bash
make phpstan
```

Runs PHPStan static analysis.

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

* `docs/architecture.md`
* `docs/security.md`
* `docs/quality.md`
* `docs/application-use-cases.md`
* `docs/developer-environment.md`
* `docs/docker.md`

## Notes

This project is intentionally built as a technical reference project.

It can be used as a reusable base to document patterns such as Symfony API security, multi-tenant scoping, business services, PHPStan integration, PHPUnit testing, CI setup and React/Vite integration.
