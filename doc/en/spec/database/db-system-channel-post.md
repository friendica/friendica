# Table system-channel-post

Posts in a system channel

## Fields

| Field       | Description                                       | Type               | Null | Key | Default             | Extra |
| ----------- | ------------------------------------------------- | ------------------ | ---- | --- | ------------------- | ----- |
| channel     | System channel id                                 | varchar(20)        | NO   | PRI | NULL                |       |
| uid         | User id                                           | mediumint unsigned | NO   | PRI | NULL                |       |
| uri-id      | Post engagement entry                             | int unsigned       | NO   | PRI | NULL                |       |
| in-timeline | If true, this post is in the user's main timeline | boolean            | NO   |     | 0                   |       |
| created     |                                                   | datetime           | NO   |     | 0001-01-01 00:00:00 |       |
| received    |                                                   | datetime           | NO   |     | 0001-01-01 00:00:00 |       |
| commented   |                                                   | datetime           | NO   |     | 0001-01-01 00:00:00 |       |

## Indexes

| Name                | Fields                |
| ------------------- | --------------------- |
| PRIMARY             | channel, uid, uri-id  |
| uri-id              | uri-id                |
| uid                 | uid                   |
| channel_uid_created | channel, uid, created |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/spec/database/db-user) | uid |
| uri-id | [post-engagement](help/spec/database/db-post-engagement) | uri-id |

Return to [database documentation](help/spec/database/index)
