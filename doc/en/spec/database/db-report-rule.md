# Table report-rule

Terms of service rule lines relevant to a moderation report

## Fields

| Field   | Description                                                               | Type         | Null | Key | Default | Extra |
| ------- | ------------------------------------------------------------------------- | ------------ | ---- | --- | ------- | ----- |
| rid     | Report id                                                                 | int unsigned | NO   | PRI | NULL    |       |
| line-id | Terms of service rule line number, may become invalid after a TOS change. | int unsigned | NO   | PRI | NULL    |       |
| text    | Terms of service rule text recorded at the time of the report             | text         | NO   |     | NULL    |       |

## Indexes

| Name    | Fields       |
| ------- | ------------ |
| PRIMARY | rid, line-id |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| rid | [report](help/spec/database/db-report) | id |

Return to [database documentation](help/spec/database/index)
