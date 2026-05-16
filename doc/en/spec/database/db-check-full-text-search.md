# Table check-full-text-search

Check for a full text search match in user defined channels before storing the message in the system

## Fields

| Field      | Description                              | Type         | Null | Key | Default | Extra |
| ---------- | ---------------------------------------- | ------------ | ---- | --- | ------- | ----- |
| pid        | The ID of the process                    | int unsigned | NO   | PRI | NULL    |       |
| searchtext | Simplified text for the full text search | mediumtext   | YES  |     | NULL    |       |

## Indexes

| Name       | Fields               |
| ---------- | -------------------- |
| PRIMARY    | pid                  |
| searchtext | FULLTEXT, searchtext |


Return to [database documentation](help/spec/database/index)
