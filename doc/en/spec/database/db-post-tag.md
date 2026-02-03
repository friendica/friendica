# Table post-tag

post relation to tags

## Fields

| Field  | Description                                               | Type             | Null | Key | Default | Extra |
| ------ | --------------------------------------------------------- | ---------------- | ---- | --- | ------- | ----- |
| uri-id | Id of the item-uri table entry that contains the item uri | int unsigned     | NO   | PRI | NULL    |       |
| type   |                                                           | tinyint unsigned | NO   | PRI | 0       |       |
| tid    |                                                           | int unsigned     | NO   | PRI | 0       |       |
| cid    | Contact id of the mentioned public contact                | int unsigned     | NO   | PRI | 0       |       |

## Indexes

| Name    | Fields                 |
| ------- | ---------------------- |
| PRIMARY | uri-id, type, tid, cid |
| tid     | tid                    |
| cid     | cid                    |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |
| tid | [tag](help/spec/database/db-tag) | id |
| cid | [contact](help/spec/database/db-contact) | id |

Return to [database documentation](help/spec/database/index)
