# Table post-origin

Posts from local users

## Fields

| Field         | Description                                                  | Type               | Null | Key | Default             | Extra |
| ------------- | ------------------------------------------------------------ | ------------------ | ---- | --- | ------------------- | ----- |
| id            |                                                              | int unsigned       | NO   | PRI | NULL                |       |
| uri-id        | Id of the item-uri table entry that contains the item uri    | int unsigned       | NO   |     | NULL                |       |
| uid           | Owner id which owns this copy of the item                    | mediumint unsigned | NO   |     | NULL                |       |
| parent-uri-id | Id of the item-uri table that contains the parent uri        | int unsigned       | YES  |     | NULL                |       |
| thr-parent-id | Id of the item-uri table that contains the thread parent uri | int unsigned       | YES  |     | NULL                |       |
| created       | Creation timestamp.                                          | datetime           | NO   |     | 0001-01-01 00:00:00 |       |
| received      | datetime                                                     | datetime           | NO   |     | 0001-01-01 00:00:00 |       |
| gravity       |                                                              | tinyint unsigned   | NO   |     | 0                   |       |
| vid           | Id of the verb table entry that contains the activity verbs  | smallint unsigned  | YES  |     | NULL                |       |
| private       | 0=public, 1=private, 2=unlisted                              | tinyint unsigned   | NO   |     | 0                   |       |
| wall          | This item was posted to the wall of uid                      | boolean            | NO   |     | 0                   |       |

## Indexes

| Name              | Fields              |
| ----------------- | ------------------- |
| PRIMARY           | id                  |
| uid_uri-id        | UNIQUE, uid, uri-id |
| uri-id            | uri-id              |
| parent-uri-id     | parent-uri-id       |
| thr-parent-id     | thr-parent-id       |
| vid               | vid                 |
| parent-uri-id_uid | parent-uri-id, uid  |
| uid_wall_received | uid, wall, received |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |
| uid | [user](help/spec/database/db-user) | uid |
| parent-uri-id | [item-uri](help/spec/database/db-item-uri) | id |
| thr-parent-id | [item-uri](help/spec/database/db-item-uri) | id |
| vid | [verb](help/spec/database/db-verb) | id |

Return to [database documentation](help/spec/database/index)
