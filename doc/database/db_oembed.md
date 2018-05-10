Table oembed
============

| Field        | Description                        | Type         | Null | Key | Default             | Extra |
| ------------ | ---------------------------------- | ------------ | ---- | --- | ------------------- | ----- |
| url          | page url                           | varchar(255) | NO   | PRI | NULL                |       |
| maxwidth     | Maximum width passed to Oembed     | int(11)      | NO   | PRI | 0                   |       |
| content      | OEmbed data of the page            | text         | NO   |     | NULL                |       |
| created      | datetime of creation               | datetime     | NO   | MUL | 0001-01-01 00:00:00 |       |

Return to [database documentation](help/database)
