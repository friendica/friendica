# Table mail

private messages

## Fields

| Field         | Description                                                    | Type               | Null | Key | Default             | Extra          |
| ------------- | -------------------------------------------------------------- | ------------------ | ---- | --- | ------------------- | -------------- |
| id            | sequential ID                                                  | int unsigned       | NO   | PRI | NULL                | auto_increment |
| uid           | Owner User id                                                  | mediumint unsigned | NO   |     | 0                   |                |
| guid          | A unique identifier for this private message                   | varbinary(255)     | NO   |     |                     |                |
| from-name     | name of the sender                                             | varchar(255)       | NO   |     |                     |                |
| from-photo    | contact photo link of the sender                               | varbinary(383)     | NO   |     |                     |                |
| from-url      | profile link of the sender                                     | varbinary(383)     | NO   |     |                     |                |
| contact-id    | contact.id                                                     | varbinary(255)     | YES  |     | NULL                |                |
| author-id     | Link to the contact table with uid=0 of the author of the mail | int unsigned       | YES  |     | NULL                |                |
| convid        | conv.id                                                        | int unsigned       | YES  |     | NULL                |                |
| title         |                                                                | varchar(255)       | NO   |     |                     |                |
| body          |                                                                | mediumtext         | YES  |     | NULL                |                |
| seen          | if message visited it is 1                                     | boolean            | NO   |     | 0                   |                |
| reply         |                                                                | boolean            | NO   |     | 0                   |                |
| replied       |                                                                | boolean            | NO   |     | 0                   |                |
| unknown       | if sender not in the contact table this is 1                   | boolean            | NO   |     | 0                   |                |
| uri           |                                                                | varbinary(383)     | NO   |     |                     |                |
| uri-id        | Item-uri id of the related mail                                | int unsigned       | YES  |     | NULL                |                |
| parent-uri    |                                                                | varbinary(383)     | NO   |     |                     |                |
| parent-uri-id | Item-uri id of the parent of the related mail                  | int unsigned       | YES  |     | NULL                |                |
| thr-parent    |                                                                | varbinary(383)     | YES  |     | NULL                |                |
| thr-parent-id | Id of the item-uri table that contains the thread parent uri   | int unsigned       | YES  |     | NULL                |                |
| created       | creation time of the private message                           | datetime           | NO   |     | 0001-01-01 00:00:00 |                |

## Indexes

| Name          | Fields         |
| ------------- | -------------- |
| PRIMARY       | id             |
| uid_seen      | uid, seen      |
| convid        | convid         |
| uri           | uri(64)        |
| parent-uri    | parent-uri(64) |
| contactid     | contact-id(32) |
| author-id     | author-id      |
| uri-id        | uri-id         |
| parent-uri-id | parent-uri-id  |
| thr-parent-id | thr-parent-id  |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/spec/database/db-user) | uid |
| author-id | [contact](help/spec/database/db-contact) | id |
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |
| parent-uri-id | [item-uri](help/spec/database/db-item-uri) | id |
| thr-parent-id | [item-uri](help/spec/database/db-item-uri) | id |

Return to [database documentation](help/spec/database/index)
