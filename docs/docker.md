# Docker setup

SecureFlow currently uses docker-compose.yml as the official local Docker setup.

It starts:

- MariaDB on host port 3307;
- phpMyAdmin on host port 8081.

The Makefile explicitly uses:

```bash
docker compose -f docker-compose.yml
```

The Symfony-generated compose.yaml and compose.override.yaml files are not used by the current development workflow.
