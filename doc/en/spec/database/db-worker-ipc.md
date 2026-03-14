# Table worker-ipc

Inter process communication between the frontend and the worker

## Fields

| Field | Description               | Type    | Null | Key | Default | Extra |
| ----- | ------------------------- | ------- | ---- | --- | ------- | ----- |
| key   |                           | int     | NO   | PRI | NULL    |       |
| jobs  | Flag for outstanding jobs | boolean | YES  |     | NULL    |       |

## Indexes

| Name    | Fields |
| ------- | ------ |
| PRIMARY | key    |


Return to [database documentation](help/spec/database/index)
