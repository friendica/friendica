Table intro
===========

| Field      | Description      | Type             | Null | Key | Default             | Extra          |
|------------|------------------|------------------|------|-----|---------------------|----------------|
| id         | sequential ID    | int(10) unsigned | NO   | PRI | NULL                | auto_increment |
| uid        |                  | int(10) unsigned | NO   |     | 0                   |                |
| fid        |                  | int(11)          | NO   |     | 0                   |                |
| contact-id |                  | int(11)          | NO   |     | 0                   |                |
| knowyou    |                  | tinyint(1)       | NO   |     | 0                   |                |
| duplex     |                  | tinyint(1)       | NO   |     | 0                   |                |
| note       |                  | text             | NO   |     | NULL                |                |
| hash       |                  | varchar(255)     | NO   |     |                     |                |
| datetime   |                  | datetime         | NO   |     | 0001-01-01 00:00:00 |                |
| blocked    |                  | tinyint(1)       | NO   |     | 1                   |                |
| ignore     |                  | tinyint(1)       | NO   |     | 0                   |                |

Return to [database documentation](help/database)
