Table cache
===========

| Field        | Description                        | Type         | Null | Key | Default             | Extra |
| ------------ | ---------------------------------- | ------------ | ---- | --- | ------------------- | ----- |
| k            | horizontal width + url or resource | varchar(255) | NO   | PRI | NULL                |       |
| v            | OEmbed response from site          | text         | NO   |     | NULL                |       |
| updated      | datetime of cache insertion        | datetime     | NO   | MUL | 0001-01-01 00:00:00 |       |
| expire_mode  |                                    | int(11)      | NO   |     | 0                   |       |

Return to [database documentation](help/database)
