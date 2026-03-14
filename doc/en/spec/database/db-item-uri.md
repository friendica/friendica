# Table item-uri

URI and GUID for items

## Fields

| Field | Description                     | Type           | Null | Key | Default | Extra          |
| ----- | ------------------------------- | -------------- | ---- | --- | ------- | -------------- |
| id    |                                 | int unsigned   | NO   | PRI | NULL    | auto_increment |
| uri   | URI of an item                  | varbinary(383) | NO   |     | NULL    |                |
| guid  | A unique identifier for an item | varbinary(255) | YES  |     | NULL    |                |

## Indexes

| Name    | Fields      |
| ------- | ----------- |
| PRIMARY | id          |
| uri     | UNIQUE, uri |
| guid    | guid        |


Return to [database documentation](help/spec/database/index)
