# Table post-searchindex

Content for all posts

## Fields

| Field      | Description                                                           | Type         | Null | Key | Default | Extra |
| ---------- | --------------------------------------------------------------------- | ------------ | ---- | --- | ------- | ----- |
| uri-id     | Id of the item-uri table entry that contains the item uri             | int unsigned | NO   | PRI | NULL    |       |
| owner-id   | Item owner                                                            | int unsigned | NO   |     | 0       |       |
| media-type | Type of media in a bit array (1 = image, 2 = video, 4 = audio)        | tinyint      | NO   |     | 0       |       |
| language   | Language information about this post in the ISO 639-1 format          | char(2)      | YES  |     | NULL    |       |
| searchtext | Simplified text for the full text search                              | mediumtext   | YES  |     | NULL    |       |
| size       | Body size                                                             | int unsigned | YES  |     | NULL    |       |
| created    |                                                                       | datetime     | YES  |     | NULL    |       |
| restricted | If true, this post is either unlisted or not from a federated network | boolean      | NO   |     | 0       |       |

## Indexes

| Name       | Fields               |
| ---------- | -------------------- |
| PRIMARY    | uri-id               |
| owner-id   | owner-id             |
| created    | created              |
| searchtext | FULLTEXT, searchtext |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |
| owner-id | [contact](help/spec/database/db-contact) | id |

Return to [database documentation](help/spec/database/index)
