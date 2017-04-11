Table term
==========

| Field    | Description   | Type                | Null | Key | Default             | Extra          |
|----------| ------------- |---------------------|------|-----|---------------------|----------------|
| tid      |               | int(10) unsigned    | NO   | PRI | NULL                | auto_increment |
| oid      |               | int(10) unsigned    | NO   | MUL | 0                   |                |
| otype    |               | tinyint(3) unsigned | NO   | MUL | 0                   |                |
| type     |               | tinyint(3) unsigned | NO   | MUL | 0                   |                |
| term     |               | varchar(255)        | NO   |     |                     |                |
| url      |               | varchar(255)        | NO   |     |                     |                |
| aid      |               | int(10) unsigned    | NO   |     | 0                   |                |
| uid      |               | int(10) unsigned    | NO   | MUL | 0                   |                |
| guid     |               | varchar(255)        | NO   | MUL |                     |                |
| created  |               | datetime            | NO   |     | 0001-01-01 00:00:00 |                |
| received |               | datetime            | NO   |     | 0001-01-01 00:00:00 |                |
| global   |               | tinyint(1)          | NO   |     | 0                   |                |

Return to [database documentation](help/database)
