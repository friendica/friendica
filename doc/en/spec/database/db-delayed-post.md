# Table delayed-post

Posts that are about to be distributed at a later time

## Fields

| Field   | Description                                    | Type               | Null | Key | Default | Extra          |
| ------- | ---------------------------------------------- | ------------------ | ---- | --- | ------- | -------------- |
| id      |                                                | int unsigned       | NO   | PRI | NULL    | auto_increment |
| uri     | URI of the post that will be distributed later | varbinary(383)     | YES  |     | NULL    |                |
| uid     | Owner User id                                  | mediumint unsigned | YES  |     | NULL    |                |
| delayed | delay time                                     | datetime           | YES  |     | NULL    |                |
| wid     | Workerqueue id                                 | int unsigned       | YES  |     | NULL    |                |

## Indexes

| Name    | Fields                |
| ------- | --------------------- |
| PRIMARY | id                    |
| uid_uri | UNIQUE, uid, uri(190) |
| wid     | wid                   |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/spec/database/db-user) | uid |
| wid | [workerqueue](help/spec/database/db-workerqueue) | id |

Return to [database documentation](help/spec/database/index)
