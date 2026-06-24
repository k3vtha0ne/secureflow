## Initialisation du projet
symfony new secureflow --webapp
cd secureflow

## Initialisation GIT
git init
git add .
git commit -m "Initial Symfony project"

## Installation des dépendances
composer require api
composer require orm
composer require maker --dev
composer require profiler --dev
composer require symfony/test-pack --dev
composer require lexik/jwt-authentication-bundle

## Créer le Docker Compose
touch docker-compose.yml
docker compose -f docker-compose.yml up -d <!-- Forcer Docker à utiliser le docker-compose.yml local -->
docker compose -f docker-compose.yml ps
docker compose -f docker-compose.yml down

## Créer un Makefile (raccourcis de commandes Docker)
touch Makefile

## Tester la connexion au ports (ex: 3307)
nc -vz 127.0.0.1 3307

## Configurer .env.local
touch .env.local

## Créer la base de données
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:status

## xxx

## xxx

## xxx

## xxx
