Table post-user
===========

User specific post data

Fields
------

| Field             | Description                                                                          | Type               | Null | Key | Default             | Extra          |
| ----------------- | ------------------------------------------------------------------------------------ | ------------------ | ---- | --- | ------------------- | -------------- |
| id                |                                                                                      | int unsigned       | NO   | PRI | NULL                | auto_increment |
| uri-id            | Id of the item-uri table entry that contains the item uri                            | int unsigned       | NO   |     | NULL                |                |
| parent-uri-id     | Id of the item-uri table that contains the parent uri                                | int unsigned       | YES  |     | NULL                |                |
| thr-parent-id     | Id of the item-uri table that contains the thread parent uri                         | int unsigned       | YES  |     | NULL                |                |
| external-id       | Id of the item-uri table entry that contains the external uri                        | int unsigned       | YES  |     | NULL                |                |
| replies-id        | Id of the item-uri table entry that contains the endpoint for the replies collection | int unsigned       | YES  |     | NULL                |                |
| created           | Creation timestamp.                                                                  | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| edited            | Date of last edit (default is created)                                               | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| received          | datetime                                                                             | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| gravity           |                                                                                      | tinyint unsigned   | NO   |     | 0                   |                |
| network           | Network from where the item comes from                                               | char(4)            | NO   |     |                     |                |
| owner-id          | Link to the contact table with uid=0 of the owner of this item                       | int unsigned       | NO   |     | 0                   |                |
| author-id         | Link to the contact table with uid=0 of the author of this item                      | int unsigned       | NO   |     | 0                   |                |
| causer-id         | Link to the contact table with uid=0 of the contact that caused the item creation    | int unsigned       | YES  |     | NULL                |                |
| post-type         | Post type (personal note, image, article, ...)                                       | tinyint unsigned   | NO   |     | 0                   |                |
| post-reason       | Reason why the post arrived at the user                                              | tinyint unsigned   | NO   |     | 0                   |                |
| vid               | Id of the verb table entry that contains the activity verbs                          | smallint unsigned  | YES  |     | NULL                |                |
| private           | 0=public, 1=private, 2=unlisted                                                      | tinyint unsigned   | NO   |     | 0                   |                |
| restrictions      | Bit array of post restrictions (1 = Reply, 2 = Like, 4 = Announce)                   | tinyint unsigned   | YES  |     | NULL                |                |
| global            |                                                                                      | boolean            | NO   |     | 0                   |                |
| visible           |                                                                                      | boolean            | NO   |     | 0                   |                |
| deleted           | item has been marked for deletion                                                    | boolean            | NO   |     | 0                   |                |
| uid               | Owner id which owns this copy of the item                                            | mediumint unsigned | NO   |     | NULL                |                |
| protocol          | Protocol used to deliver the item for this user                                      | tinyint unsigned   | YES  |     | NULL                |                |
| contact-id        | contact.id                                                                           | int unsigned       | NO   |     | 0                   |                |
| event-id          | Used to link to the event.id                                                         | int unsigned       | YES  |     | NULL                |                |
| unseen            | post has not been seen                                                               | boolean            | NO   |     | 1                   |                |
| hidden            | Marker to hide the post from the user                                                | boolean            | NO   |     | 0                   |                |
| notification-type |                                                                                      | smallint unsigned  | NO   |     | 0                   |                |
| wall              | This item was posted to the wall of uid                                              | boolean            | NO   |     | 0                   |                |
| origin            | item originated at this site                                                         | boolean            | NO   |     | 0                   |                |
| psid              | ID of the permission set of this post                                                | int unsigned       | YES  |     | NULL                |                |

Indexes
------------

| Name                  | Fields                  |
| --------------------- | ----------------------- |
| PRIMARY               | id                      |
| uid_uri-id            | UNIQUE, uid, uri-id     |
| uri-id_origin_deleted | uri-id, origin, deleted |
| parent-uri-id         | parent-uri-id           |
| thr-parent-id         | thr-parent-id           |
| external-id           | external-id             |
| replies-id            | replies-id              |
| owner-id              | owner-id                |
| author-id             | author-id               |
| causer-id             | causer-id               |
| vid                   | vid                     |
| contact-id            | contact-id              |
| event-id              | event-id                |
| psid                  | psid                    |
| author-id_uid         | author-id, uid          |
| author-id_created     | author-id, created      |
| owner-id_created      | owner-id, created       |
| parent-uri-id_uid     | parent-uri-id, uid      |
| uid_wall_received     | uid, wall, received     |
| uid_contactid         | uid, contact-id         |
| uid_unseen_contactid  | uid, unseen, contact-id |
| uid_unseen            | uid, unseen             |
| uid_hidden_uri-id     | uid, hidden, uri-id     |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/database/db_item-uri) | id |
| parent-uri-id | [item-uri](help/database/db_item-uri) | id |
| thr-parent-id | [item-uri](help/database/db_item-uri) | id |
| external-id | [item-uri](help/database/db_item-uri) | id |
| replies-id | [item-uri](help/database/db_item-uri) | id |
| owner-id | [contact](help/database/db_contact) | id |
| author-id | [contact](help/database/db_contact) | id |
| causer-id | [contact](help/database/db_contact) | id |
| vid | [verb](help/database/db_verb) | id |
| uid | [user](help/database/db_user) | uid |
| contact-id | [contact](help/database/db_contact) | id |
| event-id | [event](help/database/db_event) | id |
| psid | [permissionset](help/database/db_permissionset) | id |

Return to [database documentation](help/database)
