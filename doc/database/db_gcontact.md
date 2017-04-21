Table gcontact
==============

| Field        |Description                         | Type             | Null | Key | Default             | Extra          |
|--------------|------------------------------------|------------------|------|-----|---------------------|----------------|
| id           | sequential ID                      | int(10) unsigned | NO   | PRI | NULL                | auto_increment |
| name         | Name that this contact is known by | varchar(255)     | NO   |     |                     |                |
| nick         | Nick- and user name of the contact | varchar(255)     | NO   |     |                     |                |
| url          | Link to the contacts profile page  | varchar(255)     | NO   |     |                     |                |
| nurl         |                                    | varchar(255)     | NO   | MUL |                     |                |
| photo        | Link to the profile photo          | varchar(255)     | NO   |     |                     |                |
| connect      |                                    | varchar(255)     | NO   |     |                     |                |
| created      |                                    | datetime         | NO   |     | 0001-01-01 00:00:00 |                |
| updated      |                                    | datetime         | YES  | MUL | 0001-01-01 00:00:00 |                |
| last_contact |                                    | datetime         | YES  |     | 0001-01-01 00:00:00 |                |
| last_failure |                                    | datetime         | YES  |     | 0001-01-01 00:00:00 |                |
| location     |                                    | varchar(255)     | NO   |     |                     |                |
| about        |                                    | text             | NO   |     | NULL                |                |
| keywords     | puplic keywords (interests)        | text             | NO   |     | NULL                |                |
| gender       |                                    | varchar(32)      | NO   |     |                     |                |
| birthday     |                                    | varchar(32)      | NO   |     | 0001-01-01          |                |
| community    | 1 if contact is forum account      | tinyint(1)       | NO   |     | 0                   |                |
| hide         | 1 = should be hidden from search   | tinyint(1)       | NO   |     | 0                   |                |
| nsfw         | 1 = contact posts nsfw content     | tinyint(1)       | NO   |     | 0                   |                |
| network      | social network protocol            | varchar(255)     | NO   |     |                     |                |
| addr         |                                    | varchar(255)     | NO   |     |                     |                |
| notify       |                                    | text             | NO   |     |                     |                |
| alias        |                                    | varchar(255)     | NO   |     |                     |                |
| generation   |                                    | tinyint(3)       | NO   |     | 0                   |                |
| server_url   | baseurl of the contacts server     | varchar(255)     | NO   |     |                     |                |

Return to [database documentation](help/database)
