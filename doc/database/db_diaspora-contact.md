Table diaspora-contact
===========

Diaspora compatible contacts - used in the Diaspora implementation

Fields
------

| Field             | Description                                                  | Type         | Null | Key | Default             | Extra |
| ----------------- | ------------------------------------------------------------ | ------------ | ---- | --- | ------------------- | ----- |
| uri-id            | Id of the item-uri table entry that contains the contact URL | int unsigned | NO   | PRI | NULL                |       |
| addr              |                                                              | varchar(255) | YES  |     | NULL                |       |
| alias             |                                                              | varchar(255) | YES  |     | NULL                |       |
| nick              |                                                              | varchar(255) | YES  |     | NULL                |       |
| name              |                                                              | varchar(255) | YES  |     | NULL                |       |
| given-name        |                                                              | varchar(255) | YES  |     | NULL                |       |
| family-name       |                                                              | varchar(255) | YES  |     | NULL                |       |
| photo             |                                                              | varchar(255) | YES  |     | NULL                |       |
| photo-medium      |                                                              | varchar(255) | YES  |     | NULL                |       |
| photo-small       |                                                              | varchar(255) | YES  |     | NULL                |       |
| batch             |                                                              | varchar(255) | YES  |     | NULL                |       |
| notify            |                                                              | varchar(255) | YES  |     | NULL                |       |
| poll              |                                                              | varchar(255) | YES  |     | NULL                |       |
| subscribe         |                                                              | varchar(255) | YES  |     | NULL                |       |
| searchable        |                                                              | boolean      | YES  |     | NULL                |       |
| pubkey            |                                                              | text         | YES  |     | NULL                |       |
| gsid              | Global Server ID                                             | int unsigned | YES  |     | NULL                |       |
| created           |                                                              | datetime     | NO   |     | 0001-01-01 00:00:00 |       |
| updated           |                                                              | datetime     | NO   |     | 0001-01-01 00:00:00 |       |
| interacting_count | Number of contacts this contact interactes with              | int unsigned | YES  |     | 0                   |       |
| interacted_count  | Number of contacts that interacted with this contact         | int unsigned | YES  |     | 0                   |       |
| post_count        | Number of posts and comments                                 | int unsigned | YES  |     | 0                   |       |

Indexes
------------

| Name    | Fields       |
| ------- | ------------ |
| PRIMARY | uri-id       |
| addr    | UNIQUE, addr |
| alias   | alias        |
| gsid    | gsid         |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/database/db_item-uri) | id |
| gsid | [gserver](help/database/db_gserver) | id |

Return to [database documentation](help/database)
