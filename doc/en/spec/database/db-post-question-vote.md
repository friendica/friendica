# Table post-question-vote

Votes on question options by local users and remote contacts

## Fields

| Field     | Description                                                   | Type         | Null | Key | Default             | Extra |
| --------- | ------------------------------------------------------------- | ------------ | ---- | --- | ------------------- | ----- |
| uri-id    | Id of the item-uri table entry that contains the question uri | int unsigned | NO   | PRI | NULL                |       |
| id        | Id of the question option                                     | int unsigned | NO   | PRI | NULL                |       |
| author-id | Link to the contact table with uid=0 of the voter             | int unsigned | NO   | PRI | NULL                |       |
| created   | Creation date                                                 | datetime     | NO   |     | 0001-01-01 00:00:00 |       |

## Indexes

| Name      | Fields                |
| --------- | --------------------- |
| PRIMARY   | uri-id, id, author-id |
| author-id | author-id             |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |
| author-id | [contact](help/spec/database/db-contact) | id |

Return to [database documentation](help/spec/database/index)
