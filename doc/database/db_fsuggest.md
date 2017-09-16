Table fsuggest
==============

| Field   | Description | Type         | Null | Key | Default             | Extra           |
| ------- | ----------- | ------------ | ---- | --- | ------------------- | --------------- |
| id      |             | int(11)      | NO   | PRI | NULL                | auto_increment  |
| uid     |             | int(11)      | NO   |     | 0                   |                 |
| cid     |             | int(11)      | NO   |     | 0                   |                 |
| name    |             | varchar(255) | NO   |     |                     |                 |
| url     |             | varchar(255) | NO   |     |                     |                 |
| request |             | varchar(255) | NO   |     |                     |                 |
| photo   |             | varchar(255) | NO   |     |                     |                 |
| note    |             | text         | NO   |     | NULL                |                 |
| created |             | datetime     | NO   |     | 0001-01-01 00:00:00 |                 |

Return to [database documentation](help/database)
