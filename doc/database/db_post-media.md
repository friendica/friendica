Table post-media
===========

Attached media

Fields
------

| Field           | Description                                                        | Type              | Null | Key | Default | Extra          |
| --------------- | ------------------------------------------------------------------ | ----------------- | ---- | --- | ------- | -------------- |
| id              | sequential ID                                                      | int unsigned      | NO   | PRI | NULL    | auto_increment |
| uri-id          | Id of the item-uri table entry that contains the item uri          | int unsigned      | NO   |     | NULL    |                |
| url             | Media URL                                                          | varbinary(1024)   | NO   |     | NULL    |                |
| media-uri-id    | Id of the item-uri table entry that contains the activities uri-id | int unsigned      | YES  |     | NULL    |                |
| type            | Media type                                                         | tinyint unsigned  | NO   |     | 0       |                |
| mimetype        |                                                                    | varchar(60)       | YES  |     | NULL    |                |
| height          | Height of the media                                                | smallint unsigned | YES  |     | NULL    |                |
| width           | Width of the media                                                 | smallint unsigned | YES  |     | NULL    |                |
| size            | Media size                                                         | bigint unsigned   | YES  |     | NULL    |                |
| blurhash        | BlurHash representation of the image                               | varbinary(255)    | YES  |     | NULL    |                |
| preview         | Preview URL                                                        | varbinary(512)    | YES  |     | NULL    |                |
| preview-height  | Height of the preview picture                                      | smallint unsigned | YES  |     | NULL    |                |
| preview-width   | Width of the preview picture                                       | smallint unsigned | YES  |     | NULL    |                |
| description     |                                                                    | text              | YES  |     | NULL    |                |
| name            | Name of the media                                                  | varchar(255)      | YES  |     | NULL    |                |
| author-url      | URL of the author of the media                                     | varbinary(383)    | YES  |     | NULL    |                |
| author-name     | Name of the author of the media                                    | varchar(255)      | YES  |     | NULL    |                |
| author-image    | Image of the author of the media                                   | varbinary(383)    | YES  |     | NULL    |                |
| publisher-url   | URL of the publisher of the media                                  | varbinary(383)    | YES  |     | NULL    |                |
| publisher-name  | Name of the publisher of the media                                 | varchar(255)      | YES  |     | NULL    |                |
| publisher-image | Image of the publisher of the media                                | varbinary(383)    | YES  |     | NULL    |                |

Indexes
------------

| Name         | Fields                   |
| ------------ | ------------------------ |
| PRIMARY      | id                       |
| uri-id-url   | UNIQUE, uri-id, url(512) |
| uri-id-id    | uri-id, id               |
| media-uri-id | media-uri-id             |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/database/db_item-uri) | id |
| media-uri-id | [item-uri](help/database/db_item-uri) | id |

Return to [database documentation](help/database)
