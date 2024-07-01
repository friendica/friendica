Table post-thread
===========

Thread related data

Fields
------

| Field           | Description                                                                                             | Type         | Null | Key | Default             | Extra |
| --------------- | ------------------------------------------------------------------------------------------------------- | ------------ | ---- | --- | ------------------- | ----- |
| uri-id          | Id of the item-uri table entry that contains the item uri                                               | int unsigned | NO   | PRI | NULL                |       |
| context-id      | Id of the item-uri table entry that contains the endpoint for the context collection                    | int unsigned | YES  |     | NULL                |       |
| conversation-id | Id of the item-uri table entry that contains the conversation uri                                       | int unsigned | YES  |     | NULL                |       |
| owner-id        | Item owner                                                                                              | int unsigned | NO   |     | 0                   |       |
| author-id       | Item author                                                                                             | int unsigned | NO   |     | 0                   |       |
| causer-id       | Link to the contact table with uid=0 of the contact that caused the item creation                       | int unsigned | YES  |     | NULL                |       |
| network         |                                                                                                         | char(4)      | NO   |     |                     |       |
| created         |                                                                                                         | datetime     | NO   |     | 0001-01-01 00:00:00 |       |
| received        |                                                                                                         | datetime     | NO   |     | 0001-01-01 00:00:00 |       |
| changed         | Date that something in the conversation changed, indicating clients should fetch the conversation again | datetime     | NO   |     | 0001-01-01 00:00:00 |       |
| commented       |                                                                                                         | datetime     | NO   |     | 0001-01-01 00:00:00 |       |

Indexes
------------

| Name            | Fields          |
| --------------- | --------------- |
| PRIMARY         | uri-id          |
| context-id      | context-id      |
| conversation-id | conversation-id |
| owner-id        | owner-id        |
| author-id       | author-id       |
| causer-id       | causer-id       |
| received        | received        |
| commented       | commented       |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/database/db_item-uri) | id |
| context-id | [item-uri](help/database/db_item-uri) | id |
| conversation-id | [item-uri](help/database/db_item-uri) | id |
| owner-id | [contact](help/database/db_contact) | id |
| author-id | [contact](help/database/db_contact) | id |
| causer-id | [contact](help/database/db_contact) | id |

Return to [database documentation](help/database)
