# Table intro



## Fields

| Field       | Description       | Type               | Null | Key | Default             | Extra          |
| ----------- | ----------------- | ------------------ | ---- | --- | ------------------- | -------------- |
| id          | sequential ID     | int unsigned       | NO   | PRI | NULL                | auto_increment |
| uid         | User id           | mediumint unsigned | NO   |     | 0                   |                |
| fid         | deprecated        | int unsigned       | YES  |     | NULL                |                |
| contact-id  |                   | int unsigned       | NO   |     | 0                   |                |
| suggest-cid | Suggested contact | int unsigned       | YES  |     | NULL                |                |
| knowyou     |                   | boolean            | NO   |     | 0                   |                |
| duplex      | deprecated        | boolean            | NO   |     | 0                   |                |
| note        |                   | text               | YES  |     | NULL                |                |
| hash        |                   | varbinary(255)     | NO   |     |                     |                |
| datetime    |                   | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| blocked     | deprecated        | boolean            | NO   |     | 0                   |                |
| ignore      |                   | boolean            | NO   |     | 0                   |                |

## Indexes

| Name        | Fields      |
| ----------- | ----------- |
| PRIMARY     | id          |
| contact-id  | contact-id  |
| suggest-cid | suggest-cid |
| uid         | uid         |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/spec/database/db-user) | uid |
| contact-id | [contact](help/spec/database/db-contact) | id |
| suggest-cid | [contact](help/spec/database/db-contact) | id |

Return to [database documentation](help/spec/database/index)
