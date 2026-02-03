# Table subscription

Push Subscription for the API

## Fields

| Field          | Description                    | Type               | Null | Key | Default | Extra          |
| -------------- | ------------------------------ | ------------------ | ---- | --- | ------- | -------------- |
| id             | Auto incremented image data id | int unsigned       | NO   | PRI | NULL    | auto_increment |
| application-id |                                | int unsigned       | NO   |     | NULL    |                |
| uid            | Owner User id                  | mediumint unsigned | NO   |     | NULL    |                |
| endpoint       | Endpoint URL                   | varchar(511)       | YES  |     | NULL    |                |
| pubkey         | User agent public key          | varchar(127)       | YES  |     | NULL    |                |
| secret         | Auth secret                    | varchar(32)        | YES  |     | NULL    |                |
| follow         |                                | boolean            | YES  |     | NULL    |                |
| favourite      |                                | boolean            | YES  |     | NULL    |                |
| reblog         |                                | boolean            | YES  |     | NULL    |                |
| mention        |                                | boolean            | YES  |     | NULL    |                |
| poll           |                                | boolean            | YES  |     | NULL    |                |
| follow_request |                                | boolean            | YES  |     | NULL    |                |
| status         |                                | boolean            | YES  |     | NULL    |                |

## Indexes

| Name               | Fields                      |
| ------------------ | --------------------------- |
| PRIMARY            | id                          |
| application-id_uid | UNIQUE, application-id, uid |
| uid_application-id | uid, application-id         |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| application-id | [application](help/spec/database/db-application) | id |
| uid | [user](help/spec/database/db-user) | uid |

Return to [database documentation](help/spec/database/index)
