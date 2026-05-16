# Table locks



## Fields

| Field   | Description                  | Type         | Null | Key | Default             | Extra          |
| ------- | ---------------------------- | ------------ | ---- | --- | ------------------- | -------------- |
| id      | sequential ID                | int unsigned | NO   | PRI | NULL                | auto_increment |
| name    |                              | varchar(128) | NO   |     |                     |                |
| locked  |                              | boolean      | NO   |     | 0                   |                |
| pid     | Process ID                   | int unsigned | NO   |     | 0                   |                |
| expires | datetime of cache expiration | datetime     | NO   |     | 0001-01-01 00:00:00 |                |

## Indexes

| Name         | Fields        |
| ------------ | ------------- |
| PRIMARY      | id            |
| name_expires | name, expires |


Return to [database documentation](help/spec/database/index)
