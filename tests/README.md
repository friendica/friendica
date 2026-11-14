# Using the Friendica tests

## Test suites

| Name                  | Location             | Description                                                                                                                                                                                                                    |
|-----------------------|----------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Unit Tests**        | `tests/Unit/`        | Isolated tests of individual classes or methods without external dependencies (no database, filesystem, or network). All collaborators are replaced with test doubles. Fast, numerous, and the foundation of the test pyramid. |
| **Functional Tests**  | `tests/Functional/`  | Tests of complete business use cases across multiple layers (e.g., HTTP request → Controller → Application → Domain → Response), usually without a real browser or database.                                                   |
| **Integration Tests** | `tests/Integration/` | Tests of the interaction between multiple components, including real infrastructure (database, filesystem, external libraries). Especially useful for verifying adapters to external systems.                                  |
| **Legacy Tests**      | `tests/src/`         | Existing tests that predate the current suite structure. New tests should only be added here when they intentionally cover legacy behavior that cannot yet be isolated.                                                        |

Each suite has its own Composer script:

```bash
composer run test:unit
composer run test:functional
composer run test:integration
composer run test:legacy
composer run test              # all suites at once
```

Only `composer run test:unit` runs without any setup. Everything else needs a database — see below.
That includes the functional suite: its only test (`DependencyCheckTest`) extends `FixtureTestCase` and therefore boots a real database connection.

> **Note:** `tests/Integration/` does not contain any test yet, so `composer run test:integration` currently aborts with `No tests executed!` (exit code 1) until the first test is added there.

## Running the DB-dependent tests with the Docker stack

This is the recommended setup for local development.
It needs no MariaDB, no PHP extensions and no MySQL client on your host.

### 1. Start the stack

```bash
docker compose -f .docker/compose.yaml up -d
```

See [`.docker/README.md`](../.docker/README.md) for the full development environment.

### 2. Create the test database

The tests use their own database (`test`), so a test run never touches the data of your local dev instance (`friendica`).
A freshly created stack creates it automatically.
If your `mariadb_data` volume predates that, create it once by hand:

```bash
docker compose -f ../.docker/compose.yaml exec db \
  mariadb -uroot -proot -e "CREATE DATABASE IF NOT EXISTS \`test\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL PRIVILEGES ON \`test\`.* TO 'friendica'@'%';"
```

### 3. Import the database schema

The tests expect the tables to exist — they do not create them. Import `database.sql`, the same way CI does:

```bash
docker compose -f ../.docker/compose.yaml exec -T db \
  mariadb -ufriendica -pfriendica test < ../database.sql
```

Without this step every DB test fails with `Base table or view not found: 1146 Table 'test.config' doesn't exist`.

`database.sql` only contains `CREATE TABLE IF NOT EXISTS` statements, so a second import does not pick up structure changes.
After a schema change, drop the database and redo steps 2 and 3, or apply the update in place:

```bash
docker compose -f ../.docker/compose.yaml exec -e MYSQL_DATABASE=test php \
  bin/console dbstructure update
```

### 4. Run the tests inside the PHP container

```bash
docker compose -f ../.docker/compose.yaml exec php \
  ./vendor/bin/phpunit -c tests/phpunit.xml --testsuite legacy
```

The container already provides `MYSQL_HOST`, `MYSQL_USER`, `MYSQL_PASSWORD` and `MYSQL_TEST_DATABASE`.
The PHPUnit bootstrap maps `MYSQL_TEST_DATABASE` to `MYSQL_DATABASE`, so tests use the separate test database without overriding the development database setting.

A single test file works the same way:

```bash
docker compose -f ../.docker/compose.yaml exec php \
  ./vendor/bin/phpunit -c tests/phpunit.xml tests/src/Content/Text/BBCodeTest.php
```

> **On macOS, run the DB tests inside the container, not on the host.**
> `tests/src/` exercises code that validates hostnames with `dns_get_record()`, and the test configuration (`mods/local.config.ci.php`) uses `https://friendica.local` as base URL.
> macOS routes every `.local` lookup to mDNS/Bonjour, where it blocks for ~30 seconds instead of failing right away — the suite then takes hours instead of minutes.
> Inside the Linux container it finishes in about 3 minutes.

### Running against the Docker database from the host

Possible, but see the warning above. The database is published on `127.0.0.1:3306`, and the
host has to pass all connection variables itself:

```bash
export MYSQL_HOST=127.0.0.1
export MYSQL_PORT=3306
export MYSQL_DATABASE=test
export MYSQL_USER=friendica
export MYSQL_PASSWORD=friendica

composer run test:legacy
```

## Running the DB-dependent tests with a local database

If you prefer a database on your host, install it together with the PHP extensions the application needs:

```bash
sudo apt install mariadb-server php
sudo apt install php-mysql php-curl php-gd php-xml php-intl php-gmp php-mbstring
```

Then create the database and a user that may create and drop it:

```sql
CREATE DATABASE test DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
GRANT ALL PRIVILEGES ON test.* TO 'friendica'@'localhost' IDENTIFIED BY 'friendica' WITH GRANT OPTION;
```

Import the schema (`mysql -ufriendica -pfriendica test < database.sql`), export the variables from the next section and run the Composer scripts.

## Database environment variables

The test harness (`tests/Util/Database/StaticDatabase.php`) builds its connection purely from the environment — there are no defaults, and `config/local.config.php` is not used:

| Variable                         | Required | Description                           |
|----------------------------------|----------|---------------------------------------|
| `MYSQL_HOST`                     | yes      | Hostname or IP of the database server |
| `MYSQL_PORT`                     | no       | Port, appended to the host if set     |
| `MYSQL_USERNAME` or `MYSQL_USER` | yes      | Database user                         |
| `MYSQL_PASSWORD`                 | yes      | Database password                     |
| `MYSQL_DATABASE`                 | yes, unless `MYSQL_TEST_DATABASE` is set for PHPUnit | Database name |
| `MYSQL_TEST_DATABASE`            | no       | Test database name. When set, PHPUnit uses it instead of `MYSQL_DATABASE`. |

If host, user or database is missing, the run aborts with `Either one of the following settings are missing: Host, User or Database`.

## Reading the output

Example output of tests passing:

```
OK (2 tests, 2 assertions)
```

Failed tests look like this. Examine the output before this to see which tests failed.

```
FAILURES!
Tests: 2, Assertions: 2, Failures: 1.
```

## Where to put new tests

New tests should use the narrowest suite that can verify the behavior:

* Use `tests/Unit/` when collaborators can be replaced with mocks, stubs, builders or fakes.
* Use `tests/Functional/` for complete business flows that should not require real infrastructure.
* Use `tests/Integration/` only when the database, filesystem, external library behavior or container wiring is part of what is being verified.
* Avoid adding new tests to `tests/src/` unless the code under test still depends on legacy global state.

### Supporting test files

| Name         | Location          | Description                                                                                                                                                 | Example Names                                                                                     |
|--------------|-------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------|
| **Fixtures** | `tests/Fixtures/` | Static, predefined test data or initial states (database records, files, API payloads) used as a fixed foundation for tests.                                | `config.php`, `seed.sql`, `stripe-webhook.json`, `valid-invoice.pdf`, `github-user-response.json` |
| **Fakes**    | `tests/Fakes/`    | Fully functional but simplified implementations of real interfaces (e.g., in-memory versions of repositories) with real logic but without infrastructure.   | `InMemoryUserRepository.php`, `InMemoryEventBus.php`, `FakeClock.php`, `FakeMailer.php`           |
| **Builder**  | `tests/Builders/` | Test Data Builders – create complex, valid domain objects (aggregates, entities, value objects) using a fluent interface with default values.               | `UserBuilder.php`, `OrderBuilder.php`, `AddressBuilder.php`, `InvoiceBuilder.php`                 |
| **Helper**   | `tests/Helpers/`  | Reusable helper functions, traits, or base classes to reduce boilerplate in tests (e.g., DB setup, authentication simulation).                              | `DatabaseTestTrait.php`, `AuthenticatesUsers.php`, `AssertsJsonSchema.php`, `TestCase.php`        |
