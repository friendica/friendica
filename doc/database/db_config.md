Table config
===========

main configuration storage

Fields
------

| Field | Description | Type         | Null | Key | Default | Extra          |
| ----- | ----------- | ------------ | ---- | --- | ------- | -------------- |
| id    |             | int unsigned | NO   | PRI | NULL    | auto_increment |
| cat   |             | varchar(50)  | NO   |     |         |                |
| k     |             | varchar(50)  | NO   |     |         |                |
| v     |             | mediumtext   | YES  |     | NULL    |                |

Indexes
------------

| Name    | Fields         |
| ------- | -------------- |
| PRIMARY | id             |
| cat_k   | UNIQUE, cat, k |


Return to [database documentation](help/database)
