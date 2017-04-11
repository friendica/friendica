Table user
==========

| Field                    | Description                                                                             | Type                | Null | Key | Default             | Extra          |
|--------------------------|-----------------------------------------------------------------------------------------|---------------------|------|-----|---------------------|----------------|
| uid                      | sequential ID                                                                           | int(11)             | NO   | PRI | NULL                | auto_increment |
| guid                     | A unique identifier for this user                                                       | varchar(64)         | NO   |     |                     |                |
| username                 | Name that this user is known by                                                         | varchar(255)        | NO   |     |                     |                |
| password                 | encrypted password                                                                      | varchar(255)        | NO   |     |                     |                |
| nickname                 | nick- and user name                                                                     | varchar(255)        | NO   | MUL |                     |                |
| email                    | the users email address                                                                 | varchar(255)        | NO   |     |                     |                |
| openid                   |                                                                                         | varchar(255)        | NO   |     |                     |                |
| timezone                 | PHP-legal timezone                                                                      | varchar(128)        | NO   |     |                     |                |
| language                 | default language                                                                        | varchar(32)         | NO   |     | en                  |                |
| register_date            | timestamp of registration                                                               | datetime            | NO   |     | 0001-01-01 00:00:00 |                |
| login_date               | timestamp of last login                                                                 | datetime            | NO   |     | 0001-01-01 00:00:00 |                |
| default-location         | Default for item.location                                                               | varchar(255)        | NO   |     |                     |                |
| allow_location           | 1 allows to display the location                                                        | tinyint(1)          | NO   |     | 0                   |                |
| theme                    | user theme preference                                                                   | varchar(255)        | NO   |     |                     |                |
| pubkey                   | RSA public key 4096 bit                                                                 | text                | NO   |     | NULL                |                |
| prvkey                   | RSA private key 4096 bit                                                                | text                | NO   |     | NULL                |                |
| spubkey                  |                                                                                         | text                | NO   |     | NULL                |                |
| sprvkey                  |                                                                                         | text                | NO   |     | NULL                |                |
| verified                 | user is verified through email                                                          | tinyint(1) unsigned | NO   |     | 0                   |                |
| blocked                  | 1 for user is blocked                                                                   | tinyint(1) unsigned | NO   |     | 0                   |                |
| blockwall                | Prohibit contacts to post to the profile page of the user                               | tinyint(1) unsigned | NO   |     | 0                   |                |
| hidewall                 | Hide profile details from unkown viewers                                                | tinyint(1) unsigned | NO   |     | 0                   |                |
| blocktags                | Prohibit contacts to tag the post of this user                                          | tinyint(1) unsigned | NO   |     | 0                   |                |
| unkmail                  | Permit unknown people to send private mails to this user                                | tinyint(1)          | NO   |     | 0                   |                |
| cntunkmail               |                                                                                         | int(11)             | NO   |     | 10                  |                |
| notify-flags             | email notification options                                                              | int(11) unsigned    | NO   |     | 65535               |                |
| page-flags               | page/profile type                                                                       | int(11) unsigned    | NO   |     | 0                   |                |
| prvnets                  |                                                                                         | tinyint(1)          | NO   |     | 0                   |                |
| pwdreset                 |                                                                                         | varchar(255)        | NO   |     |                     |                |
| maxreq                   |                                                                                         | int(11)             | NO   |     | 10                  |                |
| expire                   |                                                                                         | int(11) unsigned    | NO   |     | 0                   |                |
| account_removed          | if 1 the account is removed                                                             | tinyint(1)          | NO   |     | 0                   |                |
| account_expired          |                                                                                         | tinyint(1)          | NO   |     | 0                   |                |
| account_expires_on       | timestamp when account expires and will be deleted                                      | datetime            | NO   |     | 0001-01-01 00:00:00 |                |
| expire_notification_sent | timestamp of last warning of account expiration                                         | datetime            | NO   |     | 0001-01-01 00:00:00 |                |
| service_class            | service class for this account, determines what if any limits/restrictions are in place | varchar(32)         | NO   |     |                     |                |
| def_gid                  |                                                                                         | int(11)             | NO   |     | 0                   |                |
| allow_cid                | default permission for this user                                                        | mediumtext          | NO   |     | NULL                |                |
| allow_gid                | default permission for this user                                                        | mediumtext          | NO   |     | NULL                |                |
| deny_cid                 | default permission for this user                                                        | mediumtext          | NO   |     | NULL                |                |
| deny_gid                 | default permission for this user                                                        | mediumtext          | NO   |     | NULL                |                |
| openidserver             |                                                                                         | text                | NO   |     | NULL                |                |

```
/**
* page-flags
*/
define ( 'PAGE_NORMAL',            0 );
define ( 'PAGE_SOAPBOX',           1 );
define ( 'PAGE_COMMUNITY',         2 );
define ( 'PAGE_FREELOVE',          3 );
define ( 'PAGE_BLOG',              4 );
define ( 'PAGE_PRVGROUP',          5 );

/**
* notify-flags
*/
define ( 'NOTIFY_INTRO',    0x0001 );
define ( 'NOTIFY_CONFIRM',  0x0002 );
define ( 'NOTIFY_WALL',     0x0004 );
define ( 'NOTIFY_COMMENT',  0x0008 );
define ( 'NOTIFY_MAIL',     0x0010 );
define ( 'NOTIFY_SUGGEST',  0x0020 );
define ( 'NOTIFY_PROFILE',  0x0040 );
define ( 'NOTIFY_TAGSELF',  0x0080 );
define ( 'NOTIFY_TAGSHARE', 0x0100 );
define ( 'NOTIFY_POKE',     0x0200 );
define ( 'NOTIFY_SHARE',    0x0400 );

define ( 'NOTIFY_SYSTEM',   0x8000 );
```

Return to [database documentation](help/database)
