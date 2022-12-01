Table report-post
===========



Fields
------

| Field  | Description                 | Type             | Null | Key | Default | Extra |
| ------ | --------------------------- | ---------------- | ---- | --- | ------- | ----- |
| rid    | Report id                   | int unsigned     | NO   | PRI | NULL    |       |
| uri-id | Uri-id of the reported post | int unsigned     | NO   | PRI | NULL    |       |
| status | Status of the reported post | tinyint unsigned | YES  |     | NULL    |       |

Indexes
------------

| Name    | Fields      |
| ------- | ----------- |
| PRIMARY | rid, uri-id |
| uri-id  | uri-id      |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| rid | [report](help/database/db_report) | id |
| uri-id | [item-uri](help/database/db_item-uri) | id |

Return to [database documentation](help/database)
