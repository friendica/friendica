# Tests

Friendica uses PHPUnit for automated tests. The detailed local setup is
documented in `tests/README.md`; this page summarizes the conventions for
development work.

## Test suites

Run the fast, isolated unit tests first:

```bash
composer run test:unit
```

Functional tests are reserved for complete business flows that use test doubles
or fakes instead of real infrastructure.

Run integration tests for real infrastructure or adapter wiring:

```bash
composer run test:integration
```

Run the legacy suite for the older tests under `tests/src/`:

```bash
composer run test:legacy
```

Run all configured suites:

```bash
composer run test
```

Some legacy and integration-style tests require a MariaDB/MySQL database. The
test harness reads the following environment variables:

```bash
MYSQL_HOST=127.0.0.1
MYSQL_PORT=3306
MYSQL_DATABASE=test
MYSQL_USER=friendica
MYSQL_PASSWORD=friendica
```

**Warning**: Never point database-dependent tests at a production database. Test setup may
truncate or replace table contents.
