# Tests

Friendica uses PHPUnit for automated tests.
The detailed local setup is documented in `tests/README.md`;
this page summarizes the conventions for development work.

## Test suites

Run the fast, isolated unit tests first:

```bash
composer run test:unit
```

Functional tests are reserved for complete business flows that use test doubles or fakes instead of real infrastructure.

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

<a name="template-tests" id="template-tests"></a>
## Template tests

```bash
composer run test:templates
```

Part of the `unit` suite, so no database is needed. The rules live in `tests/Util/Html/Invariants.php` and are applied by two tests:

`TemplateInvariantTest` scans every `.tpl` under `view/` for missing `alt` texts, nested interactive elements and flow content in buttons.
Existing violations are grandfathered per file and rule in `template-invariants-baseline.json`.
Once you fixed one, lock it in:

```bash
UPDATE_TEMPLATE_BASELINE=1 composer run test:unit
```

`TemplateRenderTest` renders templates through `tests/TemplateTestCase.php` and asserts on the DOM.
Rules that would produce false alarms on raw source, such as duplicate ids, only run here.

Assert invariants, not concrete markup: query with `$this->element()`, cover both themes with the `themeProvider()` data provider, and build the variables from the real entity.
Element ids  in `view/` churn about 9% per year, so pin one only as a deliberate regression guard.

Some legacy and integration-style tests require a MariaDB/MySQL database.
The test harness reads the following environment variables:

```bash
MYSQL_HOST=127.0.0.1
MYSQL_PORT=3306
MYSQL_DATABASE=test
MYSQL_USER=friendica
MYSQL_PASSWORD=friendica
```

**Warning**: Never point database-dependent tests at a production database.
Test setup may truncate or replace table contents.
