Table parsed_url
================

| Field        | Description                        | Type         | Null | Key | Default             | Extra |
| ------------ | ---------------------------------- | ------------ | ---- | --- | ------------------- | ----- |
| url          | page url                           | varchar(255) | NO   | PRI | NULL                |       |
| guessing     | is the "guessing" mode active?     | tinyint(1)   | NO   | PRI | 0                   |       |
| oembed       | is the data the result of oembed?  | tinyint(1)   | NO   | PRI | 0                   |       |
| content      | page data                          | text         | NO   |     | NULL                |       |
| created      | datetime of creation               | datetime     | NO   | MUL | 0001-01-01 00:00:00 |       |

Return to [database documentation](help/database)
