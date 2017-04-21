Table glink
===========

| Field   | Description      | Type             | Null | Key | Default             | Extra          |
|---------|------------------|------------------|------|-----|---------------------|----------------|
| id      | sequential ID    | int(10) unsigned | NO   | PRI | NULL                | auto_increment |
| cid     |                  | int(11)          | NO   | MUL | 0                   |                |
| uid     |                  | int(11)          | NO   |     | 0                   |                |
| gcid    |                  | int(11)          | NO   | MUL | 0                   |                |
| zcid    |                  | int(11)          | NO   | MUL | 0                   |                |
| updated |                  | datetime         | NO   |     | 0001-01-01 00:00:00 |                |

Return to [database documentation](help/database)
