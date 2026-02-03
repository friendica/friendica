# Table openwebauth-token

Store OpenWebAuth token to verify contacts

## Fields

| Field   | Description                | Type               | Null | Key | Default             | Extra          |
| ------- | -------------------------- | ------------------ | ---- | --- | ------------------- | -------------- |
| id      | sequential ID              | int unsigned       | NO   | PRI | NULL                | auto_increment |
| uid     | User id - currently unused | mediumint unsigned | NO   |     | 0                   |                |
| type    | Verify type                | varchar(32)        | NO   |     |                     |                |
| token   | A generated token          | varchar(255)       | NO   |     |                     |                |
| meta    |                            | varchar(255)       | NO   |     |                     |                |
| created | datetime of creation       | datetime           | NO   |     | 0001-01-01 00:00:00 |                |

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
