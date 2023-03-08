Table inbox-status
===========

Status of ActivityPub inboxes

Fields
------

| Field    | Description                          | Type           | Null | Key | Default             | Extra |
| -------- | ------------------------------------ | -------------- | ---- | --- | ------------------- | ----- |
| url      | URL of the inbox                     | varbinary(383) | NO   | PRI | NULL                |       |
| uri-id   | Item-uri id of inbox url             | int unsigned   | YES  |     | NULL                |       |
| gsid     | ID of the related server             | int unsigned   | YES  |     | NULL                |       |
| created  | Creation date of this entry          | datetime       | NO   |     | 0001-01-01 00:00:00 |       |
| success  | Date of the last successful delivery | datetime       | NO   |     | 0001-01-01 00:00:00 |       |
| failure  | Date of the last failed delivery     | datetime       | NO   |     | 0001-01-01 00:00:00 |       |
| previous | Previous delivery date               | datetime       | NO   |     | 0001-01-01 00:00:00 |       |
| archive  | Is the inbox archived?               | boolean        | NO   |     | 0                   |       |
| shared   | Is it a shared inbox?                | boolean        | NO   |     | 0                   |       |

Indexes
------------

| Name    | Fields |
| ------- | ------ |
| PRIMARY | url    |
| uri-id  | uri-id |
| gsid    | gsid   |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/database/db_item-uri) | id |
| gsid | [gserver](help/database/db_gserver) | id |

Return to [database documentation](help/database)
