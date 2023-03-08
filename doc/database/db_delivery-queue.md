Table delivery-queue
===========

Delivery data for posts for the batch processing

Fields
------

| Field   | Description                             | Type               | Null | Key | Default | Extra |
| ------- | --------------------------------------- | ------------------ | ---- | --- | ------- | ----- |
| gsid    | Target server                           | int unsigned       | NO   | PRI | NULL    |       |
| uri-id  | Delivered post                          | int unsigned       | NO   | PRI | NULL    |       |
| created |                                         | datetime           | YES  |     | NULL    |       |
| command |                                         | varbinary(32)      | YES  |     | NULL    |       |
| cid     | Target contact                          | int unsigned       | YES  |     | NULL    |       |
| uid     | Delivering user                         | mediumint unsigned | YES  |     | NULL    |       |
| failed  | Number of times the delivery has failed | tinyint            | YES  |     | 0       |       |

Indexes
------------

| Name         | Fields        |
| ------------ | ------------- |
| PRIMARY      | uri-id, gsid  |
| gsid_created | gsid, created |
| uid          | uid           |
| cid          | cid           |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| gsid | [gserver](help/database/db_gserver) | id |
| uri-id | [item-uri](help/database/db_item-uri) | id |
| cid | [contact](help/database/db_contact) | id |
| uid | [user](help/database/db_user) | uid |

Return to [database documentation](help/database)
