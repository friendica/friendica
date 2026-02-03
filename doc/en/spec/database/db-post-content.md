# Table post-content

Content for all posts

## Fields

| Field           | Description                                                                                                               | Type           | Null | Key | Default | Extra |
| --------------- | ------------------------------------------------------------------------------------------------------------------------- | -------------- | ---- | --- | ------- | ----- |
| uri-id          | Id of the item-uri table entry that contains the item uri                                                                 | int unsigned   | NO   | PRI | NULL    |       |
| title           | item title                                                                                                                | varchar(255)   | NO   |     |         |       |
| content-warning |                                                                                                                           | varchar(500)   | NO   |     |         |       |
| body            | item body content                                                                                                         | mediumtext     | YES  |     | NULL    |       |
| raw-body        | Body without embedded media links                                                                                         | mediumtext     | YES  |     | NULL    |       |
| quote-uri-id    | Id of the item-uri table that contains the quoted uri                                                                     | int unsigned   | YES  |     | NULL    |       |
| location        | text location where this item originated                                                                                  | varchar(255)   | NO   |     |         |       |
| coord           | longitude/latitude pair representing location where this item originated                                                  | varchar(255)   | NO   |     |         |       |
| language        | Language information about this post                                                                                      | text           | YES  |     | NULL    |       |
| sensitive       | If true, this post contains sensitive content                                                                             | boolean        | YES  |     | NULL    |       |
| app             | application which generated this item                                                                                     | varchar(255)   | NO   |     |         |       |
| rendered-hash   |                                                                                                                           | varchar(32)    | NO   |     |         |       |
| rendered-html   | item.body converted to html                                                                                               | mediumtext     | YES  |     | NULL    |       |
| object-type     | ActivityStreams object type                                                                                               | varchar(100)   | NO   |     |         |       |
| object          | JSON encoded object structure unless it is an implied object (normal post)                                                | text           | YES  |     | NULL    |       |
| target-type     | ActivityStreams target type if applicable (URI)                                                                           | varchar(100)   | NO   |     |         |       |
| target          | JSON encoded target structure if used                                                                                     | text           | YES  |     | NULL    |       |
| resource-id     | Used to link other tables to items, it identifies the linked resource (e.g. photo) and if set must also set resource_type | varchar(32)    | NO   |     |         |       |
| plink           | permalink or URL to a displayable copy of the message at its source                                                       | varbinary(383) | NO   |     |         |       |

## Indexes

| Name         | Fields       |
| ------------ | ------------ |
| PRIMARY      | uri-id       |
| plink        | plink(191)   |
| resource-id  | resource-id  |
| quote-uri-id | quote-uri-id |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |
| quote-uri-id | [item-uri](help/spec/database/db-item-uri) | id |

Return to [database documentation](help/spec/database/index)
