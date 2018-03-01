Table cache
===========

Stores temporary data

| Field        | Description                        | Type         | Null | Key | Default             | Extra |
| ------------ | ---------------------------------- | ------------ | ---- | --- | ------------------- | ----- |
| k            | cache key                          | varchar(255) | NO   | PRI | NULL                |       |
| v            | cached serialized value            | text         | NO   |     | NULL                |       |
| expires      | datetime of cache expiration       | datetime     | NO   | MUL | 0001-01-01 00:00:00 |       |
| updated      | datetime of cache insertion        | datetime     | NO   | MUL | 0001-01-01 00:00:00 |       |

Return to [database documentation](help/database)
