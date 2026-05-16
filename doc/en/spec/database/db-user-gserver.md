# Table user-gserver

User settings about remote servers

## Fields

| Field   | Description                              | Type               | Null | Key | Default | Extra |
| ------- | ---------------------------------------- | ------------------ | ---- | --- | ------- | ----- |
| uid     | Owner User id                            | mediumint unsigned | NO   | PRI | 0       |       |
| gsid    | Gserver id                               | int unsigned       | NO   | PRI | 0       |       |
| ignored | server accounts are ignored for the user | boolean            | NO   |     | 0       |       |

## Indexes

| Name    | Fields    |
| ------- | --------- |
| PRIMARY | uid, gsid |
| gsid    | gsid      |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/spec/database/db-user) | uid |
| gsid | [gserver](help/spec/database/db-gserver) | id |

Return to [database documentation](help/spec/database/index)
