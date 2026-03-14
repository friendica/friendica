# Table 2fa_trusted_browser

Two-factor authentication trusted browsers

## Fields

| Field       | Description                                    | Type               | Null | Key | Default | Extra |
| ----------- | ---------------------------------------------- | ------------------ | ---- | --- | ------- | ----- |
| cookie_hash | Trusted cookie hash                            | varchar(80)        | NO   | PRI | NULL    |       |
| uid         | User ID                                        | mediumint unsigned | NO   |     | NULL    |       |
| user_agent  | User agent string                              | text               | YES  |     | NULL    |       |
| trusted     | Whenever this browser should be trusted or not | boolean            | NO   |     | 1       |       |
| created     | Datetime the trusted browser was recorded      | datetime           | NO   |     | NULL    |       |
| last_used   | Datetime the trusted browser was last used     | datetime           | YES  |     | NULL    |       |

## Indexes

| Name    | Fields      |
| ------- | ----------- |
| PRIMARY | cookie_hash |
| uid     | uid         |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/spec/database/db-user) | uid |

Return to [database documentation](help/spec/database/index)
