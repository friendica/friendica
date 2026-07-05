# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

This is **LARPnet** — a fork of [Friendica](https://friendi.ca) (an ActivityPub-federated social network) customized for a Polish LARP community. The `develop` branch tracks upstream Friendica; the `larpnet` branch is the production fork. All custom work lives on `larpnet`. When merging upstream Friendica updates, merge `develop` → `larpnet`.

## Development environment

The dev stack is in `.docker/`. Copy `.docker/.dist.env` to `.docker/.env.local` and adjust if needed (defaults work out of the box).

```bash
docker compose -f .docker/compose.yaml up -d   # start stack (nginx + php-fpm + mariadb + redis)
docker compose -f .docker/compose.yaml down     # stop
```

App runs at `http://localhost:8080`. phpMyAdmin at `http://localhost:8086`.

Install PHP deps (needed for linting/tests outside Docker):
```bash
composer install
```

## Common commands

```bash
# Tests
composer test                  # all tests
composer test:unit             # unit tests only
phpunit tests/src/Unit/Object/PostTest.php   # single test file

# Code quality
composer cs:check              # check code style (php-cs-fixer)
composer cs:fix                # auto-fix code style
composer phpstan               # static analysis (src/)
composer phpstan-addons        # static analysis (addon/)
composer phpmd                 # mess detector

# Database schema
composer db:update-structure   # regenerates database.sql from static/dbstructure.config.php
```

## Architecture

### Request lifecycle

`index.php` → `src/App.php` bootstraps the Dice DI container → `src/App/Router.php` dispatches to a `src/Module/**` class → module `run()` renders output.

Routes are declared in `static/routes.config.php` as `'path' => [Module::class, [HTTP_METHODS]]`.

### Dependency injection

All services are accessed via `DI::*()` static methods (`src/DI.php`), which proxy to a [Dice](https://r.je/dice) container. Wire new services in `static/dependencies.config.php`.

### Data layer

- **Models** (`src/Model/`) contain business logic and query the database directly via `DBA` / `Post::select*` helpers.
- **Database schema** is the source of truth in `static/dbstructure.config.php`. Changes there must be followed by `composer db:update-structure` to regenerate `database.sql`.
- Posts are stored across `post`, `post-user`, and related tables (not `item` — that table no longer exists).

### Background workers

`src/Worker/` contains worker classes. Each has a static `execute()` method. Workers are dispatched via `Worker::add()` and run by the background daemon (`bin/console.php daemon`).

### Addon system

Addons in `addon/<name>/` register callbacks with `Hook::register()`. The main hook points are `addon_settings`, `addon_settings_post`, `page_end`, and protocol-level hooks.

### Privacy levels (`src/Model/Item.php`)

| Constant | Value | Behaviour |
|---|---|---|
| `Item::PUBLIC` | 0 | Visible to everyone, federated |
| `Item::PRIVATE` | 1 | Visible to explicitly listed recipients |
| `Item::UNLISTED` | 2 | Unlisted, federated |
| `Item::SERVER_ONLY` | 3 | (**larpnet custom**) Visible to all local logged-in users, never federated |

## LARPnet-specific files

All files below are larpnet additions or patches. When rebasing onto a new Friendica release, these need careful conflict resolution.

**Important:** every patched core file must have a corresponding `COPY` line in `Dockerfile` AND an entry in `larpnet-entrypoint.sh`. The Friendica entrypoint only rsyncs files when the Friendica version changes — `larpnet-entrypoint.sh` is a wrapper that unconditionally copies the patched files on every container start so larpnet redeployments always land.

| Path | Purpose |
|---|---|
| `addon/larpnet_banner/` | Per-user profile banner image (uploaded via addon settings, injected as CSS background on profile pages) |
| `addon/larpnet_calendar/` | Generates private iCal subscription URL for each user's events |
| `src/Worker/NtfyPush.php` | Background worker: sends push notification via [ntfy](https://ntfy.sh) when a notification is created. Configured with admin-level `larpnet_notifications/ntfy_url` + `ntfy_token`, per-user `ntfy_topic`. |
| `src/Model/Subscription.php` | One-line patch: dispatches `NtfyPush` worker on push subscription notification |
| `view/theme/larpnet/` | Main custom theme (based on Frio) |
| `view/theme/larpnet_notifications/` | Stripped-down theme for notification emails |
| `src/Content/Item.php` | Patched `getACL()` to handle `visibility=local` → `private = SERVER_ONLY` |
| `src/Core/ACL.php` | Patched `getFullSelectorHTML()` to pass local option labels and detect existing SERVER_ONLY posts |
| `src/Model/Item.php` | Patched to add `SERVER_ONLY = 3` privacy constant |
| `src/Object/Post.php` | Patched `fetchPrivacy()` to handle `SERVER_ONLY` |
| `src/Module/Item/Compose.php` | Patched to allow the compose page with themes that extend Frio (not just Frio itself) |
| `src/Module/Item/Display.php` | Patched to allow logged-in users to view `SERVER_ONLY` posts |
| `src/Module/Post/Share.php` | Patched to block sharing of `SERVER_ONLY` posts |
| `src/Module/Privacy/PermissionTooltip.php` | Patched to label `SERVER_ONLY` posts |
| `src/Module/Manifest.php` | Patched to serve larpnet-branded PWA icons for larpnet/larpnet_notifications themes |
| `src/App/Page.php` | Patched to use larpnet icon as apple-touch-icon default for larpnet themes |

## Building and deploying

```bash
cp .env.example .env       # fill in REGISTRY_URL, REGISTRY_USER, REGISTRY_PASSWORD
./build.sh                 # builds Docker image and pushes to cr.mj12.cloud
```

The image is tagged `{FRIENDICA_VERSION}-{GIT_SHA}` (e.g. `2026.05-2393b52`). CI (`.github/workflows/build.yml`) builds and pushes automatically on push to `larpnet`.

If the registry is behind Cloudflare and upload fails, open an SSH tunnel and set `REGISTRY_PUSH_URL=localhost:5000` in `.env`.
