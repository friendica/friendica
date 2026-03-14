# Table channel

User defined Channels

## Fields

| Field            | Description                                                                                       | Type               | Null | Key | Default | Extra          |
| ---------------- | ------------------------------------------------------------------------------------------------- | ------------------ | ---- | --- | ------- | -------------- |
| id               |                                                                                                   | int unsigned       | NO   | PRI | NULL    | auto_increment |
| uid              | User id                                                                                           | mediumint unsigned | NO   |     | NULL    |                |
| label            | Channel label                                                                                     | varchar(64)        | NO   |     | NULL    |                |
| description      | Channel description                                                                               | varchar(64)        | YES  |     | NULL    |                |
| circle           | Circle or channel that this channel is based on                                                   | int                | YES  |     | NULL    |                |
| access-key       | Access key                                                                                        | varchar(1)         | YES  |     | NULL    |                |
| include-tags     | Comma separated list of tags that will be included in the channel                                 | varchar(1023)      | YES  |     | NULL    |                |
| exclude-tags     | Comma separated list of tags that aren't allowed in the channel                                   | varchar(1023)      | YES  |     | NULL    |                |
| min-size         | Minimum post size                                                                                 | int unsigned       | YES  |     | NULL    |                |
| max-size         | Maximum post size                                                                                 | int unsigned       | YES  |     | NULL    |                |
| full-text-search | Full text search pattern, see https://mariadb.com/kb/en/full-text-index-overview/#in-boolean-mode | varchar(1023)      | YES  |     | NULL    |                |
| media-type       | Filtered media types                                                                              | smallint unsigned  | YES  |     | NULL    |                |
| languages        | Desired languages                                                                                 | mediumtext         | YES  |     | NULL    |                |
| publish          | publish channel content                                                                           | boolean            | YES  |     | NULL    |                |
| valid            | Set, when the full-text-search is valid                                                           | boolean            | YES  |     | NULL    |                |

## Indexes

| Name    | Fields |
| ------- | ------ |
| PRIMARY | id     |
| uid     | uid    |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/spec/database/db-user) | uid |

Return to [database documentation](help/spec/database/index)
