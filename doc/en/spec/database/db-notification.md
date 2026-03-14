# Table notification

notifications

## Fields

| Field         | Description                                                                    | Type               | Null | Key | Default | Extra          |
| ------------- | ------------------------------------------------------------------------------ | ------------------ | ---- | --- | ------- | -------------- |
| id            | sequential ID                                                                  | int unsigned       | NO   | PRI | NULL    | auto_increment |
| uid           | Owner User id                                                                  | mediumint unsigned | YES  |     | NULL    |                |
| vid           | Id of the verb table entry that contains the activity verbs                    | smallint unsigned  | YES  |     | NULL    |                |
| type          |                                                                                | smallint unsigned  | YES  |     | NULL    |                |
| actor-id      | Link to the contact table with uid=0 of the actor that caused the notification | int unsigned       | YES  |     | NULL    |                |
| target-uri-id | Item-uri id of the related post                                                | int unsigned       | YES  |     | NULL    |                |
| parent-uri-id | Item-uri id of the parent of the related post                                  | int unsigned       | YES  |     | NULL    |                |
| created       |                                                                                | datetime           | YES  |     | NULL    |                |
| seen          | Seen on the desktop                                                            | boolean            | YES  |     | 0       |                |
| dismissed     | Dismissed via the API                                                          | boolean            | YES  |     | 0       |                |

## Indexes

| Name                                | Fields                                          |
| ----------------------------------- | ----------------------------------------------- |
| PRIMARY                             | id                                              |
| uid_vid_type_actor-id_target-uri-id | UNIQUE, uid, vid, type, actor-id, target-uri-id |
| vid                                 | vid                                             |
| actor-id                            | actor-id                                        |
| target-uri-id                       | target-uri-id                                   |
| parent-uri-id                       | parent-uri-id                                   |
| seen_uid                            | seen, uid                                       |
| uid_type_parent-uri-id_actor-id     | uid, type, parent-uri-id, actor-id              |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/spec/database/db-user) | uid |
| vid | [verb](help/spec/database/db-verb) | id |
| actor-id | [contact](help/spec/database/db-contact) | id |
| target-uri-id | [item-uri](help/spec/database/db-item-uri) | id |
| parent-uri-id | [item-uri](help/spec/database/db-item-uri) | id |

Return to [database documentation](help/spec/database/index)
