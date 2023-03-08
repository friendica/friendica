Table key-value
===========

A key value storage

Fields
------

| Field      | Description                  | Type          | Null | Key | Default | Extra |
| ---------- | ---------------------------- | ------------- | ---- | --- | ------- | ----- |
| k          |                              | varbinary(50) | NO   | PRI | NULL    |       |
| v          |                              | mediumtext    | YES  |     | NULL    |       |
| updated_at | timestamp of the last update | int unsigned  | NO   |     | NULL    |       |

Indexes
------------

| Name    | Fields |
| ------- | ------ |
| PRIMARY | k      |


Return to [database documentation](help/database)
