Table photo
===========

photo storage

Fields
------

| Field         | Description                                                         | Type               | Null | Key | Default             | Extra          |
| ------------- | ------------------------------------------------------------------- | ------------------ | ---- | --- | ------------------- | -------------- |
| id            | sequential ID                                                       | int unsigned       | NO   | PRI | NULL                | auto_increment |
| uid           | Owner User id                                                       | mediumint unsigned | NO   |     | 0                   |                |
| contact-id    | contact.id                                                          | int unsigned       | NO   |     | 0                   |                |
| guid          | A unique identifier for this photo                                  | char(16)           | NO   |     |                     |                |
| resource-id   |                                                                     | char(32)           | NO   |     |                     |                |
| hash          | hash value of the photo                                             | char(32)           | YES  |     | NULL                |                |
| created       | creation date                                                       | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| edited        | last edited date                                                    | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| title         |                                                                     | varchar(255)       | NO   |     |                     |                |
| desc          |                                                                     | text               | YES  |     | NULL                |                |
| album         | The name of the album to which the photo belongs                    | varchar(255)       | NO   |     |                     |                |
| photo-type    | User avatar, user banner, contact avatar, contact banner or default | tinyint unsigned   | YES  |     | NULL                |                |
| filename      |                                                                     | varchar(255)       | NO   |     |                     |                |
| type          |                                                                     | varchar(30)        | NO   |     | image/jpeg          |                |
| height        |                                                                     | smallint unsigned  | NO   |     | 0                   |                |
| width         |                                                                     | smallint unsigned  | NO   |     | 0                   |                |
| datasize      |                                                                     | int unsigned       | NO   |     | 0                   |                |
| blurhash      | BlurHash representation of the photo                                | varbinary(255)     | YES  |     | NULL                |                |
| data          |                                                                     | mediumblob         | NO   |     | NULL                |                |
| scale         |                                                                     | tinyint unsigned   | NO   |     | 0                   |                |
| profile       |                                                                     | boolean            | NO   |     | 0                   |                |
| allow_cid     | Access Control - list of allowed contact.id '<19><78>'              | mediumtext         | YES  |     | NULL                |                |
| allow_gid     | Access Control - list of allowed circles                            | mediumtext         | YES  |     | NULL                |                |
| deny_cid      | Access Control - list of denied contact.id                          | mediumtext         | YES  |     | NULL                |                |
| deny_gid      | Access Control - list of denied circles                             | mediumtext         | YES  |     | NULL                |                |
| accessible    | Make photo publicly accessible, ignoring permissions                | boolean            | NO   |     | 0                   |                |
| backend-class | Storage backend class                                               | tinytext           | YES  |     | NULL                |                |
| backend-ref   | Storage backend data reference                                      | text               | YES  |     | NULL                |                |
| updated       |                                                                     | datetime           | NO   |     | 0001-01-01 00:00:00 |                |

Indexes
------------

| Name                          | Fields                               |
| ----------------------------- | ------------------------------------ |
| PRIMARY                       | id                                   |
| contactid                     | contact-id                           |
| uid_contactid                 | uid, contact-id                      |
| uid_profile                   | uid, profile                         |
| uid_album_scale_created       | uid, album(32), scale, created       |
| uid_album_resource-id_created | uid, album(32), resource-id, created |
| resource-id                   | resource-id                          |
| uid_photo-type                | uid, photo-type                      |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/database/db_user) | uid |
| contact-id | [contact](help/database/db_contact) | id |

Return to [database documentation](help/database)
