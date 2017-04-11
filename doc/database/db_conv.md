Table conv
==========

| Field   | Description                               | Type             | Null | Key | Default             | Extra           |
| ------- | ----------------------------------------- | ---------------- | ---- | --- | ------------------- | --------------- |
| id      | sequential ID                             | int(10) unsigned | NO   | PRI | NULL                | auto_increment  |
| guid    | A unique identifier for this conversation | varchar(64)      | NO   |     |                     |                 |
| recips  | sender_handle;recipient_handle            | mediumtext       | NO   |     | NULL                |                 |
| uid     | user_id of the owner of this data         | int(11)          | NO   | MUL | 0                   |                 |
| creator | handle of creator                         | varchar(255)     | NO   |     |                     |                 |
| created | creation timestamp                        | datetime         | NO   |     | 0001-01-01 00:00:00 |                 |
| updated | edited timestamp                          | datetime         | NO   |     | 0001-01-01 00:00:00 |                 |
| subject | subject of initial message                | mediumtext       | NO   |     | NULL                |                 |

Return to [database documentation](help/database)
