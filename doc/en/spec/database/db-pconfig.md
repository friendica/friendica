# Table pconfig

personal (per user) configuration storage

## Fields

| Field | Description | Type               | Null | Key | Default | Extra          |
| ----- | ----------- | ------------------ | ---- | --- | ------- | -------------- |
| id    | Primary key | int unsigned       | NO   | PRI | NULL    | auto_increment |
| uid   | User id     | mediumint unsigned | NO   |     | 0       |                |
| cat   | Category    | varchar(50)        | NO   |     |         |                |
| k     | Key         | varchar(100)       | NO   |     |         |                |
| v     | Value       | mediumtext         | YES  |     | NULL    |                |

## Indexes

| Name      | Fields              |
| --------- | ------------------- |
| PRIMARY   | id                  |
| uid_cat_k | UNIQUE, uid, cat, k |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/spec/database/db-user) | uid |

Return to [database documentation](help/spec/database/index)
