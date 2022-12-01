Table account-suggestion
===========

Account suggestion

Fields
------

| Field  | Description                                                  | Type               | Null | Key | Default | Extra |
| ------ | ------------------------------------------------------------ | ------------------ | ---- | --- | ------- | ----- |
| uri-id | Id of the item-uri table entry that contains the account url | int unsigned       | NO   | PRI | NULL    |       |
| uid    | User ID                                                      | mediumint unsigned | NO   | PRI | NULL    |       |
| level  | level of closeness                                           | smallint unsigned  | YES  |     | NULL    |       |
| ignore | If set, this account will not be suggested again             | boolean            | NO   |     | 0       |       |

Indexes
------------

| Name       | Fields      |
| ---------- | ----------- |
| PRIMARY    | uid, uri-id |
| uri-id_uid | uri-id, uid |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/database/db_item-uri) | id |
| uid | [user](help/database/db_user) | uid |

Return to [database documentation](help/database)
