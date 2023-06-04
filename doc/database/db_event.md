Table event
===========

Events

Fields
------

| Field     | Description                                                | Type               | Null | Key | Default             | Extra          |
| --------- | ---------------------------------------------------------- | ------------------ | ---- | --- | ------------------- | -------------- |
| id        | sequential ID                                              | int unsigned       | NO   | PRI | NULL                | auto_increment |
| guid      |                                                            | varbinary(255)     | NO   |     |                     |                |
| uid       | Owner User id                                              | mediumint unsigned | NO   |     | 0                   |                |
| cid       | contact_id (ID of the contact in contact table)            | int unsigned       | NO   |     | 0                   |                |
| uri       |                                                            | varbinary(383)     | NO   |     |                     |                |
| uri-id    | Id of the item-uri table entry that contains the event uri | int unsigned       | YES  |     | NULL                |                |
| created   | creation time                                              | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| edited    | last edit time                                             | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| start     | event start time                                           | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| finish    | event end time                                             | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| summary   | short description or title of the event                    | text               | YES  |     | NULL                |                |
| desc      | event description                                          | text               | YES  |     | NULL                |                |
| location  | event location                                             | text               | YES  |     | NULL                |                |
| type      | event or birthday                                          | varchar(20)        | NO   |     |                     |                |
| nofinish  | if event does have no end this is 1                        | boolean            | NO   |     | 0                   |                |
| ignore    | 0 or 1                                                     | boolean            | NO   |     | 0                   |                |
| allow_cid | Access Control - list of allowed contact.id '<19><78>'     | mediumtext         | YES  |     | NULL                |                |
| allow_gid | Access Control - list of allowed circles                   | mediumtext         | YES  |     | NULL                |                |
| deny_cid  | Access Control - list of denied contact.id                 | mediumtext         | YES  |     | NULL                |                |
| deny_gid  | Access Control - list of denied circles                    | mediumtext         | YES  |     | NULL                |                |

Indexes
------------

| Name      | Fields     |
| --------- | ---------- |
| PRIMARY   | id         |
| uid_start | uid, start |
| cid       | cid        |
| uri-id    | uri-id     |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/database/db_user) | uid |
| cid | [contact](help/database/db_contact) | id |
| uri-id | [item-uri](help/database/db_item-uri) | id |

Return to [database documentation](help/database)
