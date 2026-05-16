# Table post-delivery

Delivery data for posts for the batch processing

## Fields

| Field     | Description                                               | Type               | Null | Key | Default             | Extra |
| --------- | --------------------------------------------------------- | ------------------ | ---- | --- | ------------------- | ----- |
| uri-id    | Id of the item-uri table entry that contains the item uri | int unsigned       | NO   | PRI | NULL                |       |
| inbox-id  | Item-uri id of inbox url                                  | int unsigned       | NO   | PRI | NULL                |       |
| uid       | Delivering user                                           | mediumint unsigned | YES  |     | NULL                |       |
| created   |                                                           | datetime           | YES  |     | 0001-01-01 00:00:00 |       |
| command   |                                                           | varbinary(32)      | YES  |     | NULL                |       |
| failed    | Number of times the delivery has failed                   | tinyint            | YES  |     | 0                   |       |
| receivers | JSON encoded array with the receiving contacts            | mediumtext         | YES  |     | NULL                |       |

## Indexes

| Name             | Fields            |
| ---------------- | ----------------- |
| PRIMARY          | uri-id, inbox-id  |
| inbox-id_created | inbox-id, created |
| uid              | uid               |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |
| inbox-id | [item-uri](help/spec/database/db-item-uri) | id |
| uid | [user](help/spec/database/db-user) | uid |

Return to [database documentation](help/spec/database/index)
