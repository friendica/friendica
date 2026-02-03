# Table arrived-activity

Id of arrived activities

## Fields

| Field     | Description                        | Type           | Null | Key | Default | Extra |
| --------- | ---------------------------------- | -------------- | ---- | --- | ------- | ----- |
| object-id | object id of the incoming activity | varbinary(383) | NO   | PRI | NULL    |       |
| received  | Receiving date                     | datetime       | YES  |     | NULL    |       |

## Indexes

| Name    | Fields    |
| ------- | --------- |
| PRIMARY | object-id |


Return to [database documentation](help/spec/database/index)
