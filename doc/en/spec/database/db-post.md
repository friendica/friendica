# Table post

Structure for all posts

## Fields

| Field         | Description                                                                          | Type              | Null | Key | Default             | Extra |
| ------------- | ------------------------------------------------------------------------------------ | ----------------- | ---- | --- | ------------------- | ----- |
| uri-id        | Id of the item-uri table entry that contains the item uri                            | int unsigned      | NO   | PRI | NULL                |       |
| parent-uri-id | Id of the item-uri table that contains the parent uri                                | int unsigned      | YES  |     | NULL                |       |
| thr-parent-id | Id of the item-uri table that contains the thread parent uri                         | int unsigned      | YES  |     | NULL                |       |
| external-id   | Id of the item-uri table entry that contains the external uri                        | int unsigned      | YES  |     | NULL                |       |
| replies-id    | Id of the item-uri table entry that contains the endpoint for the replies collection | int unsigned      | YES  |     | NULL                |       |
| created       | Creation timestamp.                                                                  | datetime          | NO   |     | 0001-01-01 00:00:00 |       |
| edited        | Date of last edit (default is created)                                               | datetime          | NO   |     | 0001-01-01 00:00:00 |       |
| received      | datetime                                                                             | datetime          | NO   |     | 0001-01-01 00:00:00 |       |
| gravity       |                                                                                      | tinyint unsigned  | NO   |     | 0                   |       |
| network       | Network from where the item comes from                                               | char(4)           | NO   |     |                     |       |
| owner-id      | Link to the contact table with uid=0 of the owner of this item                       | int unsigned      | NO   |     | 0                   |       |
| author-id     | Link to the contact table with uid=0 of the author of this item                      | int unsigned      | NO   |     | 0                   |       |
| causer-id     | Link to the contact table with uid=0 of the contact that caused the item creation    | int unsigned      | YES  |     | NULL                |       |
| post-type     | Post type (personal note, image, article, ...)                                       | tinyint unsigned  | NO   |     | 0                   |       |
| vid           | Id of the verb table entry that contains the activity verbs                          | smallint unsigned | YES  |     | NULL                |       |
| private       | 0=public, 1=private, 2=unlisted                                                      | tinyint unsigned  | NO   |     | 0                   |       |
| global        |                                                                                      | boolean           | NO   |     | 0                   |       |
| visible       |                                                                                      | boolean           | NO   |     | 0                   |       |
| deleted       | item has been marked for deletion                                                    | boolean           | NO   |     | 0                   |       |

## Indexes

| Name          | Fields        |
| ------------- | ------------- |
| PRIMARY       | uri-id        |
| parent-uri-id | parent-uri-id |
| thr-parent-id | thr-parent-id |
| external-id   | external-id   |
| replies-id    | replies-id    |
| owner-id      | owner-id      |
| author-id     | author-id     |
| causer-id     | causer-id     |
| vid           | vid           |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |
| parent-uri-id | [item-uri](help/spec/database/db-item-uri) | id |
| thr-parent-id | [item-uri](help/spec/database/db-item-uri) | id |
| external-id | [item-uri](help/spec/database/db-item-uri) | id |
| replies-id | [item-uri](help/spec/database/db-item-uri) | id |
| owner-id | [contact](help/spec/database/db-contact) | id |
| author-id | [contact](help/spec/database/db-contact) | id |
| causer-id | [contact](help/spec/database/db-contact) | id |
| vid | [verb](help/spec/database/db-verb) | id |

Return to [database documentation](help/spec/database/index)
