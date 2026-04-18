# Friendica Docker Development Environment

Die Docker-Umgebung für die lokale Entwicklung ist mit den folgenden Services konfiguriert:

## Services

- **PHP 8.2** - PHP-FPM mit allen notwendigen Erweiterungen für Friendica
- **Nginx** - Webserver auf Port 8080
- **MariaDB** - Datenbankserver

## Quick Start

### Umgebung starten:
```bash
docker compose up -d
```

### Friendica installieren
```bash
docker compose exec php composer install
docker compose exec php bin/console autoinstall -av -f .docker/autoinstall.config.php
```

### Logs ansehen:
```bash
docker compose logs -f php
docker compose logs -f nginx
docker compose logs -f mariadb
```

### Umgebung stoppen:
```bash
docker compose down
```

## Zugriff

- **Friendica-Anwendung**: http://localhost:8080
- **MySQL/MariaDB**: localhost:3306
  - Benutzer: `friendica`
  - Passwort: `friendica_`
  - Datenbank: `friendica`

## Datenbankzugang über CLI

```bash
docker compose exec mariadb mysql -u friendica -p friendica friendica
```

## Container-Namen

- `friendica-php` - PHP Container
- `friendica-nginx` - Nginx Container
- `friendica-mariadb` - MariaDB Container

## Konfiguration anpassen

### PHP-Konfiguration
Die PHP-Konfiguration befindet sich in `.docker/php/php.ini`

### Nginx-Konfiguration
Die Nginx-Konfiguration befindet sich in `.docker/nginx/nginx.conf`

### Umgebungsvariablen
Die Umgebungsvariablen können in `docker compose.yml` unter `services.php.environment` angepasst werden.

## Troubleshooting

### Container neustarten:
```bash
docker compose restart php
```

### Volumes löschen und neustart:
```bash
docker compose down -v
docker compose up -d
```

### Composer Install:
```bash
docker compose exec php composer install
```

## Wichtige Hinweise

- Das gesamte Projektverzeichnis wird in den PHP-Container gemountet
- MariaDB-Daten werden in einem Volume gespeichert
- Diese Konfiguration ist für die lokale Entwicklung optimiert
