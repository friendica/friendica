# Table tag

tags and mentions

## Fields

| Field | Description                                                                   | Type             | Null | Key | Default | Extra          |
| ----- | ----------------------------------------------------------------------------- | ---------------- | ---- | --- | ------- | -------------- |
| id    |                                                                               | int unsigned     | NO   | PRI | NULL    | auto_increment |
| name  |                                                                               | varchar(96)      | NO   |     |         |                |
| url   |                                                                               | varbinary(383)   | NO   |     |         |                |
| type  | Type of the tag (Unknown, General Collection, Follower Collection or Account) | tinyint unsigned | YES  |     | NULL    |                |

## Indexes

| Name          | Fields            |
| ------------- | ----------------- |
| PRIMARY       | id                |
| type_name_url | UNIQUE, name, url |
| url           | url               |


Return to [database documentation](help/spec/database/index)
