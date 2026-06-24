# SecureFlow

SecureFlow est un projet fil rouge de préparation technique autour d'une application SaaS de gestion de documents sécurisés, campagnes de diffusion, droits d'accès, logs, analytics et traitements automatisés.

## Objectifs techniques

- PHP 8.3
- Symfony 7
- API Platform
- Doctrine ORM
- MariaDB / MySQL
- Sécurité Symfony
- Tests unitaires et fonctionnels
- Docker
- CI/CD
- Front React
- Scripts Python batch
- MongoDB optionnel pour analytics

## Prérequis

- PHP 8.3+
- Composer
- Symfony CLI
- Docker Desktop
- Make

## Installation locale

```bash
composer install
cp .env.local.example .env.local
make start
php bin/console doctrine:database:create --if-not-exists
