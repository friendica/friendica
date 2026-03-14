# Table session

web session storage

## Fields

| Field  | Description   | Type            | Null | Key | Default | Extra          |
| ------ | ------------- | --------------- | ---- | --- | ------- | -------------- |
| id     | sequential ID | bigint unsigned | NO   | PRI | NULL    | auto_increment |
| sid    |               | varbinary(255)  | NO   |     |         |                |
| data   |               | text            | YES  |     | NULL    |                |
| expire |               | int unsigned    | NO   |     | 0       |                |

## Indexes

| Name    | Fields  |
| ------- | ------- |
| PRIMARY | id      |
| sid     | sid(64) |
| expire  | expire  |


Return to [database documentation](help/spec/database/index)
