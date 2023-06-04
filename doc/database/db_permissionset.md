Table permissionset
===========



Fields
------

| Field     | Description                                            | Type               | Null | Key | Default | Extra          |
| --------- | ------------------------------------------------------ | ------------------ | ---- | --- | ------- | -------------- |
| id        | sequential ID                                          | int unsigned       | NO   | PRI | NULL    | auto_increment |
| uid       | Owner id of this permission set                        | mediumint unsigned | NO   |     | 0       |                |
| allow_cid | Access Control - list of allowed contact.id '<19><78>' | mediumtext         | YES  |     | NULL    |                |
| allow_gid | Access Control - list of allowed circles               | mediumtext         | YES  |     | NULL    |                |
| deny_cid  | Access Control - list of denied contact.id             | mediumtext         | YES  |     | NULL    |                |
| deny_gid  | Access Control - list of denied circles                | mediumtext         | YES  |     | NULL    |                |

Indexes
------------

| Name                                      | Fields                                                        |
| ----------------------------------------- | ------------------------------------------------------------- |
| PRIMARY                                   | id                                                            |
| uid_allow_cid_allow_gid_deny_cid_deny_gid | uid, allow_cid(50), allow_gid(30), deny_cid(50), deny_gid(30) |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/database/db_user) | uid |

Return to [database documentation](help/database)
