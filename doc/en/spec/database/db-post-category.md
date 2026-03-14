# Table post-category

post relation to categories

## Fields

| Field  | Description                                               | Type               | Null | Key | Default | Extra |
| ------ | --------------------------------------------------------- | ------------------ | ---- | --- | ------- | ----- |
| uri-id | Id of the item-uri table entry that contains the item uri | int unsigned       | NO   | PRI | NULL    |       |
| uid    | User id                                                   | mediumint unsigned | NO   | PRI | 0       |       |
| type   |                                                           | tinyint unsigned   | NO   | PRI | 0       |       |
| tid    |                                                           | int unsigned       | NO   | PRI | 0       |       |

## Indexes

| Name       | Fields                 |
| ---------- | ---------------------- |
| PRIMARY    | uri-id, uid, type, tid |
| tid        | tid                    |
| uid_uri-id | uid, uri-id            |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |
| uid | [user](help/spec/database/db-user) | uid |
| tid | [tag](help/spec/database/db-tag) | id |

Return to [database documentation](help/spec/database/index)
