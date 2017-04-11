Table queue
===========

| Field   | Description      | Type        | Null | Key | Default             | Extra          |
|---------|------------------|-------------|------|-----|---------------------|----------------|
| id      | sequential ID    | int(11)     | NO   | PRI | NULL                | auto_increment |
| cid     |                  | int(11)     | NO   | MUL | 0                   |                |
| network |                  | varchar(32) | NO   | MUL |                     |                |
| created |                  | datetime    | NO   | MUL | 0001-01-01 00:00:00 |                |
| last    |                  | datetime    | NO   | MUL | 0001-01-01 00:00:00 |                |
| content |                  | mediumtext  | NO   |     | NULL                |                |
| batch   |                  | tinyint(1)  | NO   | MUL | 0                   |                |

Return to [database documentation](help/database)
