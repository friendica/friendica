Table conversation
==================

| Field             | Description                        | Type                | Null | Key | Default             | Extra          |
|-------------------| ---------------------------------- |---------------------|------|-----|---------------------|----------------|
| item-uri          | URI of the item                    | varbinary(255)      | NO   | PRI | NULL                |                |
| reply-to-uri      | URI to which this item is a reply  | varbinary(255)      | NO   |     |                     |                |
| conversation-uri  | GNU Social conversation URI        | varbinary(255)      | NO   |     |                     |                |
| conversation-href | GNU Social conversation link       | varbinary(255)      | NO   |     |                     |                |
| protocol          | The protocol of the item           | tinyint(1) unsigned | NO   |     | 0                   |                |
| source            | Original source                    | mediumtext          | NO   |     |                     |                |
| received          | Receiving date                     | datetime            | NO   |     | 0001-01-01          |                |

Return to [database documentation](help/database)
