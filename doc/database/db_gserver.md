Table gserver
=============

| Field           | Description      | Type             | Null | Key | Default             | Extra          |
|-----------------|------------------|------------------|------|-----|---------------------|----------------|
| id              | sequential ID    | int(10) unsigned | NO   | PRI | NULL                | auto_increment |
| url             |                  | varchar(255)     | NO   |     |                     |                |
| nurl            |                  | varchar(255)     | NO   | MUL |                     |                |
| version         |                  | varchar(255)     | NO   |     |                     |                |
| site_name       |                  | varchar(255)     | NO   |     |                     |                |
| info            |                  | text             | NO   |     | NULL                |                |
| register_policy |                  | tinyint(1)       | NO   |     | 0                   |                |
| poco            |                  | varchar(255)     | NO   |     |                     |                |
| noscrape        |                  | varchar(255)     | NO   |     |                     |                |
| network         |                  | varchar(32)      | NO   |     |                     |                |
| platform        |                  | varchar(255)     | NO   |     |                     |                |
| created         |                  | datetime         | NO   |     | 0001-01-01 00:00:00 |                |
| last_poco_query |                  | datetime         | YES  |     | 0001-01-01 00:00:00 |                |
| last_contact    |                  | datetime         | YES  |     | 0001-01-01 00:00:00 |                |
| last_failure    |                  | datetime         | YES  |     | 0001-01-01 00:00:00 |                |


Return to [database documentation](help/database)
