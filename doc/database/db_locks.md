Table locks
===========

| Field   | Description      | Type             | Null | Key | Default             | Extra          |
|---------|------------------|------------------|------|-----|---------------------|----------------|
| id      | sequential ID    | int(11)          | NO   | PRI | NULL                | auto_increment |
| name    |                  | varchar(128)     | NO   |     |                     |                |
| locked  |                  | tinyint(1)       | NO   |     | 0                   |                |
| pid     | Process ID       | int(10) unsigned | NO   |     | 0                   |                |

Return to [database documentation](help/database)
