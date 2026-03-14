# Table post-counts

Original remote activity

## Fields

| Field         | Description                                                 | Type              | Null | Key | Default | Extra |
| ------------- | ----------------------------------------------------------- | ----------------- | ---- | --- | ------- | ----- |
| uri-id        | Id of the item-uri table entry that contains the item uri   | int unsigned      | NO   | PRI | NULL    |       |
| vid           | Id of the verb table entry that contains the activity verbs | smallint unsigned | NO   | PRI | NULL    |       |
| reaction      | Emoji Reaction                                              | varchar(4)        | NO   | PRI | NULL    |       |
| parent-uri-id | Id of the item-uri table that contains the parent uri       | int unsigned      | YES  |     | NULL    |       |
| count         | Number of activities                                        | int unsigned      | YES  |     | 0       |       |

## Indexes

| Name          | Fields                |
| ------------- | --------------------- |
| PRIMARY       | uri-id, vid, reaction |
| vid           | vid                   |
| parent-uri-id | parent-uri-id         |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |
| vid | [verb](help/spec/database/db-verb) | id |
| parent-uri-id | [item-uri](help/spec/database/db-item-uri) | id |

Return to [database documentation](help/spec/database/index)
