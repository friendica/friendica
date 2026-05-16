# Table conv

private messages

## Fields

| Field   | Description                               | Type               | Null | Key | Default             | Extra          |
| ------- | ----------------------------------------- | ------------------ | ---- | --- | ------------------- | -------------- |
| id      | sequential ID                             | int unsigned       | NO   | PRI | NULL                | auto_increment |
| guid    | A unique identifier for this conversation | varbinary(255)     | NO   |     |                     |                |
| recips  | sender_handle;recipient_handle            | text               | YES  |     | NULL                |                |
| uid     | Owner User id                             | mediumint unsigned | NO   |     | 0                   |                |
| creator | handle of creator                         | varchar(255)       | NO   |     |                     |                |
| created | creation timestamp                        | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| updated | edited timestamp                          | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| subject | subject of initial message                | text               | YES  |     | NULL                |                |

## Indexes

| Name    | Fields |
| ------- | ------ |
| PRIMARY | id     |
| uid     | uid    |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/spec/database/db-user) | uid |

Return to [database documentation](help/spec/database/index)
