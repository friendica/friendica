# Table apcontact

ActivityPub compatible contacts - used in the ActivityPub implementation

## Fields

| Field              | Description                                                         | Type           | Null | Key | Default             | Extra |
| ------------------ | ------------------------------------------------------------------- | -------------- | ---- | --- | ------------------- | ----- |
| url                | URL of the contact                                                  | varbinary(383) | NO   | PRI | NULL                |       |
| uri-id             | Id of the item-uri table entry that contains the apcontact url      | int unsigned   | YES  |     | NULL                |       |
| uuid               |                                                                     | varbinary(255) | YES  |     | NULL                |       |
| type               |                                                                     | varchar(20)    | NO   |     | NULL                |       |
| following          |                                                                     | varbinary(383) | YES  |     | NULL                |       |
| followers          |                                                                     | varbinary(383) | YES  |     | NULL                |       |
| inbox              |                                                                     | varbinary(383) | NO   |     | NULL                |       |
| outbox             |                                                                     | varbinary(383) | YES  |     | NULL                |       |
| sharedinbox        |                                                                     | varbinary(383) | YES  |     | NULL                |       |
| featured           | Address for the collection of featured posts                        | varbinary(383) | YES  |     | NULL                |       |
| featured-tags      | Address for the collection of featured tags                         | varbinary(383) | YES  |     | NULL                |       |
| manually-approve   |                                                                     | boolean        | YES  |     | NULL                |       |
| discoverable       | Mastodon extension: true if profile is published in their directory | boolean        | YES  |     | NULL                |       |
| suspended          | Mastodon extension: true if profile is suspended                    | boolean        | YES  |     | NULL                |       |
| posting-restricted | lemmy:postingRestrictedToMods                                       | boolean        | YES  |     | NULL                |       |
| nick               |                                                                     | varchar(255)   | NO   |     |                     |       |
| name               |                                                                     | varchar(255)   | YES  |     | NULL                |       |
| about              |                                                                     | text           | YES  |     | NULL                |       |
| xmpp               | XMPP address                                                        | varchar(255)   | YES  |     | NULL                |       |
| matrix             | Matrix address                                                      | varchar(255)   | YES  |     | NULL                |       |
| photo              |                                                                     | varbinary(383) | YES  |     | NULL                |       |
| header             | Header picture                                                      | varbinary(383) | YES  |     | NULL                |       |
| addr               |                                                                     | varchar(255)   | YES  |     | NULL                |       |
| alias              |                                                                     | varbinary(383) | YES  |     | NULL                |       |
| pubkey             |                                                                     | text           | YES  |     | NULL                |       |
| subscribe          |                                                                     | varbinary(383) | YES  |     | NULL                |       |
| baseurl            | baseurl of the ap contact                                           | varbinary(383) | YES  |     | NULL                |       |
| gsid               | Global Server ID                                                    | int unsigned   | YES  |     | NULL                |       |
| generator          | Name of the contact's system                                        | varchar(255)   | YES  |     | NULL                |       |
| following_count    | Number of following contacts                                        | int unsigned   | YES  |     | 0                   |       |
| followers_count    | Number of followers                                                 | int unsigned   | YES  |     | 0                   |       |
| statuses_count     | Number of posts                                                     | int unsigned   | YES  |     | 0                   |       |
| updated            |                                                                     | datetime       | NO   |     | 0001-01-01 00:00:00 |       |

## Indexes

| Name        | Fields           |
| ----------- | ---------------- |
| PRIMARY     | url              |
| addr        | addr(32)         |
| alias       | alias(190)       |
| followers   | followers(190)   |
| baseurl     | baseurl(190)     |
| sharedinbox | sharedinbox(190) |
| gsid        | gsid             |
| uri-id      | UNIQUE, uri-id   |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |
| gsid | [gserver](help/spec/database/db-gserver) | id |

Return to [database documentation](help/spec/database/index)
