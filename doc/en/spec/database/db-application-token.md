# Table application-token

OAuth user token

## Fields

| Field          | Description   | Type               | Null | Key | Default | Extra |
| -------------- | ------------- | ------------------ | ---- | --- | ------- | ----- |
| application-id |               | int unsigned       | NO   | PRI | NULL    |       |
| uid            | Owner User id | mediumint unsigned | NO   | PRI | NULL    |       |
| code           |               | varchar(64)        | NO   |     | NULL    |       |
| access_token   |               | varchar(64)        | NO   |     | NULL    |       |
| created_at     | creation time | datetime           | NO   |     | NULL    |       |
| scopes         |               | varchar(255)       | YES  |     | NULL    |       |
| read           | Read scope    | boolean            | YES  |     | NULL    |       |
| write          | Write scope   | boolean            | YES  |     | NULL    |       |
| follow         | Follow scope  | boolean            | YES  |     | NULL    |       |
| push           | Push scope    | boolean            | YES  |     | NULL    |       |

## Indexes

| Name    | Fields              |
| ------- | ------------------- |
| PRIMARY | application-id, uid |
| uid_id  | uid, application-id |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| application-id | [application](help/spec/database/db-application) | id |
| uid | [user](help/spec/database/db-user) | uid |

Return to [database documentation](help/spec/database/index)
