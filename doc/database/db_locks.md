Table locks
===========

| Field   | Description      | Type         | Null | Key | Default             | Extra          |
|---------|------------------|--------------|------|-----|---------------------|----------------|
| id      | sequential ID    | int(11)      | NO   | PRI | NULL                | auto_increment |
| name    |                  | varchar(128) | NO   |     |                     |                |
| locked  |                  | tinyint(1)   | NO   |     | 0                   |                |
| created |                  | datetime     | YES  |     | 0001-01-01 00:00:00 |                |

Return to [database documentation](help/database)
