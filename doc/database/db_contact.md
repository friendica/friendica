Table contact
=============

| Field                     | Description                                               | Type         | Null | Key | Default             | Extra          |
|---------------------------|-----------------------------------------------------------|--------------|------|-----|---------------------|----------------|
| id                        | sequential ID                                             | int(11)      | NO   | PRI | NULL                | auto_increment |
| uid                       | user.id of the owner of this data                         | int(11)      | NO   | MUL | 0                   |                |
| created                   |                                                           | datetime     | NO   |     | 0001-01-01 00:00:00 |                |
| self                      | 1 if the contact is the user him/her self                 | tinyint(1)   | NO   |     | 0                   |                |
| remote_self               |                                                           | tinyint(1)   | NO   |     | 0                   |                |
| rel                       | The kind of the relation between the user and the contact | tinyint(1)   | NO   |     | 0                   |                |
| duplex                    |                                                           | tinyint(1)   | NO   |     | 0                   |                |
| network                   | Network protocol of the contact                           | varchar(255) | NO   |     |                     |                |
| name                      | Name that this contact is known by                        | varchar(255) | NO   |     |                     |                |
| nick                      | Nick- and user name of the contact                        | varchar(255) | NO   |     |                     |                |
| location                  |                                                           | varchar(255) | NO   |     |                     |                |
| about                     |                                                           | text         | NO   |     | NULL                |                |
| keywords                  | public keywords (interests) of the contact                | text         | NO   |     | NULL                |                |
| gender                    |                                                           | varchar(32)  | NO   |     |                     |                |
| attag                     |                                                           | varchar(255) | NO   |     |                     |                |
| photo                     | Link to the profile photo of the contact                  | text         | NO   |     | NULL                |                |
| thumb                     | Link to the profile photo (thumb size)                    | text         | NO   |     | NULL                |                |
| micro                     | Link to the profile photo (micro size)                    | text         | NO   |     | NULL                |                |
| site-pubkey               |                                                           | text         | NO   |     | NULL                |                |
| issued-id                 |                                                           | varchar(255) | NO   |     |                     |                |
| dfrn-id                   |                                                           | varchar(255) | NO   |     |                     |                |
| url                       |                                                           | varchar(255) | NO   |     |                     |                |
| nurl                      |                                                           | varchar(255) | NO   |     |                     |                |
| addr                      |                                                           | varchar(255) | NO   |     |                     |                |
| alias                     |                                                           | varchar(255) | NO   |     |                     |                |
| pubkey                    | RSA public key 4096 bit                                   | text         | NO   |     | NULL                |                |
| prvkey                    | RSA private key 4096 bit                                  | text         | NO   |     | NULL                |                |
| batch                     |                                                           | varchar(255) | NO   |     |                     |                |
| request                   |                                                           | text         | NO   |     | NULL                |                |
| notify                    |                                                           | text         | NO   |     | NULL                |                |
| poll                      |                                                           | text         | NO   |     | NULL                |                |
| confirm                   |                                                           | text         | NO   |     | NULL                |                |
| poco                      |                                                           | text         | NO   |     | NULL                |                |
| aes_allow                 |                                                           | tinyint(1)   | NO   |     | 0                   |                |
| ret-aes                   |                                                           | tinyint(1)   | NO   |     | 0                   |                |
| usehub                    |                                                           | tinyint(1)   | NO   |     | 0                   |                |
| subhub                    |                                                           | tinyint(1)   | NO   |     | 0                   |                |
| hub-verify                |                                                           | varchar(255) | NO   |     |                     |                |
| last-update               | Date of the last try to update the contact info           | datetime     | NO   |     | 0001-01-01 00:00:00 |                |
| success_update            | Date of the last successful contact update                | datetime     | NO   |     | 0001-01-01 00:00:00 |                |
| failure_update            | Date of the last failed update                            | datetime     | NO   |     | 0001-01-01 00:00:00 |                |
| name-date                 |                                                           | datetime     | NO   |     | 0001-01-01 00:00:00 |                |
| uri-date                  |                                                           | datetime     | NO   |     | 0001-01-01 00:00:00 |                |
| avatar-date               |                                                           | datetime     | NO   |     | 0001-01-01 00:00:00 |                |
| term-date                 |                                                           | datetime     | NO   |     | 0001-01-01 00:00:00 |                |
| last-item                 | date of the last post                                     | datetime     | NO   |     | 0001-01-01 00:00:00 |                |
| priority                  |                                                           | tinyint(3)   | NO   |     | 0                   |                |
| blocked                   |                                                           | tinyint(1)   | NO   |     | 1                   |                |
| readonly                  | posts of the contact are readonly                         | tinyint(1)   | NO   |     | 0                   |                |
| writable                  |                                                           | tinyint(1)   | NO   |     | 0                   |                |
| forum                     | contact is a forum                                        | tinyint(1)   | NO   |     | 0                   |                |
| prv                       | contact is a private group                                | tinyint(1)   | NO   |     | 0                   |                |
| hidden                    |                                                           | tinyint(1)   | NO   |     | 0                   |                |
| archive                   |                                                           | tinyint(1)   | NO   |     | 0                   |                |
| pending                   |                                                           | tinyint(1)   | NO   |     | 1                   |                |
| rating                    |                                                           | tinyint(1)   | NO   |     | 0                   |                |
| reason                    |                                                           | text         | NO   |     | NULL                |                |
| closeness                 |                                                           | tinyint(2)   | NO   |     | 99                  |                |
| info                      |                                                           | mediumtext   | NO   |     | NULL                |                |
| profile-id                |                                                           | int(11)      | NO   |     | 0                   |                |
| bdyear                    |                                                           | varchar(4)   | NO   |     |                     |                |
| bd                        |                                                           | date         | NO   |     | 0001-01-01          |                |
| notify_new_posts          |                                                           | tinyint(1)   | NO   |     | 0                   |                |
| fetch_further_information |                                                           | tinyint(1)   | NO   |     | 0                   |                |
| ffi_keyword_blacklist     |                                                           | mediumtext   | NO   |     | NULL                |                |

Return to [database documentation](help/database)
