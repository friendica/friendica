# Table mailacct

Mail account data for fetching mails

## Fields

| Field        | Description   | Type               | Null | Key | Default             | Extra          |
| ------------ | ------------- | ------------------ | ---- | --- | ------------------- | -------------- |
| id           | sequential ID | int unsigned       | NO   | PRI | NULL                | auto_increment |
| uid          | User id       | mediumint unsigned | NO   |     | 0                   |                |
| server       |               | varchar(255)       | NO   |     |                     |                |
| port         |               | smallint unsigned  | NO   |     | 0                   |                |
| ssltype      |               | varchar(16)        | NO   |     |                     |                |
| mailbox      |               | varchar(255)       | NO   |     |                     |                |
| user         |               | varchar(255)       | NO   |     |                     |                |
| pass         |               | text               | YES  |     | NULL                |                |
| reply_to     |               | varchar(255)       | NO   |     |                     |                |
| action       |               | tinyint unsigned   | NO   |     | 0                   |                |
| movetofolder |               | varchar(255)       | NO   |     |                     |                |
| pubmail      |               | boolean            | NO   |     | 0                   |                |
| last_check   |               | datetime           | NO   |     | 0001-01-01 00:00:00 |                |

## Indexes

| Name    | Fields |
| ------- | ------ |
| PRIMARY | id     |
| uid     | uid    |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/spec/database/db-user) | uid |

Return to [database documentation](help/spec/database/index)
