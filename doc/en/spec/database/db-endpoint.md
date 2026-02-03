# Table endpoint

ActivityPub endpoints - used in the ActivityPub implementation

## Fields

| Field        | Description                                                    | Type           | Null | Key | Default | Extra |
| ------------ | -------------------------------------------------------------- | -------------- | ---- | --- | ------- | ----- |
| url          | URL of the contact                                             | varbinary(383) | NO   | PRI | NULL    |       |
| type         |                                                                | varchar(20)    | NO   |     | NULL    |       |
| owner-uri-id | Id of the item-uri table entry that contains the apcontact url | int unsigned   | YES  |     | NULL    |       |

## Indexes

| Name              | Fields                     |
| ----------------- | -------------------------- |
| PRIMARY           | url                        |
| owner-uri-id_type | UNIQUE, owner-uri-id, type |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| owner-uri-id | [item-uri](help/spec/database/db-item-uri) | id |

Return to [database documentation](help/spec/database/index)
