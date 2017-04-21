Table mail
==========

| Field      | Description                                  | Type             | Null | Key | Default             | Extra           |
| ---------- | -------------------------------------------- | ---------------- | ---- | --- | ------------------- | --------------- |
| id         | sequential ID                                | int(10) unsigned | NO   | PRI | NULL                | auto_increment  |
| uid        | user.id of the owner of this data            | int(10) unsigned | NO   | MUL | 0                   |                 |
| guid       | A unique identifier for this private message | int(10) unsigned | NO   | MUL |                     |                 |
| from-name  | name of the sender                           | varchar(255)     | NO   |     |                     |                 |
| from-photo | contact photo link of the sender             | varchar(255)     | NO   |     |                     |                 |
| from-url   | profile linke of the sender                  | varchar(255)     | NO   |     |                     |                 |
| contact-id | contact.id                                   | varchar(255)     | NO   |     |                     |                 |
| convid     | conv.id                                      | int(11) unsigned | NO   | MUL | 0                   |                 |
| title      |                                              | varchar(255)     | NO   |     |                     |                 |
| body       |                                              | mediumtext       | NO   |     | NULL                |                 |
| seen       | if message visited it is 1                   | varchar(255)     | NO   |     | 0                   |                 |
| reply      |                                              | varchar(255)     | NO   | MUL | 0                   |                 |
| replied    |                                              | varchar(255)     | NO   |     | 0                   |                 |
| unknown    | if sender not in the contact table this is 1 | varchar(255)     | NO   |     | 0                   |                 |
| uri        |                                              | varchar(255)     | NO   | MUL |                     |                 |
| parent-uri |                                              | varchar(255)     | NO   | MUL |                     |                 |
| created    | creation time of the private message         | datetime         | NO   |     | 0001-01-01 00:00:00 |                 |

Return to [database documentation](help/database)
