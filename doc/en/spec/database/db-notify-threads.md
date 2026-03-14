# Table notify-threads



## Fields

| Field                | Description                                   | Type               | Null | Key | Default | Extra          |
| -------------------- | --------------------------------------------- | ------------------ | ---- | --- | ------- | -------------- |
| id                   | sequential ID                                 | int unsigned       | NO   | PRI | NULL    | auto_increment |
| notify-id            |                                               | int unsigned       | NO   |     | 0       |                |
| master-parent-item   | Deprecated                                    | int unsigned       | YES  |     | NULL    |                |
| master-parent-uri-id | Item-uri id of the parent of the related post | int unsigned       | YES  |     | NULL    |                |
| parent-item          |                                               | int unsigned       | NO   |     | 0       |                |
| receiver-uid         | User id                                       | mediumint unsigned | NO   |     | 0       |                |

## Indexes

| Name                 | Fields               |
| -------------------- | -------------------- |
| PRIMARY              | id                   |
| master-parent-uri-id | master-parent-uri-id |
| receiver-uid         | receiver-uid         |
| notify-id            | notify-id            |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| notify-id | [notify](help/spec/database/db-notify) | id |
| master-parent-uri-id | [item-uri](help/spec/database/db-item-uri) | id |
| receiver-uid | [user](help/spec/database/db-user) | uid |

Return to [database documentation](help/spec/database/index)
