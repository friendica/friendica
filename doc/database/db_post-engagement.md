Table post-engagement
===========

Engagement data per post

Fields
------

| Field        | Description                                                           | Type               | Null | Key | Default | Extra |
| ------------ | --------------------------------------------------------------------- | ------------------ | ---- | --- | ------- | ----- |
| uri-id       | Id of the item-uri table entry that contains the item uri             | int unsigned       | NO   | PRI | NULL    |       |
| owner-id     | Item owner                                                            | int unsigned       | NO   |     | 0       |       |
| contact-type | Person, organisation, news, community, relay                          | tinyint            | NO   |     | 0       |       |
| media-type   | Type of media in a bit array (1 = image, 2 = video, 4 = audio         | tinyint            | NO   |     | 0       |       |
| language     | Language information about this post in the ISO 639-1 format          | char(2)            | YES  |     | NULL    |       |
| searchtext   | Simplified text for the full text search                              | mediumtext         | YES  |     | NULL    |       |
| size         | Body size                                                             | int unsigned       | YES  |     | NULL    |       |
| created      |                                                                       | datetime           | YES  |     | NULL    |       |
| network      |                                                                       | char(4)            | YES  |     | NULL    |       |
| restricted   | If true, this post is either unlisted or not from a federated network | boolean            | NO   |     | 0       |       |
| comments     | Number of comments                                                    | mediumint unsigned | YES  |     | NULL    |       |
| activities   | Number of activities (like, dislike, ...)                             | mediumint unsigned | YES  |     | NULL    |       |

Indexes
------------

| Name       | Fields               |
| ---------- | -------------------- |
| PRIMARY    | uri-id               |
| owner-id   | owner-id             |
| created    | created              |
| searchtext | FULLTEXT, searchtext |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/database/db_item-uri) | id |
| owner-id | [contact](help/database/db_contact) | id |

Return to [database documentation](help/database)
