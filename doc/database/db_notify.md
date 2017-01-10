Table notify
============

| Field      | Description                       | Type         | Null | Key | Default             | Extra           |
| ---------- | --------------------------------- | ------------ | ---- | --- | ------------------- | --------------- |
| id         | sequential ID                     | int(11)      | NO   | PRI | NULL                | auto_increment  |
| hash       |                                   | varchar(64)  | NO   |     |                     |                 |
| type       |                                   | int(11)      | NO   |     | 0                   |                 |
| name       |                                   | varchar(255) | NO   |     |                     |                 |
| url        |                                   | varchar(255) | NO   |     |                     |                 |
| photo      |                                   | varchar(255) | NO   |     |                     |                 |
| date       |                                   | datetime     | NO   |     | 0000-00-00 00:00:00 |                 |
| msg        |                                   | mediumtext   | YES  |     | NULL                |                 |
| uid        | user.id of the owner of this data | int(11)      | NO   | MUL | 0                   |                 |
| link       |                                   | varchar(255) | NO   |     |                     |                 |
| iid        | item.id                           | int(11)      | NO   |     | 0                   |                 |
| parent     |                                   | int(11)      | NO   |     | 0                   |                 |
| seen       |                                   | tinyint(1)   | NO   |     | 0                   |                 |
| verb       |                                   | varchar(255) | NO   |     |                     |                 |
| otype      |                                   | varchar(16)  | NO   |     |                     |                 |
| name_cache | Cached bbcode parsing of name     | tinytext     | YES  |     | NULL                |                 |
| msg_cache  | Cached bbcode parsing of msg      | mediumtext   | YES  |     | NULL                |                 |

Return to [database documentation](help/database)
