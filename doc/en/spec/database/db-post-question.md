# Table post-question

Question

## Fields

| Field    | Description                                               | Type         | Null | Key | Default             | Extra          |
| -------- | --------------------------------------------------------- | ------------ | ---- | --- | ------------------- | -------------- |
| id       | sequential ID                                             | int unsigned | NO   | PRI | NULL                | auto_increment |
| uri-id   | Id of the item-uri table entry that contains the item uri | int unsigned | NO   |     | NULL                |                |
| multiple | Multiple choice                                           | boolean      | NO   |     | 0                   |                |
| voters   | Number of voters for this question                        | int unsigned | YES  |     | NULL                |                |
| end-time | Question end time                                         | datetime     | YES  |     | 0001-01-01 00:00:00 |                |

## Indexes

| Name    | Fields         |
| ------- | -------------- |
| PRIMARY | id             |
| uri-id  | UNIQUE, uri-id |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |

Return to [database documentation](help/spec/database/index)
