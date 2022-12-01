Table report
===========



Fields
------

| Field   | Description                             | Type               | Null | Key | Default             | Extra          |
| ------- | --------------------------------------- | ------------------ | ---- | --- | ------------------- | -------------- |
| id      | sequential ID                           | int unsigned       | NO   | PRI | NULL                | auto_increment |
| uid     | Reporting user                          | mediumint unsigned | YES  |     | NULL                |                |
| cid     | Reported contact                        | int unsigned       | NO   |     | NULL                |                |
| comment | Report                                  | text               | YES  |     | NULL                |                |
| forward | Forward the report to the remote server | boolean            | YES  |     | NULL                |                |
| created |                                         | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| status  | Status of the report                    | tinyint unsigned   | YES  |     | NULL                |                |

Indexes
------------

| Name    | Fields |
| ------- | ------ |
| PRIMARY | id     |
| uid     | uid    |
| cid     | cid    |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/database/db_user) | uid |
| cid | [contact](help/database/db_contact) | id |

Return to [database documentation](help/database)
