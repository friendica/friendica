# Table channel-post

Posts in a user defined channel

## Fields

| Field     | Description           | Type               | Null | Key | Default             | Extra |
| --------- | --------------------- | ------------------ | ---- | --- | ------------------- | ----- |
| channel   | Channel id            | int unsigned       | NO   | PRI | NULL                |       |
| uri-id    | Post engagement entry | int unsigned       | NO   | PRI | NULL                |       |
| uid       | User id               | mediumint unsigned | NO   |     | NULL                |       |
| created   |                       | datetime           | NO   |     | 0001-01-01 00:00:00 |       |
| received  |                       | datetime           | NO   |     | 0001-01-01 00:00:00 |       |
| commented |                       | datetime           | NO   |     | 0001-01-01 00:00:00 |       |

## Indexes

| Name              | Fields             |
| ----------------- | ------------------ |
| PRIMARY           | channel, uri-id    |
| uri-id            | uri-id             |
| uid               | uid                |
| channel_created   | channel, created   |
| channel_received  | channel, received  |
| channel_commented | channel, commented |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| channel | [channel](help/spec/database/db-channel) | id |
| uri-id | [post-engagement](help/spec/database/db-post-engagement) | uri-id |
| uid | [user](help/spec/database/db-user) | uid |

Return to [database documentation](help/spec/database/index)
