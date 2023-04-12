# 主题

* [首页](help)

你可以使用 [PHPUnit](https://phpunit.de/) 运行单元测试：

```bash
phpunit
```

一些测试需要访问 MySQL 数据库。你可以在环境变量中指定数据库凭据：

```bash
USER=database_user PASS=database_password DB=database_name phpunit
```

**警告**：这将清空所有表！永远不要在生产数据库上使用此功能。