# Table post-link

Post related external links

## Fields

| Field    | Description                                               | Type              | Null | Key | Default | Extra          |
| -------- | --------------------------------------------------------- | ----------------- | ---- | --- | ------- | -------------- |
| id       | sequential ID                                             | int unsigned      | NO   | PRI | NULL    | auto_increment |
| uri-id   | Id of the item-uri table entry that contains the item uri | int unsigned      | NO   |     | NULL    |                |
| url      | External URL                                              | varbinary(511)    | NO   |     | NULL    |                |
| mimetype |                                                           | varchar(60)       | YES  |     | NULL    |                |
| height   | Height of the media                                       | smallint unsigned | YES  |     | NULL    |                |
| width    | Width of the media                                        | smallint unsigned | YES  |     | NULL    |                |
| blurhash | BlurHash representation of the link                       | varbinary(255)    | YES  |     | NULL    |                |

## Indexes

| Name       | Fields              |
| ---------- | ------------------- |
| PRIMARY    | id                  |
| uri-id-url | UNIQUE, uri-id, url |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |

Return to [database documentation](help/spec/database/index)
