# Table post-quote

Quotes

## Fields

| Field        | Description                                               | Type         | Null | Key | Default | Extra |
| ------------ | --------------------------------------------------------- | ------------ | ---- | --- | ------- | ----- |
| uri-id       | Id of the item-uri table entry that contains the item uri | int unsigned | NO   | PRI | NULL    |       |
| quote-uri-id | Id of the item-uri table that contains the quoted uri     | int unsigned | YES  |     | NULL    |       |

## Indexes

| Name         | Fields       |
| ------------ | ------------ |
| PRIMARY      | uri-id       |
| quote-uri-id | quote-uri-id |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |
| quote-uri-id | [item-uri](help/spec/database/db-item-uri) | id |

Return to [database documentation](help/spec/database/index)
