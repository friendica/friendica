Table thread
============

| Field       | Description      | Type             | Null | Key | Default             | Extra |
|-------------|------------------|------------------|------|-----|---------------------|-------|
| iid         | sequential ID    | int(10) unsigned | NO   | PRI | 0                   |       |
| uid         |                  | int(10) unsigned | NO   | MUL | 0                   |       |
| contact-id  |                  | int(11) unsigned | NO   |     | 0                   |       |
| gcontact-id | Global Contact   | int(11) unsigned | NO   |     | 0                   |       |
| owner-id    | Item owner       | int(11) unsigned | NO   | MUL | 0                   |       |
| author-id   | Item author      | int(11) unsigned | NO   | MUL | 0                   |       |
| created     |                  | datetime         | NO   | MUL | 0001-01-01 00:00:00 |       |
| edited      |                  | datetime         | NO   |     | 0001-01-01 00:00:00 |       |
| commented   |                  | datetime         | NO   | MUL | 0001-01-01 00:00:00 |       |
| received    |                  | datetime         | NO   |     | 0001-01-01 00:00:00 |       |
| changed     |                  | datetime         | NO   |     | 0001-01-01 00:00:00 |       |
| wall        |                  | tinyint(1)       | NO   | MUL | 0                   |       |
| private     |                  | tinyint(1)       | NO   |     | 0                   |       |
| pubmail     |                  | tinyint(1)       | NO   |     | 0                   |       |
| moderated   |                  | tinyint(1)       | NO   |     | 0                   |       |
| visible     |                  | tinyint(1)       | NO   |     | 0                   |       |
| spam        |                  | tinyint(1)       | NO   |     | 0                   |       |
| starred     |                  | tinyint(1)       | NO   |     | 0                   |       |
| ignored     |                  | tinyint(1)       | NO   |     | 0                   |       |
| bookmark    |                  | tinyint(1)       | NO   |     | 0                   |       |
| unseen      |                  | tinyint(1)       | NO   |     | 1                   |       |
| deleted     |                  | tinyint(1)       | NO   |     | 0                   |       |
| origin      |                  | tinyint(1)       | NO   |     | 0                   |       |
| forum_mode  |                  | tinyint(1)       | NO   |     | 0                   |       |
| mention     |                  | tinyint(1)       | NO   |     | 0                   |       |
| network     |                  | varchar(32)      | NO   |     |                     |       |

Return to [database documentation](help/database)
