# Table post-question-voter

Records which user voted for which question option, to prevent double voting

## Fields

| Field   | Description                                               | Type               | Null | Key | Default             | Extra |
| ------- | --------------------------------------------------------- | ------------------ | ---- | --- | ------------------- | ----- |
| uri-id  | Id of the item-uri table entry that contains the item uri | int unsigned       | NO   | PRI | NULL                |       |
| id      | Id of the question option                                 | int unsigned       | NO   | PRI | NULL                |       |
| uid     | User ID                                                   | mediumint unsigned | NO   | PRI | NULL                |       |
| created | Creation date                                             | datetime           | NO   |     | 0001-01-01 00:00:00 |       |

## Indexes

| Name    | Fields          |
| ------- | --------------- |
| PRIMARY | uri-id, id, uid |
| uid     | uid             |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |
| uid | [user](help/spec/database/db-user) | uid |

Return to [database documentation](help/spec/database/index)
