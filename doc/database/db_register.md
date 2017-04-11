Table register
==============

| Field    | Description   | Type             | Null | Key | Default             | Extra           |
| -------- | ------------- | ---------------- | ---- | --- | ------------------- | --------------- |
| id       | sequential ID | int(11) unsigned | NO   | PRI | NULL                | auto_increment  |
| hash     |               | varchar(255)     | NO   |     |                     |                 |
| created  |               | datetime         | NO   |     | 0001-01-01 00:00:00 |                 |
| uid      | user.id       | int(11) unsigned | NO   |     |                     |                 |
| password |               | varchar(255)     | NO   |     |                     |                 |
| language |               | varchar(16)      | NO   |     |                     |                 |

Return to [database documentation](help/database)
