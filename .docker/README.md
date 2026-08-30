# Friendica Docker Development Environment

The Docker environment for local development is configured with the following services:

## Services

- **PHP 8.2** - PHP-FPM with all necessary extensions for Friendica
- **Nginx** - Web server on port 8080
- **MariaDB** - Database server

## Quick Start

### Start environment:
```bash
docker compose up -d
```

### Install Friendica
```bash
docker compose exec php composer install
docker compose exec php bin/console autoinstall -av -f .docker/autoinstall.config.php
```

### Run the tests
The PHPUnit suites use their own `test` database and need the schema imported separately.
See [`tests/README.md`](../tests/README.md).

### View logs:
```bash
docker compose logs -f php
docker compose logs -f nginx
docker compose logs -f db
```

### Stop environment:
```bash
docker compose down
```

## Access

- **Friendica application**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8086
- **MySQL/MariaDB**: 127.0.0.1:3306
  - User: `friendica`
  - Password: `friendica`
  - Database: `friendica` (dev instance), `test` (PHPUnit suites)
  - Root password: `root`

## Database access via CLI

```bash
docker compose exec db mariadb -ufriendica -pfriendica friendica
```

## Services and container names

| Service      | Container             | Description      |
|--------------|-----------------------|------------------|
| `php`        | `docker-php-1`        | PHP-FPM          |
| `nginx`      | `docker-nginx-1`      | Web server       |
| `db`         | `docker-db-1`         | MariaDB          |
| `phpmyadmin` | `docker-phpmyadmin-1` | phpMyAdmin       |

Container names follow the Compose project name, which defaults to the `.docker` directory.

## Adjust configuration

### PHP configuration
The PHP configuration is located in `.docker/php/php.ini`

### Nginx configuration
The Nginx configuration is located in `.docker/nginx/nginx.conf`

### Environment variables
The defaults live in `.docker/.dist.env`. Copy that file to `.docker/.env.local` and adjust it there — both files are read by every service, and `.env.local` wins.

## Troubleshooting

### Restart container:
```bash
docker compose restart php
```

### Delete volumes and restart:
```bash
docker compose down -v
docker compose up -d
```

### Composer Install:
```bash
docker compose exec php composer install
```

## Important notes

- The entire project directory is mounted in the PHP container
- MariaDB data is stored in a volume
- This configuration is optimized for local development
