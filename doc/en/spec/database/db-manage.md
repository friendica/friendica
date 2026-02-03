# Table manage

table of accounts that can manage each other

## Fields

| Field | Description   | Type               | Null | Key | Default | Extra          |
| ----- | ------------- | ------------------ | ---- | --- | ------- | -------------- |
| id    | sequential ID | int unsigned       | NO   | PRI | NULL    | auto_increment |
| uid   | User id       | mediumint unsigned | NO   |     | 0       |                |
| mid   | User id       | mediumint unsigned | NO   |     | 0       |                |

## Indexes

| Name    | Fields           |
| ------- | ---------------- |
| PRIMARY | id               |
| uid_mid | UNIQUE, uid, mid |
| mid     | mid              |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/spec/database/db-user) | uid |
| mid | [user](help/spec/database/db-user) | uid |

Return to [database documentation](help/spec/database/index)
