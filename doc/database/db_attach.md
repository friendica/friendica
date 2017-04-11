Table attach
============

| Field      | Description                                           | Type         | Null | Key | Default             | Extra           |
| ---------- | ------------------------------------------------------| ------------ | ---- | --- | ------------------- | --------------- |
| id         | generated index                                       | int(11)      | NO   | PRI | NULL                | auto_increment  |
| uid        | user_id of owner                                      | int(11)      | NO   |     | 0                   |                 |
| hash       | hash                                                  | varchar(64)  | NO   |     |                     |                 |
| filename   | filename of original                                  | varchar(255) | NO   |     |                     |                 |
| filetype   | mimetype                                              | varchar(64)  | NO   |     |                     |                 |
| filesize   | size in bytes                                         | int(11)      | NO   |     | 0                   |                 |
| data       | file data                                             | longblob     | NO   |     | NULL                |                 |
| created    | creation time                                         | datetime     | NO   |     | 0001-01-01 00:00:00 |                 |
| edited     | last edit time                                        | datetime     | NO   |     | 0001-01-01 00:00:00 |                 |
| allow_cid  | Access Control - list of allowed contact.id '<19><78> | mediumtext   | NO   |     | NULL                |                 |
| allow_gid  | Access Control - list of allowed groups               | mediumtext   | NO   |     | NULL                |                 |
| deny_cid   | Access Control - list of denied contact.id            | mediumtext   | NO   |     | NULL                |                 |
| deny_gid   | Access Control - list of denied groups                | mediumtext   | NO   |     | NULL                |                 |

Notes: Permissions are surrounded by angle chars. e.g. <4>

Return to [database documentation](help/database)
