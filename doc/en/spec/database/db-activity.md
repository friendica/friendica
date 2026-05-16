# Table activity



## Fields

| Field               | Description                                | Type               | Null | Key | Default | Extra |
| ------------------- | ------------------------------------------ | ------------------ | ---- | --- | ------- | ----- |
| uid                 | User ID                                    | mediumint unsigned | NO   | PRI | NULL    |       |
| network             | Network from where the activity comes from | char(4)            | NO   | PRI | NULL    |       |
| cid                 | the user's public contact                  | int unsigned       | NO   |     | 0       |       |
| expires             | datetime of activity statistics expiration | datetime           | YES  |     | NULL    |       |
| languages           | JSON with the selected user languages      | varbinary(383)     | YES  |     | NULL    |       |
| median-comments     |                                            | int unsigned       | YES  |     | NULL    |       |
| median-activities   |                                            | int unsigned       | YES  |     | NULL    |       |
| median-views        |                                            | int unsigned       | YES  |     | NULL    |       |
| median-thread-score |                                            | int unsigned       | YES  |     | NULL    |       |
| median-post-score   |                                            | int unsigned       | YES  |     | NULL    |       |

## Indexes

| Name    | Fields       |
| ------- | ------------ |
| PRIMARY | uid, network |
| cid     | cid          |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/spec/database/db-user) | uid |
| cid | [contact](help/spec/database/db-contact) | id |

Return to [database documentation](help/spec/database/index)
