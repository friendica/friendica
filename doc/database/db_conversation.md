Table conversation
==================

| Field             | Description   | Type                | Null | Key | Default             | Extra          |
|-------------------| ------------- |---------------------|------|-----|---------------------|----------------|
| item-uri          |               | varbinary(255)      | NO   | PRI | NULL                |                |
| reply-to-uri      |               | varbinary(255)      | NO   |     |                     |                |
| conversation-uri  |               | varbinary(255)      | NO   |     |                     |                |
| conversation-href |               | varbinary(255)      | NO   |     |                     |                |
| protocol          |               | tinyint(1) unsigned | NO   |     | 0                   |                |
| source            |               | mediumtext          | NO   |     |                     |                |
| received          |               | datetime            | NO   |     | 0001-01-01          |                |

Return to [database documentation](help/database)
