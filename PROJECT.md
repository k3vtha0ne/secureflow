# SecureFlow project overview

SecureFlow is a technical portfolio project designed to demonstrate a production-oriented Symfony API with security, multi-tenant data isolation, business rules, automated tests and continuous integration.

The project is intentionally focused on backend engineering quality rather than feature volume.

## Product context

SecureFlow simulates a SaaS document management platform where users belong to an organization.

Users can access secured document and campaign resources through a JWT-protected API. The API prevents users from reading resources owned by another organization.

A small React dashboard consumes the secured Symfony API to demonstrate a full request flow from frontend to backend.

## Technical focus

The project highlights:

* Symfony and API Platform API development;
* Doctrine ORM data modeling;
* JWT authentication;
* organization-based data isolation;
* Symfony voters for object-level permissions;
* business rules extracted into dedicated services;
* lightweight Commands and Queries for application use cases;
* PHPUnit unit and functional tests;
* PHPStan static analysis;
* GitHub Actions continuous integration;
* Docker-based local MariaDB setup.

## Design approach

The codebase favors explicit and readable structures over unnecessary abstractions.

Business rules are kept out of controllers and voters when they need to be reused or tested directly. API resources expose read operations, while write use cases are handled server-side to avoid trusting client-provided ownership or organization data.

The goal is not to reproduce a full enterprise architecture, but to show clear boundaries, defensive security decisions and maintainable Symfony code.

## Documentation

Further documentation is available in:

* `docs/architecture.md`
* `docs/security.md`
* `docs/quality.md`
* `docs/application-use-cases.md`
* `docs/developer-environment.md`
* `docs/docker.md`
