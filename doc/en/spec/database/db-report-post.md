# Table report-post

Individual posts attached to a moderation report

## Fields

| Field  | Description                 | Type             | Null | Key | Default | Extra |
| ------ | --------------------------- | ---------------- | ---- | --- | ------- | ----- |
| rid    | Report id                   | int unsigned     | NO   | PRI | NULL    |       |
| uri-id | Uri-id of the reported post | int unsigned     | NO   | PRI | NULL    |       |
| status | Status of the reported post | tinyint unsigned | YES  |     | NULL    |       |

## Indexes

| Name    | Fields      |
| ------- | ----------- |
| PRIMARY | rid, uri-id |
| uri-id  | uri-id      |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| rid | [report](help/spec/database/db-report) | id |
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |

Return to [database documentation](help/spec/database/index)
