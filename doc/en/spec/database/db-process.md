# Table process

Currently running system processes

## Fields

| Field    | Description                                | Type          | Null | Key | Default             | Extra |
| -------- | ------------------------------------------ | ------------- | ---- | --- | ------------------- | ----- |
| pid      | The ID of the process                      | int unsigned  | NO   | PRI | NULL                |       |
| hostname | The name of the host the process is ran on | varchar(255)  | NO   | PRI | NULL                |       |
| command  |                                            | varbinary(32) | NO   |     |                     |       |
| created  |                                            | datetime      | NO   |     | 0001-01-01 00:00:00 |       |

## Indexes

| Name    | Fields        |
| ------- | ------------- |
| PRIMARY | pid, hostname |
| command | command       |


Return to [database documentation](help/spec/database/index)
