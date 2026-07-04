# Using the Friendica tests

## Install Tools

You need to install the following software:

* PHP
* MySQL or Mariadb (the latter is preferred)

For example in Ubuntu you can run:

```
sudo apt install mariadb-server php
```

## Install PHP extensions

The following extensions must be installed:

* MySQL
* Curl
* GD
* XML
* DOM
* SimpleXML
* Intl
* Multi-precision
* Multi-byte string

For example in Ubuntu:

```
sudo apt install php-mysql php-curl php-gd php-xml php-intl php-gmp php-mbstring
```

## Create Local Database

The default database name is `test`, username `friendica`, password
`friendica`.  These can be overridden using environment variables
`DATABASE_NAME`, `DATABASE_USER`, `DATABASE_HOST`, and
`DATABASE_PASSWORD`.  Whatever settings you choose, you must give the
corresponding user the necessary privileges to create and destroy the
chosen database.

```
GRANT ALL PRIVILEGES ON test.* TO 'friendica'@'localhost' IDENTIFIED BY 'friendica' WITH GRANT OPTION;
GRANT CREATE, DROP ON test.* TO 'friendica'@'localhost';
```

## Use Docker Database

Instead of using a local database, you can also use a database running in a docker container.

The local development stack starts a MariaDB service that is reachable from the host on
`127.0.0.1:3306` by default:

```
docker compose -f .docker/compose.yaml up -d db
```

The default credentials are defined in `.docker/.dist.env`:

```
MYSQL_HOST=127.0.0.1
MYSQL_PORT=3306
MYSQL_DATABASE=friendica
MYSQL_USER=friendica
MYSQL_PASSWORD=friendica
```

The database test harness reads the `MYSQL_*` variables. To run PHPUnit against the
Docker database from the host, export them first:

```
export MYSQL_HOST=127.0.0.1
export MYSQL_PORT=3306
export MYSQL_DATABASE=friendica
export MYSQL_USER=friendica
export MYSQL_PASSWORD=friendica

composer run test:legacy
```

## Running Tests

Fast unit tests do not require a database:

```
composer run test:unit
```

Functional tests are meant for complete use cases with test doubles or fakes.
Add new DB-free use-case tests to `tests/Functional/`.

```
composer run test: functional
```

Integration tests use real infrastructure or adapter wiring. They usually require
the `MYSQL_*` variables described above:

```
composer run test:integration
```

The legacy suite contains the older tests under `tests/src/`, many of which still
use global DI state, broad fixtures, or a real database:

```
composer run test:legacy
```

All configured suites can be run through Composer:

```
composer run test
```

You can then run the tests using the `autotest.sh` script.  You should
specify the type of database as an argument, either `mysql` or
`mariadb`:

```
bin/dev/autotest.sh mariadb
```

You can also run just one particular file of tests:

```
bin/dev/autotest.sh mariadb src/Util/ImagesTest.php
```

Example output of tests passing:

```
OK (2 tests, 2 assertions)
```

Failed tests look like this.  Examine the output before this to see which tests failed.

```
FAILURES!
Tests: 2, Assertions: 2, Failures: 1.
```

## File structure

Tests are divided into test suites and supporting files.

### Test Suites

| Name                  | Location             | Description                                                                                                                                                                                                                    |
|-----------------------|----------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Unit Tests**        | `tests/Unit/`        | Isolated tests of individual classes or methods without external dependencies (no database, filesystem, or network). All collaborators are replaced with test doubles. Fast, numerous, and the foundation of the test pyramid. |
| **Functional Tests**  | `tests/Functional/`  | Tests of complete business use cases across multiple layers (e.g., HTTP request → Controller → Application → Domain → Response), usually without a real browser or database.                                                   |
| **Integration Tests** | `tests/Integration/` | Tests of the interaction between multiple components, including real infrastructure (database, filesystem, external libraries). Especially useful for verifying adapters to external systems.                                  |
| **Legacy Tests**      | `tests/src/`         | Existing tests that predate the current suite structure. New tests should only be added here when they intentionally cover legacy behavior that cannot yet be isolated.                                                        |

### Placement Rules

New tests should use the narrowest suite that can verify the behavior:

* Use `tests/Unit/` when collaborators can be replaced with mocks, stubs, builders, or fakes.
* Use `tests/Functional/` for complete business flows that should not require real infrastructure.
* Use `tests/Integration/` only when the database, filesystem, external library behavior, or container wiring is part of what is being verified.
* Avoid adding new tests to `tests/src/` unless the code under test still depends on legacy global state.

### Supporting Test Files

| Name         | Location          | Description                                                                                                                                                 | Example Names                                                                                     |
|--------------|-------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------|
| **Fixtures** | `tests/Fixtures/` | Static, predefined test data or initial states (database records, files, API payloads) used as a fixed foundation for tests.                                | `config.php`, `seed.sql`, `stripe-webhook.json`, `valid-invoice.pdf`, `github-user-response.json` |
| **Fakes**    | `tests/Fakes/`    | Fully functional but simplified implementations of real interfaces (e.g., in-memory versions of repositories) with real logic but without infrastructure.   | `InMemoryUserRepository.php`, `InMemoryEventBus.php`, `FakeClock.php`, `FakeMailer.php`           |
| **Builder**  | `tests/Builders/` | Test Data Builders – create complex, valid domain objects (aggregates, entities, value objects) using a fluent interface with default values.               | `UserBuilder.php`, `OrderBuilder.php`, `AddressBuilder.php`, `InvoiceBuilder.php`                 |
| **Helper**   | `tests/Helpers/`  | Reusable helper functions, traits, or base classes to reduce boilerplate in tests (e.g., DB setup, authentication simulation).                              | `DatabaseTestTrait.php`, `AuthenticatesUsers.php`, `AssertsJsonSchema.php`, `TestCase.php`        |
