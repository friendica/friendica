# Table fsuggest

friend suggestion stuff

## Fields

| Field   | Description | Type               | Null | Key | Default             | Extra          |
| ------- | ----------- | ------------------ | ---- | --- | ------------------- | -------------- |
| id      |             | int unsigned       | NO   | PRI | NULL                | auto_increment |
| uid     | User id     | mediumint unsigned | NO   |     | 0                   |                |
| cid     |             | int unsigned       | NO   |     | 0                   |                |
| name    |             | varchar(255)       | NO   |     |                     |                |
| url     |             | varbinary(383)     | NO   |     |                     |                |
| request |             | varbinary(383)     | NO   |     |                     |                |
| photo   |             | varbinary(383)     | NO   |     |                     |                |
| note    |             | text               | YES  |     | NULL                |                |
| created |             | datetime           | NO   |     | 0001-01-01 00:00:00 |                |

## Indexes

| Name    | Fields |
| ------- | ------ |
| PRIMARY | id     |
| cid     | cid    |
| uid     | uid    |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/spec/database/db-user) | uid |
| cid | [contact](help/spec/database/db-contact) | id |

Return to [database documentation](help/spec/database/index)
