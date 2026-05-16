# Table profile

user profiles data

## Fields

| Field             | Description                                                    | Type               | Null | Key | Default    | Extra          |
| ----------------- | -------------------------------------------------------------- | ------------------ | ---- | --- | ---------- | -------------- |
| id                | sequential ID                                                  | int unsigned       | NO   | PRI | NULL       | auto_increment |
| uid               | Owner User id                                                  | mediumint unsigned | NO   |     | 0          |                |
| profile-name      | Deprecated                                                     | varchar(255)       | YES  |     | NULL       |                |
| is-default        | Deprecated                                                     | boolean            | YES  |     | NULL       |                |
| hide-friends      | Hide friend list from viewers of this profile                  | boolean            | NO   |     | 0          |                |
| name              | Unused in favor of user.username                               | varchar(255)       | NO   |     |            |                |
| pdesc             | Deprecated                                                     | varchar(255)       | YES  |     | NULL       |                |
| dob               | Day of birth                                                   | varchar(32)        | NO   |     | 0000-00-00 |                |
| address           |                                                                | varchar(255)       | NO   |     |            |                |
| locality          |                                                                | varchar(255)       | NO   |     |            |                |
| region            |                                                                | varchar(255)       | NO   |     |            |                |
| postal-code       |                                                                | varchar(32)        | NO   |     |            |                |
| country-name      |                                                                | varchar(255)       | NO   |     |            |                |
| hometown          | Deprecated                                                     | varchar(255)       | YES  |     | NULL       |                |
| gender            | Deprecated                                                     | varchar(32)        | YES  |     | NULL       |                |
| marital           | Deprecated                                                     | varchar(255)       | YES  |     | NULL       |                |
| with              | Deprecated                                                     | text               | YES  |     | NULL       |                |
| howlong           | Deprecated                                                     | datetime           | YES  |     | NULL       |                |
| sexual            | Deprecated                                                     | varchar(255)       | YES  |     | NULL       |                |
| politic           | Deprecated                                                     | varchar(255)       | YES  |     | NULL       |                |
| religion          | Deprecated                                                     | varchar(255)       | YES  |     | NULL       |                |
| pub_keywords      |                                                                | text               | YES  |     | NULL       |                |
| prv_keywords      |                                                                | text               | YES  |     | NULL       |                |
| likes             | Deprecated                                                     | text               | YES  |     | NULL       |                |
| dislikes          | Deprecated                                                     | text               | YES  |     | NULL       |                |
| about             | Profile description                                            | text               | YES  |     | NULL       |                |
| summary           | Deprecated                                                     | varchar(255)       | YES  |     | NULL       |                |
| music             | Deprecated                                                     | text               | YES  |     | NULL       |                |
| book              | Deprecated                                                     | text               | YES  |     | NULL       |                |
| tv                | Deprecated                                                     | text               | YES  |     | NULL       |                |
| film              | Deprecated                                                     | text               | YES  |     | NULL       |                |
| interest          | Deprecated                                                     | text               | YES  |     | NULL       |                |
| romance           | Deprecated                                                     | text               | YES  |     | NULL       |                |
| work              | Deprecated                                                     | text               | YES  |     | NULL       |                |
| education         | Deprecated                                                     | text               | YES  |     | NULL       |                |
| contact           | Deprecated                                                     | text               | YES  |     | NULL       |                |
| homepage          |                                                                | varchar(255)       | NO   |     |            |                |
| homepage_verified | was the homepage verified by a rel-me link back to the profile | boolean            | NO   |     | 0          |                |
| xmpp              | XMPP address                                                   | varchar(255)       | NO   |     |            |                |
| matrix            | Matrix address                                                 | varchar(255)       | NO   |     |            |                |
| photo             |                                                                | varbinary(383)     | NO   |     |            |                |
| thumb             |                                                                | varbinary(383)     | NO   |     |            |                |
| publish           | publish default profile in local directory                     | boolean            | NO   |     | 0          |                |
| net-publish       | publish profile in global directory                            | boolean            | NO   |     | 0          |                |

## Indexes

| Name           | Fields          |
| -------------- | --------------- |
| PRIMARY        | id              |
| uid_is-default | uid, is-default |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/spec/database/db-user) | uid |

Return to [database documentation](help/spec/database/index)
