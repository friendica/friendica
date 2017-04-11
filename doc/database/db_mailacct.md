Table mailacct
==============

| Field        | Description      | Type         | Null | Key | Default             | Extra          |
|--------------|------------------|--------------|------|-----|---------------------|----------------|
| id           | sequential ID    | int(11)      | NO   | PRI | NULL                | auto_increment |
| uid          |                  | int(11)      | NO   |     | 0                   |                |
| server       |                  | varchar(255) | NO   |     |                     |                |
| port         |                  | int(11)      | NO   |     | 0                   |                |
| ssltype      |                  | varchar(16)  | NO   |     |                     |                |
| mailbox      |                  | varchar(255) | NO   |     |                     |                |
| user         |                  | varchar(255) | NO   |     |                     |                |
| pass         |                  | text         | NO   |     | NULL                |                |
| reply_to     |                  | varchar(255) | NO   |     |                     |                |
| action       |                  | int(11)      | NO   |     | 0                   |                |
| movetofolder |                  | varchar(255) | NO   |     |                     |                |
| pubmail      |                  | tinyint(1)   | NO   |     | 0                   |                |
| last_check   |                  | datetime     | NO   |     | 0001-01-01 00:00:00 |                |

Return to [database documentation](help/database)
