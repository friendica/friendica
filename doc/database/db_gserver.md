Table gserver
===========

Global servers

Fields
------

| Field                 | Description                                                    | Type             | Null | Key | Default             | Extra          |
| --------------------- | -------------------------------------------------------------- | ---------------- | ---- | --- | ------------------- | -------------- |
| id                    | sequential ID                                                  | int unsigned     | NO   | PRI | NULL                | auto_increment |
| url                   |                                                                | varbinary(383)   | NO   |     |                     |                |
| nurl                  |                                                                | varbinary(383)   | NO   |     |                     |                |
| version               | The version of this server software.                           | varchar(255)     | NO   |     |                     |                |
| site_name             |                                                                | varchar(255)     | NO   |     |                     |                |
| info                  |                                                                | text             | YES  |     | NULL                |                |
| register_policy       |                                                                | tinyint          | NO   |     | 0                   |                |
| registered-users      | Number of registered users                                     | int unsigned     | NO   |     | 0                   |                |
| active-week-users     | Number of active users in the last week                        | int unsigned     | YES  |     | NULL                |                |
| active-month-users    | Number of active users in the last month                       | int unsigned     | YES  |     | NULL                |                |
| active-halfyear-users | Number of active users in the last six month                   | int unsigned     | YES  |     | NULL                |                |
| local-posts           | Number of local posts                                          | int unsigned     | YES  |     | NULL                |                |
| local-comments        | Number of local comments                                       | int unsigned     | YES  |     | NULL                |                |
| directory-type        | Type of directory service (Poco, Mastodon)                     | tinyint          | YES  |     | 0                   |                |
| poco                  |                                                                | varbinary(383)   | NO   |     |                     |                |
| openwebauth           | Path to the OpenWebAuth endpoint                               | varbinary(383)   | YES  |     | NULL                |                |
| authredirect          | Path to the authRedirect endpoint                              | varbinary(383)   | YES  |     | NULL                |                |
| noscrape              |                                                                | varbinary(383)   | NO   |     |                     |                |
| network               |                                                                | char(4)          | NO   |     |                     |                |
| protocol              | The protocol of the server                                     | tinyint unsigned | YES  |     | NULL                |                |
| platform              | The canonical name of this server software.                    | varchar(255)     | NO   |     |                     |                |
| repository            | The url of the source code repository of this server software. | varbinary(383)   | YES  |     | NULL                |                |
| homepage              | The url of the homepage of this server software.               | varbinary(383)   | YES  |     | NULL                |                |
| relay-subscribe       | Has the server subscribed to the relay system                  | boolean          | NO   |     | 0                   |                |
| relay-scope           | The scope of messages that the server wants to get             | varchar(10)      | NO   |     |                     |                |
| detection-method      | Method that had been used to detect that server                | tinyint unsigned | YES  |     | NULL                |                |
| created               |                                                                | datetime         | NO   |     | 0001-01-01 00:00:00 |                |
| last_poco_query       |                                                                | datetime         | YES  |     | 0001-01-01 00:00:00 |                |
| last_contact          | Last successful connection request                             | datetime         | YES  |     | 0001-01-01 00:00:00 |                |
| last_failure          | Last failed connection request                                 | datetime         | YES  |     | 0001-01-01 00:00:00 |                |
| blocked               | Server is blocked                                              | boolean          | YES  |     | NULL                |                |
| failed                | Connection failed                                              | boolean          | YES  |     | NULL                |                |
| next_contact          | Next connection request                                        | datetime         | YES  |     | 0001-01-01 00:00:00 |                |
| redirect-gsid         | Target Gserver id in case of a redirect                        | int unsigned     | YES  |     | NULL                |                |

Indexes
------------

| Name          | Fields            |
| ------------- | ----------------- |
| PRIMARY       | id                |
| nurl          | UNIQUE, nurl(190) |
| next_contact  | next_contact      |
| network       | network           |
| redirect-gsid | redirect-gsid     |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| redirect-gsid | [gserver](help/database/db_gserver) | id |

Return to [database documentation](help/database)
