# Table application

OAuth application

## Fields

| Field         | Description     | Type           | Null | Key | Default | Extra          |
| ------------- | --------------- | -------------- | ---- | --- | ------- | -------------- |
| id            | generated index | int unsigned   | NO   | PRI | NULL    | auto_increment |
| client_id     |                 | varchar(64)    | NO   |     | NULL    |                |
| client_secret |                 | varchar(64)    | NO   |     | NULL    |                |
| name          |                 | varchar(255)   | NO   |     | NULL    |                |
| redirect_uri  |                 | varbinary(383) | NO   |     | NULL    |                |
| website       |                 | varbinary(383) | YES  |     | NULL    |                |
| scopes        |                 | varchar(255)   | YES  |     | NULL    |                |
| read          | Read scope      | boolean        | YES  |     | NULL    |                |
| write         | Write scope     | boolean        | YES  |     | NULL    |                |
| follow        | Follow scope    | boolean        | YES  |     | NULL    |                |
| push          | Push scope      | boolean        | YES  |     | NULL    |                |

## Indexes

| Name      | Fields            |
| --------- | ----------------- |
| PRIMARY   | id                |
| client_id | UNIQUE, client_id |


Return to [database documentation](help/spec/database/index)
