Table report
===========



Fields
------

| Field       | Description                                     | Type               | Null | Key | Default             | Extra          |
| ----------- | ----------------------------------------------- | ------------------ | ---- | --- | ------------------- | -------------- |
| id          | sequential ID                                   | int unsigned       | NO   | PRI | NULL                | auto_increment |
| uid         | Reporting user                                  | mediumint unsigned | YES  |     | NULL                |                |
| reporter-id | Reporting contact                               | int unsigned       | YES  |     | NULL                |                |
| cid         | Reported contact                                | int unsigned       | NO   |     | NULL                |                |
| comment     | Report                                          | text               | YES  |     | NULL                |                |
| category    | Category of the report (spam, violation, other) | varchar(20)        | YES  |     | NULL                |                |
| rules       | Violated rules                                  | text               | YES  |     | NULL                |                |
| forward     | Forward the report to the remote server         | boolean            | YES  |     | NULL                |                |
| created     |                                                 | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| status      | Status of the report                            | tinyint unsigned   | YES  |     | NULL                |                |

Indexes
------------

| Name        | Fields      |
| ----------- | ----------- |
| PRIMARY     | id          |
| uid         | uid         |
| cid         | cid         |
| reporter-id | reporter-id |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/database/db_user) | uid |
| reporter-id | [contact](help/database/db_contact) | id |
| cid | [contact](help/database/db_contact) | id |

Return to [database documentation](help/database)
