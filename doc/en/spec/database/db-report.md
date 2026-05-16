# Table report



## Fields

| Field           | Description                                                  | Type               | Null | Key | Default                    | Extra          |
| --------------- | ------------------------------------------------------------ | ------------------ | ---- | --- | -------------------------- | -------------- |
| id              | sequential ID                                                | int unsigned       | NO   | PRI | NULL                       | auto_increment |
| uid             | Reporting user                                               | mediumint unsigned | YES  |     | NULL                       |                |
| reporter-id     | Reporting contact                                            | int unsigned       | YES  |     | NULL                       |                |
| cid             | Reported contact                                             | int unsigned       | NO   |     | NULL                       |                |
| gsid            | Reported contact server                                      | int unsigned       | YES  |     | NULL                       |                |
| comment         | Report                                                       | text               | YES  |     | NULL                       |                |
| category-id     | Report category, one of Entity Report::CATEGORY_*            | int unsigned       | NO   |     | 1                          |                |
| forward         | Forward the report to the remote server                      | boolean            | YES  |     | NULL                       |                |
| public-remarks  | Remarks shared with the reporter                             | text               | YES  |     | NULL                       |                |
| private-remarks | Remarks shared with the moderation team                      | text               | YES  |     | NULL                       |                |
| last-editor-uid | Last editor user                                             | mediumint unsigned | YES  |     | NULL                       |                |
| assigned-uid    | Assigned moderator user                                      | mediumint unsigned | YES  |     | NULL                       |                |
| status          | Status of the report, one of Entity Report::STATUS_*         | tinyint unsigned   | NO   |     | NULL                       |                |
| resolution      | Resolution of the report, one of Entity Report::RESOLUTION_* | tinyint unsigned   | YES  |     | NULL                       |                |
| created         |                                                              | datetime(6)        | NO   |     | 0001-01-01 00:00:00.000000 |                |
| edited          | Last time the report has been edited                         | datetime(6)        | YES  |     | NULL                       |                |

## Indexes

| Name              | Fields             |
| ----------------- | ------------------ |
| PRIMARY           | id                 |
| uid               | uid                |
| cid               | cid                |
| reporter-id       | reporter-id        |
| gsid              | gsid               |
| last-editor-uid   | last-editor-uid    |
| assigned-uid      | assigned-uid       |
| status-resolution | status, resolution |
| created           | created            |
| edited            | edited             |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/spec/database/db-user) | uid |
| reporter-id | [contact](help/spec/database/db-contact) | id |
| cid | [contact](help/spec/database/db-contact) | id |
| gsid | [gserver](help/spec/database/db-gserver) | id |
| last-editor-uid | [user](help/spec/database/db-user) | uid |
| assigned-uid | [user](help/spec/database/db-user) | uid |

Return to [database documentation](help/spec/database/index)
