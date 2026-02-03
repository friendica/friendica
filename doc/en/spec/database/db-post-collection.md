# Table post-collection

Collection of posts

## Fields

| Field     | Description                                               | Type             | Null | Key | Default | Extra |
| --------- | --------------------------------------------------------- | ---------------- | ---- | --- | ------- | ----- |
| uri-id    | Id of the item-uri table entry that contains the item uri | int unsigned     | NO   | PRI | NULL    |       |
| type      | 0 - Featured                                              | tinyint unsigned | NO   | PRI | 0       |       |
| author-id | Author of the featured post                               | int unsigned     | YES  |     | NULL    |       |

## Indexes

| Name      | Fields       |
| --------- | ------------ |
| PRIMARY   | uri-id, type |
| type      | type         |
| author-id | author-id    |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |
| author-id | [contact](help/spec/database/db-contact) | id |

Return to [database documentation](help/spec/database/index)
