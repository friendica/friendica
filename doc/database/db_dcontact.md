Table dcontact
===========

Diaspora compatible contacts - used in the Diaspora implementation

Fields
------

| Field        | Description                                                   | Type           | Null | Key | Default             | Extra |
| ------------ | ------------------------------------------------------------- | -------------- | ---- | --- | ------------------- | ----- |
| url          | URL of the contact                                            | varbinary(255) | NO   | PRI | NULL                |       |
| uri-id       | Id of the item-uri table entry that contains the dcontact url | int unsigned   | YES  |     | NULL                |       |
| guid         | unique id                                                     | varbinary(255) | YES  |     | NULL                |       |
| addr         |                                                               | varchar(255)   | YES  |     | NULL                |       |
| alias        |                                                               | varchar(255)   | YES  |     | NULL                |       |
| nick         |                                                               | varchar(255)   | YES  |     | NULL                |       |
| name         |                                                               | varchar(255)   | YES  |     | NULL                |       |
| given-name   |                                                               | varchar(255)   | YES  |     | NULL                |       |
| family-name  |                                                               | varchar(255)   | YES  |     | NULL                |       |
| photo        |                                                               | varchar(255)   | YES  |     | NULL                |       |
| photo-medium |                                                               | varchar(255)   | YES  |     | NULL                |       |
| photo-small  |                                                               | varchar(255)   | YES  |     | NULL                |       |
| batch        |                                                               | varchar(255)   | YES  |     | NULL                |       |
| notify       |                                                               | varchar(255)   | YES  |     | NULL                |       |
| poll         |                                                               | varchar(255)   | YES  |     | NULL                |       |
| subscribe    |                                                               | varchar(255)   | YES  |     | NULL                |       |
| searchable   |                                                               | boolean        | YES  |     | NULL                |       |
| pubkey       |                                                               | text           | YES  |     | NULL                |       |
| baseurl      | baseurl of the diaspora contact                               | varchar(255)   | YES  |     | NULL                |       |
| gsid         | Global Server ID                                              | int unsigned   | YES  |     | NULL                |       |
| updated      |                                                               | datetime       | NO   |     | 0001-01-01 00:00:00 |       |

Indexes
------------

| Name    | Fields         |
| ------- | -------------- |
| PRIMARY | url            |
| addr    | addr(32)       |
| gsid    | gsid           |
| uri-id  | UNIQUE, uri-id |
| guid    | UNIQUE, guid   |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/database/db_item-uri) | id |
| gsid | [gserver](help/database/db_gserver) | id |

Return to [database documentation](help/database)
