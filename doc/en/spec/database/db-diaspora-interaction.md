# Table diaspora-interaction

Signed Diaspora Interaction

## Fields

| Field       | Description                                               | Type         | Null | Key | Default | Extra |
| ----------- | --------------------------------------------------------- | ------------ | ---- | --- | ------- | ----- |
| uri-id      | Id of the item-uri table entry that contains the item uri | int unsigned | NO   | PRI | NULL    |       |
| interaction | The Diaspora interaction                                  | mediumtext   | YES  |     | NULL    |       |

## Indexes

| Name    | Fields |
| ------- | ------ |
| PRIMARY | uri-id |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |

Return to [database documentation](help/spec/database/index)
