# Table 2fa_app_specific_password

Two-factor app-specific _password

## Fields

| Field           | Description                              | Type               | Null | Key | Default | Extra          |
| --------------- | ---------------------------------------- | ------------------ | ---- | --- | ------- | -------------- |
| id              | Password ID for revocation               | mediumint unsigned | NO   | PRI | NULL    | auto_increment |
| uid             | User ID                                  | mediumint unsigned | NO   |     | NULL    |                |
| description     | Description of the usage of the password | varchar(255)       | YES  |     | NULL    |                |
| hashed_password | Hashed password                          | varchar(255)       | NO   |     | NULL    |                |
| generated       | Datetime the password was generated      | datetime           | NO   |     | NULL    |                |
| last_used       | Datetime the password was last used      | datetime           | YES  |     | NULL    |                |

## Indexes

| Name            | Fields                |
| --------------- | --------------------- |
| PRIMARY         | id                    |
| uid_description | uid, description(190) |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/spec/database/db-user) | uid |

Return to [database documentation](help/spec/database/index)
