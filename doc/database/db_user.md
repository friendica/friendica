Table user
===========

The local users

Fields
------

| Field                    | Description                                                                       | Type               | Null | Key | Default             | Extra          |
| ------------------------ | --------------------------------------------------------------------------------- | ------------------ | ---- | --- | ------------------- | -------------- |
| uid                      | sequential ID                                                                     | mediumint unsigned | NO   | PRI | NULL                | auto_increment |
| parent-uid               | The parent user that has full control about this user                             | mediumint unsigned | YES  |     | NULL                |                |
| guid                     | A unique identifier for this user                                                 | varchar(64)        | NO   |     |                     |                |
| username                 | Name that this user is known by                                                   | varchar(255)       | NO   |     |                     |                |
| password                 | encrypted password                                                                | varchar(255)       | NO   |     |                     |                |
| legacy_password          | Is the password hash double-hashed?                                               | boolean            | NO   |     | 0                   |                |
| nickname                 | nick- and user name                                                               | varchar(255)       | NO   |     |                     |                |
| email                    | the users email address                                                           | varchar(255)       | NO   |     |                     |                |
| openid                   |                                                                                   | varchar(255)       | NO   |     |                     |                |
| timezone                 | PHP-legal timezone                                                                | varchar(128)       | NO   |     |                     |                |
| language                 | default language                                                                  | varchar(32)        | NO   |     | en                  |                |
| register_date            | timestamp of registration                                                         | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| login_date               | timestamp of last login                                                           | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| last-activity            | Day of the last activity                                                          | date               | YES  |     | NULL                |                |
| default-location         | Default for item.location                                                         | varchar(255)       | NO   |     |                     |                |
| allow_location           | 1 allows to display the location                                                  | boolean            | NO   |     | 0                   |                |
| theme                    | user theme preference                                                             | varchar(255)       | NO   |     |                     |                |
| pubkey                   | RSA public key 4096 bit                                                           | text               | YES  |     | NULL                |                |
| prvkey                   | RSA private key 4096 bit                                                          | text               | YES  |     | NULL                |                |
| spubkey                  |                                                                                   | text               | YES  |     | NULL                |                |
| sprvkey                  |                                                                                   | text               | YES  |     | NULL                |                |
| verified                 | user is verified through email                                                    | boolean            | NO   |     | 0                   |                |
| blocked                  | 1 for user is blocked                                                             | boolean            | NO   |     | 0                   |                |
| blockwall                | Prohibit contacts to post to the profile page of the user                         | boolean            | NO   |     | 0                   |                |
| hidewall                 | Hide profile details from unkown viewers                                          | boolean            | NO   |     | 0                   |                |
| blocktags                | Prohibit contacts to tag the post of this user                                    | boolean            | NO   |     | 0                   |                |
| unkmail                  | Permit unknown people to send private mails to this user                          | boolean            | NO   |     | 0                   |                |
| cntunkmail               |                                                                                   | int unsigned       | NO   |     | 10                  |                |
| notify-flags             | email notification options                                                        | smallint unsigned  | NO   |     | 65535               |                |
| page-flags               | page/profile type                                                                 | tinyint unsigned   | NO   |     | 0                   |                |
| account-type             |                                                                                   | tinyint unsigned   | NO   |     | 0                   |                |
| prvnets                  |                                                                                   | boolean            | NO   |     | 0                   |                |
| pwdreset                 | Password reset request token                                                      | varchar(255)       | YES  |     | NULL                |                |
| pwdreset_time            | Timestamp of the last password reset request                                      | datetime           | YES  |     | NULL                |                |
| maxreq                   |                                                                                   | int unsigned       | NO   |     | 10                  |                |
| expire                   | Delay in days before deleting user-related posts. Scope is controlled by pConfig. | int unsigned       | NO   |     | 0                   |                |
| account_removed          | if 1 the account is removed                                                       | boolean            | NO   |     | 0                   |                |
| account_expired          |                                                                                   | boolean            | NO   |     | 0                   |                |
| account_expires_on       | timestamp when account expires and will be deleted                                | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| expire_notification_sent | timestamp of last warning of account expiration                                   | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| def_gid                  |                                                                                   | int unsigned       | NO   |     | 0                   |                |
| allow_cid                | default permission for this user                                                  | mediumtext         | YES  |     | NULL                |                |
| allow_gid                | default permission for this user                                                  | mediumtext         | YES  |     | NULL                |                |
| deny_cid                 | default permission for this user                                                  | mediumtext         | YES  |     | NULL                |                |
| deny_gid                 | default permission for this user                                                  | mediumtext         | YES  |     | NULL                |                |
| openidserver             |                                                                                   | text               | YES  |     | NULL                |                |

Indexes
------------

| Name       | Fields       |
| ---------- | ------------ |
| PRIMARY    | uid          |
| nickname   | nickname(32) |
| parent-uid | parent-uid   |
| guid       | guid         |
| email      | email(64)    |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| parent-uid | [user](help/database/db_user) | uid |

Return to [database documentation](help/database)
