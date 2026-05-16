# Table inbox-entry-receiver

Receiver for the incoming activity

## Fields

| Field    | Description | Type               | Null | Key | Default | Extra |
| -------- | ----------- | ------------------ | ---- | --- | ------- | ----- |
| queue-id |             | int unsigned       | NO   | PRI | NULL    |       |
| uid      | User id     | mediumint unsigned | NO   | PRI | NULL    |       |

## Indexes

| Name    | Fields        |
| ------- | ------------- |
| PRIMARY | queue-id, uid |
| uid     | uid           |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| queue-id | [inbox-entry](help/spec/database/db-inbox-entry) | id |
| uid | [user](help/spec/database/db-user) | uid |

Return to [database documentation](help/spec/database/index)
