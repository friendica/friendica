Table inbox-entry
===========

Incoming activity

Fields
------

| Field              | Description                            | Type             | Null | Key | Default | Extra          |
| ------------------ | -------------------------------------- | ---------------- | ---- | --- | ------- | -------------- |
| id                 | sequential ID                          | int unsigned     | NO   | PRI | NULL    | auto_increment |
| activity-id        | id of the incoming activity            | varbinary(383)   | YES  |     | NULL    |                |
| object-id          |                                        | varbinary(383)   | YES  |     | NULL    |                |
| in-reply-to-id     |                                        | varbinary(383)   | YES  |     | NULL    |                |
| context            |                                        | varbinary(383)   | YES  |     | NULL    |                |
| conversation       |                                        | varbinary(383)   | YES  |     | NULL    |                |
| type               | Type of the activity                   | varchar(64)      | YES  |     | NULL    |                |
| object-type        | Type of the object activity            | varchar(64)      | YES  |     | NULL    |                |
| object-object-type | Type of the object's object activity   | varchar(64)      | YES  |     | NULL    |                |
| received           | Receiving date                         | datetime         | YES  |     | NULL    |                |
| activity           | The JSON activity                      | mediumtext       | YES  |     | NULL    |                |
| signer             |                                        | varchar(255)     | YES  |     | NULL    |                |
| push               | Is the entry pushed or have pulled it? | boolean          | YES  |     | NULL    |                |
| trust              | Do we trust this entry?                | boolean          | YES  |     | NULL    |                |
| wid                | Workerqueue id                         | int unsigned     | YES  |     | NULL    |                |
| retrial            | Retrial counter                        | tinyint unsigned | YES  |     | 0       |                |

Indexes
------------

| Name        | Fields              |
| ----------- | ------------------- |
| PRIMARY     | id                  |
| activity-id | UNIQUE, activity-id |
| object-id   | object-id           |
| received    | received            |
| wid         | wid                 |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| wid | [workerqueue](help/database/db_workerqueue) | id |

Return to [database documentation](help/database)
