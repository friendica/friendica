Table contact
===========

contact table

Fields
------

| Field                     | Description                                                                                                    | Type               | Null | Key | Default             | Extra          |
| ------------------------- | -------------------------------------------------------------------------------------------------------------- | ------------------ | ---- | --- | ------------------- | -------------- |
| id                        | sequential ID                                                                                                  | int unsigned       | NO   | PRI | NULL                | auto_increment |
| uid                       | Owner User id                                                                                                  | mediumint unsigned | NO   |     | 0                   |                |
| created                   |                                                                                                                | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| updated                   | Date of last contact update                                                                                    | datetime           | YES  |     | 0001-01-01 00:00:00 |                |
| network                   | Network of the contact                                                                                         | char(4)            | NO   |     |                     |                |
| name                      | Name that this contact is known by                                                                             | varchar(255)       | NO   |     |                     |                |
| nick                      | Nick- and user name of the contact                                                                             | varchar(255)       | NO   |     |                     |                |
| location                  |                                                                                                                | varchar(255)       | YES  |     |                     |                |
| about                     |                                                                                                                | text               | YES  |     | NULL                |                |
| keywords                  | public keywords (interests) of the contact                                                                     | text               | YES  |     | NULL                |                |
| xmpp                      | XMPP address                                                                                                   | varchar(255)       | NO   |     |                     |                |
| matrix                    | Matrix address                                                                                                 | varchar(255)       | NO   |     |                     |                |
| avatar                    |                                                                                                                | varbinary(383)     | NO   |     |                     |                |
| blurhash                  | BlurHash representation of the avatar                                                                          | varbinary(255)     | YES  |     | NULL                |                |
| header                    | Header picture                                                                                                 | varbinary(383)     | YES  |     | NULL                |                |
| url                       |                                                                                                                | varbinary(383)     | NO   |     |                     |                |
| nurl                      |                                                                                                                | varbinary(383)     | NO   |     |                     |                |
| uri-id                    | Id of the item-uri table entry that contains the contact url                                                   | int unsigned       | YES  |     | NULL                |                |
| addr                      |                                                                                                                | varchar(255)       | NO   |     |                     |                |
| alias                     |                                                                                                                | varbinary(383)     | NO   |     |                     |                |
| pubkey                    | RSA public key 4096 bit                                                                                        | text               | YES  |     | NULL                |                |
| prvkey                    | RSA private key 4096 bit                                                                                       | text               | YES  |     | NULL                |                |
| batch                     |                                                                                                                | varbinary(383)     | NO   |     |                     |                |
| notify                    |                                                                                                                | varbinary(383)     | YES  |     | NULL                |                |
| poll                      |                                                                                                                | varbinary(383)     | YES  |     | NULL                |                |
| subscribe                 |                                                                                                                | varbinary(383)     | YES  |     | NULL                |                |
| last-update               | Date of the last try to update the contact info                                                                | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| next-update               | Next connection request                                                                                        | datetime           | YES  |     | NULL                |                |
| success_update            | Date of the last successful contact update                                                                     | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| failure_update            | Date of the last failed update                                                                                 | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| failed                    | Connection failed                                                                                              | boolean            | YES  |     | NULL                |                |
| term-date                 |                                                                                                                | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| last-item                 | date of the last post                                                                                          | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| last-discovery            | date of the last follower discovery                                                                            | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| local-data                | Is true when there are posts with this contact on the system                                                   | boolean            | YES  |     | NULL                |                |
| blocked                   | Node-wide block status                                                                                         | boolean            | NO   |     | 1                   |                |
| block_reason              | Node-wide block reason                                                                                         | text               | YES  |     | NULL                |                |
| readonly                  | posts of the contact are readonly                                                                              | boolean            | NO   |     | 0                   |                |
| contact-type              | Person, organisation, news, community, relay                                                                   | tinyint            | NO   |     | 0                   |                |
| manually-approve          | Contact requests have to be approved manually                                                                  | boolean            | YES  |     | NULL                |                |
| archive                   |                                                                                                                | boolean            | NO   |     | 0                   |                |
| unsearchable              | Contact prefers to not be searchable                                                                           | boolean            | NO   |     | 0                   |                |
| sensitive                 | Contact posts sensitive content                                                                                | boolean            | NO   |     | 0                   |                |
| baseurl                   | baseurl of the contact                                                                                         | varbinary(383)     | YES  |     |                     |                |
| gsid                      | Global Server ID                                                                                               | int unsigned       | YES  |     | NULL                |                |
| bd                        |                                                                                                                | date               | NO   |     | 0001-01-01          |                |
| reason                    |                                                                                                                | text               | YES  |     | NULL                |                |
| self                      | 1 if the contact is the user him/her self                                                                      | boolean            | NO   |     | 0                   |                |
| remote_self               |                                                                                                                | boolean            | NO   |     | 0                   |                |
| rel                       | The kind of the relation between the user and the contact                                                      | tinyint unsigned   | NO   |     | 0                   |                |
| protocol                  | Protocol of the contact                                                                                        | char(4)            | NO   |     |                     |                |
| subhub                    |                                                                                                                | boolean            | NO   |     | 0                   |                |
| hub-verify                |                                                                                                                | varbinary(383)     | NO   |     |                     |                |
| rating                    | Automatically detected feed poll frequency                                                                     | tinyint            | NO   |     | 0                   |                |
| priority                  | Feed poll priority                                                                                             | tinyint unsigned   | NO   |     | 0                   |                |
| attag                     |                                                                                                                | varchar(255)       | NO   |     |                     |                |
| hidden                    |                                                                                                                | boolean            | NO   |     | 0                   |                |
| pending                   | Contact request is pending                                                                                     | boolean            | NO   |     | 1                   |                |
| deleted                   | Contact has been deleted                                                                                       | boolean            | NO   |     | 0                   |                |
| info                      |                                                                                                                | mediumtext         | YES  |     | NULL                |                |
| notify_new_posts          |                                                                                                                | boolean            | NO   |     | 0                   |                |
| fetch_further_information |                                                                                                                | tinyint unsigned   | NO   |     | 0                   |                |
| ffi_keyword_denylist      |                                                                                                                | text               | YES  |     | NULL                |                |
| photo                     | Link to the profile photo of the contact                                                                       | varbinary(383)     | YES  |     |                     |                |
| thumb                     | Link to the profile photo (thumb size)                                                                         | varbinary(383)     | YES  |     |                     |                |
| micro                     | Link to the profile photo (micro size)                                                                         | varbinary(383)     | YES  |     |                     |                |
| name-date                 |                                                                                                                | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| uri-date                  |                                                                                                                | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| avatar-date               |                                                                                                                | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| request                   |                                                                                                                | varbinary(383)     | YES  |     | NULL                |                |
| confirm                   |                                                                                                                | varbinary(383)     | YES  |     | NULL                |                |
| poco                      |                                                                                                                | varbinary(383)     | YES  |     | NULL                |                |
| writable                  |                                                                                                                | boolean            | NO   |     | 0                   |                |
| forum                     | contact is a group. Deprecated, use 'contact-type' = 'community' and 'manually-approve' = false instead        | boolean            | NO   |     | 0                   |                |
| prv                       | contact is a private group. Deprecated, use 'contact-type' = 'community' and 'manually-approve' = true instead | boolean            | NO   |     | 0                   |                |
| bdyear                    |                                                                                                                | varchar(4)         | NO   |     |                     |                |
| site-pubkey               | Deprecated                                                                                                     | text               | YES  |     | NULL                |                |
| gender                    | Deprecated                                                                                                     | varchar(32)        | NO   |     |                     |                |
| duplex                    | Deprecated                                                                                                     | boolean            | NO   |     | 0                   |                |
| issued-id                 | Deprecated                                                                                                     | varbinary(383)     | NO   |     |                     |                |
| dfrn-id                   | Deprecated                                                                                                     | varbinary(383)     | NO   |     |                     |                |
| aes_allow                 | Deprecated                                                                                                     | boolean            | NO   |     | 0                   |                |
| ret-aes                   | Deprecated                                                                                                     | boolean            | NO   |     | 0                   |                |
| usehub                    | Deprecated                                                                                                     | boolean            | NO   |     | 0                   |                |
| closeness                 | Deprecated                                                                                                     | tinyint unsigned   | NO   |     | 99                  |                |
| profile-id                | Deprecated                                                                                                     | int unsigned       | YES  |     | NULL                |                |

Indexes
------------

| Name                        | Fields                               |
| --------------------------- | ------------------------------------ |
| PRIMARY                     | id                                   |
| uid_name                    | uid, name(190)                       |
| self_uid                    | self, uid                            |
| alias_uid                   | alias(128), uid                      |
| pending_uid                 | pending, uid                         |
| blocked_uid                 | blocked, uid                         |
| uid_rel_network_poll        | uid, rel, network, poll(64), archive |
| uid_network_batch           | uid, network, batch(64)              |
| batch_contact-type          | batch(64), contact-type              |
| addr_uid                    | addr(128), uid                       |
| nurl_uid                    | nurl(128), uid                       |
| nick_uid                    | nick(128), uid                       |
| attag_uid                   | attag(96), uid                       |
| network_uid_lastupdate      | network, uid, last-update            |
| uid_network_self_lastupdate | uid, network, self, last-update      |
| next-update                 | next-update                          |
| local-data-next-update      | local-data, next-update              |
| uid_lastitem                | uid, last-item                       |
| baseurl                     | baseurl(64)                          |
| uid_contact-type            | uid, contact-type                    |
| uid_self_contact-type       | uid, self, contact-type              |
| self_network_uid            | self, network, uid                   |
| gsid_uid_failed             | gsid, uid, failed                    |
| uri-id                      | uri-id                               |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/database/db_user) | uid |
| uri-id | [item-uri](help/database/db_item-uri) | id |
| gsid | [gserver](help/database/db_gserver) | id |

Return to [database documentation](help/database)
