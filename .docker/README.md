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

### View logs:
```bash
docker compose logs -f php
docker compose logs -f nginx
docker compose logs -f mariadb
```

### Stop environment:
```bash
docker compose down
```

## Access

- **Friendica application**: http://localhost:8080
- **MySQL/MariaDB**: localhost:3306
  - User: `friendica`
  - Password: `friendica_`
  - Database: `friendica`

## Database access via CLI

```bash
docker compose exec mariadb mysql -u friendica -p friendica friendica
```

## Container names

- `friendica-php` - PHP container
- `friendica-nginx` - Nginx container
- `friendica-mariadb` - MariaDB container

## Adjust configuration

### PHP configuration
The PHP configuration is located in `.docker/php/php.ini`

### Nginx configuration
The Nginx configuration is located in `.docker/nginx/nginx.conf`

### Environment variables
Environment variables can be adjusted in `docker compose.yml` under `services.php.environment`.

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
