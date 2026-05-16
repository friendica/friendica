# Tests

You can run unit tests with [PHPUnit](https://phpunit.de/):

```bash
phpunit
```

Some tests require access to a MySQL database.
You can specify the database credentials in environment variables:

```bash
USER=database_user PASS=database_password DB=database_name phpunit
```

**Warning**: This will empty all the tables! Never use this on a production database.
