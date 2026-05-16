# Table hook

addon hook registry

## Fields

| Field    | Description                                                                                                | Type              | Null | Key | Default | Extra          |
| -------- | ---------------------------------------------------------------------------------------------------------- | ----------------- | ---- | --- | ------- | -------------- |
| id       | sequential ID                                                                                              | int unsigned      | NO   | PRI | NULL    | auto_increment |
| hook     | name of hook                                                                                               | varbinary(100)    | NO   |     |         |                |
| file     | relative filename of hook handler                                                                          | varbinary(200)    | NO   |     |         |                |
| function | function name of hook handler                                                                              | varbinary(200)    | NO   |     |         |                |
| priority | not yet implemented - can be used to sort conflicts in hook handling by calling handlers in priority order | smallint unsigned | NO   |     | 0       |                |

## Indexes

| Name               | Fields                       |
| ------------------ | ---------------------------- |
| PRIMARY            | id                           |
| priority           | priority                     |
| hook_file_function | UNIQUE, hook, file, function |


Return to [database documentation](help/spec/database/index)
