Table workerqueue
=================

| Field     | Description      | Type                | Null | Key | Default             | Extra          |
|-----------|------------------|---------------------|------|-----|---------------------|----------------|
| id        | sequential ID    | int(11)             | NO   | PRI | NULL                | auto_increment |
| parameter |                  | text                | NO   |     | NULL                |                |
| priority  |                  | tinyint(3) unsigned | NO   |     | 0                   |                |
| created   |                  | datetime            | NO   | MUL | 0001-01-01 00:00:00 |                |
| pid       |                  | int(11)             | NO   |     | 0                   |                |
| executed  |                  | datetime            | NO   |     | 0001-01-01 00:00:00 |                |

Return to [database documentation](help/database)
