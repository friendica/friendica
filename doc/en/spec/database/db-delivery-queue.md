# Table delivery-queue

Delivery data for posts for the batch processing

## Fields

| Field   | Description                             | Type               | Null | Key | Default | Extra |
| ------- | --------------------------------------- | ------------------ | ---- | --- | ------- | ----- |
| gsid    | Target server                           | int unsigned       | NO   | PRI | NULL    |       |
| uri-id  | Delivered post                          | int unsigned       | NO   | PRI | NULL    |       |
| created |                                         | datetime           | YES  |     | NULL    |       |
| command |                                         | varbinary(32)      | YES  |     | NULL    |       |
| cid     | Target contact                          | int unsigned       | YES  |     | NULL    |       |
| uid     | Delivering user                         | mediumint unsigned | YES  |     | NULL    |       |
| failed  | Number of times the delivery has failed | tinyint            | YES  |     | 0       |       |

## Indexes

| Name         | Fields        |
| ------------ | ------------- |
| PRIMARY      | uri-id, gsid  |
| gsid_created | gsid, created |
| uid          | uid           |
| cid          | cid           |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| gsid | [gserver](help/spec/database/db-gserver) | id |
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |
| cid | [contact](help/spec/database/db-contact) | id |
| uid | [user](help/spec/database/db-user) | uid |

Return to [database documentation](help/spec/database/index)
