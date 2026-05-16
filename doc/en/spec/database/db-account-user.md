# Table account-user

Remote and local accounts

## Fields

| Field  | Description                                                  | Type               | Null | Key | Default | Extra          |
| ------ | ------------------------------------------------------------ | ------------------ | ---- | --- | ------- | -------------- |
| id     | sequential ID                                                | int unsigned       | NO   | PRI | NULL    | auto_increment |
| uri-id | Id of the item-uri table entry that contains the account url | int unsigned       | NO   |     | NULL    |                |
| uid    | User ID                                                      | mediumint unsigned | NO   |     | NULL    |                |

## Indexes

| Name       | Fields              |
| ---------- | ------------------- |
| PRIMARY    | id                  |
| uri-id_uid | UNIQUE, uri-id, uid |
| uid_uri-id | uid, uri-id         |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |
| uid | [user](help/spec/database/db-user) | uid |

Return to [database documentation](help/spec/database/index)
