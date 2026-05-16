# Table fetch-entry



## Fields

| Field   | Description                        | Type           | Null | Key | Default             | Extra          |
| ------- | ---------------------------------- | -------------- | ---- | --- | ------------------- | -------------- |
| id      | sequential ID                      | int unsigned   | NO   | PRI | NULL                | auto_increment |
| url     | url that awaiting to be fetched    | varbinary(383) | YES  |     | NULL                |                |
| created | Creation date of the fetch request | datetime       | NO   |     | 0001-01-01 00:00:00 |                |
| wid     | Workerqueue id                     | int unsigned   | YES  |     | NULL                |                |

## Indexes

| Name    | Fields      |
| ------- | ----------- |
| PRIMARY | id          |
| url     | UNIQUE, url |
| created | created     |
| wid     | wid         |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| wid | [workerqueue](help/spec/database/db-workerqueue) | id |

Return to [database documentation](help/spec/database/index)
