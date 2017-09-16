Table fcontact
==============

| Field    | Description   | Type             | Null | Key | Default             | Extra           |
| -------- | ------------- | ---------------- | ---- | --- | ------------------- | --------------- |
| id       | sequential ID | int(10) unsigned | NO   | PRI | NULL                | auto_increment  |
| guid     | unique id     | varchar(64)      | NO   |     |                     |                 |
| url      |               | varchar(255)     | NO   |     |                     |                 |
| name     |               | varchar(255)     | NO   |     |                     |                 |
| photo    |               | varchar(255)     | NO   |     |                     |                 |
| request  |               | varchar(255)     | NO   |     |                     |                 |
| nick     |               | varchar(255)     | NO   |     |                     |                 |
| addr     |               | varchar(255)     | NO   | MUL |                     |                 |
| batch    |               | varchar(255)     | NO   |     |                     |                 |
| notify   |               | varchar(255)     | NO   |     |                     |                 |
| poll     |               | varchar(255)     | NO   |     |                     |                 |
| confirm  |               | varchar(255)     | NO   |     |                     |                 |
| priority |               | tinyint(1)       | NO   |     | 0                   |                 |
| network  |               | varchar(32)      | NO   |     |                     |                 |
| alias    |               | varchar(255)     | NO   |     |                     |                 |
| pubkey   |               | text             | NO   |     | NULL                |                 |
| updated  |               | datetime         | NO   |     | 0001-01-01 00:00:00 |                 |

Return to [database documentation](help/database)
