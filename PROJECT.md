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
composer require --dev orm-fixtures
composer require lexik/jwt-authentication-bundle (+php bin/console lexik:jwt:generate-keypair)

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
git status --ignored | grep .env.local <!-- Vérifier que le fichier est ignoré par GIT -->

## Créer la base de données
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:status

# Si besoin de la recréer suite à une souci de migrations:
php bin/console doctrine:database:drop --force --if-exists
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction

## Créer un controller de santé pour tester le endpoint
php bin/console make:controller HealthController
symfony serve -d --port=8001
symfony server:list
curl http://127.0.0.1:8001/api/health

## Commandes Symfony

## Check Symfony
php bin/console lint:container vérifie que le container de services Symfony est cohérent.

Détecte des erreurs comme :

- service mal configuré
- dépendance impossible à injecter
- classe introuvable
- argument de constructeur manquant
- autowiring impossible
- erreur dans la configuration services.yaml

Combo: php bin/console doctrine:schema:validate && php bin/console lint:container

Si le lint:container passe mais qu'il reste des erreurs intelephense: CMD + Shift + P => Intelephense: Index workspace

## Faire une requête en ligne de commande:
php bin/console dbal:run-sql "SELECT COUNT(*) AS total FROM organization"

## Créer une commande
php bin/console make:command DomainOverviewCommand
