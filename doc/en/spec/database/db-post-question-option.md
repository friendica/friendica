# Table post-question-option

Question option

## Fields

| Field   | Description                                               | Type         | Null | Key | Default | Extra |
| ------- | --------------------------------------------------------- | ------------ | ---- | --- | ------- | ----- |
| id      | Id of the question                                        | int unsigned | NO   | PRI | NULL    |       |
| uri-id  | Id of the item-uri table entry that contains the item uri | int unsigned | NO   | PRI | NULL    |       |
| name    | Name of the option                                        | varchar(255) | YES  |     | NULL    |       |
| replies | Number of replies for this question option                | int unsigned | YES  |     | NULL    |       |

## Indexes

| Name    | Fields     |
| ------- | ---------- |
| PRIMARY | uri-id, id |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |

Return to [database documentation](help/spec/database/index)
