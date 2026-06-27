# Quality

SecureFlow uses automated checks to keep the project stable and readable.

The goal is not only to test features, but also to catch configuration, typing and integration problems early.

## Local quality command

Run the full local quality suite with:

```bash
make qa
```

This runs:

1. Doctrine schema validation;
2. Symfony container lint;
3. PHPStan static analysis;
4. PHPUnit tests;
5. React/Vite production build.

## Doctrine schema validation

Doctrine schema validation checks that the entity mapping is coherent.

It helps catch issues such as invalid relationships, broken mapping attributes or inconsistent database metadata.

## Symfony container lint

The Symfony container lint checks that services can be wired correctly.

It can detect issues such as:

* missing services;
* invalid constructor arguments;
* autowiring errors;
* broken service configuration.

## PHPStan

PHPStan is configured through:

```text
phpstan.dist.neon
```

The project currently uses level 5.

The local override file `phpstan.neon` is ignored by Git and reserved for developer-specific configuration.

The Makefile generates the Symfony container XML before running PHPStan so that Symfony services can be analyzed more accurately.

## PHPUnit

The project includes unit and functional tests.

Unit tests cover business services in isolation:

* `DocumentAccessServiceTest`
* `CampaignAccessServiceTest`
* `DocumentLifecycleServiceTest`
* `CampaignSchedulingServiceTest`

Functional tests cover real API scenarios:

* authentication;
* document API access;
* campaign API access;
* cross-organization protection.

## Frontend build

The React dashboard is built with Vite.

The CI runs a production frontend build to make sure backend changes do not leave the dashboard in a broken state.

## Continuous integration

GitHub Actions runs the project quality checks on pushed branches and pull requests.

The CI environment uses:

* PHP 8.4;
* MariaDB 11.4;
* Composer;
* generated JWT test keys;
* Doctrine migrations;
* Symfony validation;
* PHPStan;
* PHPUnit;
* Node.js;
* Vite production build.

This provides a reproducible quality gate before code reaches `master`.
