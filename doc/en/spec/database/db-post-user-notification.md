# Table post-user-notification

User post notifications

## Fields

| Field             | Description                                               | Type               | Null | Key | Default | Extra |
| ----------------- | --------------------------------------------------------- | ------------------ | ---- | --- | ------- | ----- |
| uri-id            | Id of the item-uri table entry that contains the item uri | int unsigned       | NO   | PRI | NULL    |       |
| uid               | Owner id which owns this copy of the item                 | mediumint unsigned | NO   | PRI | NULL    |       |
| notification-type |                                                           | smallint unsigned  | NO   |     | 0       |       |

## Indexes

| Name    | Fields      |
| ------- | ----------- |
| PRIMARY | uid, uri-id |
| uri-id  | uri-id      |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |
| uid | [user](help/spec/database/db-user) | uid |

Return to [database documentation](help/spec/database/index)
