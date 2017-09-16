Table event
===========

| Field      | Description                                            | Type                | Null | Key | Default             | Extra           |
| ---------- | ----------------------------------------------- -------| ------------------- | ---- | --- | ------------------- | --------------- |
| id         | sequential ID                                          | int(11)             | NO   | PRI | NULL                | auto_increment  |
| uid        | user_id of the owner of this data                      | int(11)             | NO   | MUL | 0                   |                 |
| cid        | contact_id (ID of the contact in contact table)        | int(11)             | NO   |     | 0                   |                 |
| uri        |                                                        | varchar(255)        | NO   |     |                     |                 |
| created    |  creation time                                         | datetime            | NO   |     | 0001-01-01 00:00:00 |                 |
| edited     | last edit time                                         | datetime            | NO   |     | 0001-01-01 00:00:00 |                 |
| start      | event start time                                       | datetime            | NO   |     | 0001-01-01 00:00:00 |                 |
| finish     | event end time                                         | datetime            | NO   |     | 0001-01-01 00:00:00 |                 |
| summary    |  short description or title of the event               | text                | NO   |     | NULL                |                 |
| desc       | event description                                      | text                | NO   |     | NULL                |                 |
| location   | event location                                         | text                | NO   |     | NULL                |                 |
| type       | event or birthday                                      | varchar(255)        | NO   |     |                     |                 |
| nofinish   | if event does have no end this is 1                    | tinyint(1)          | NO   |     | 0                   |                 |
| adjust     | adjust to timezone of the recipient (0 or 1)           | tinyint(1)          | NO   |     | 1                   |                 |
| ignore     | 0 or 1                                                 | tinyint(1) unsigned | NO   |     | 0                   |                 |
| allow_cid  | Access Control - list of allowed contact.id '<19><78>' | mediumtext          | NO   |     | NULL                |                 |
| allow_gid  | Access Control - list of allowed groups                | mediumtext          | NO   |     | NULL                |                 |
| deny_cid   | Access Control - list of denied contact.id             | mediumtext          | NO   |     | NULL                |                 |
| deny_gid   | Access Control - list of denied groups                 | mediumtext          | NO   |     | NULL                |                 |

Return to [database documentation](help/database)
