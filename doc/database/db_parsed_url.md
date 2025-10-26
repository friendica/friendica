Table parsed_url
===========

cache for 'parse_url' queries

Fields
------

| Field    | Description                                         | Type       | Null | Key | Default             | Extra |
| -------- | --------------------------------------------------- | ---------- | ---- | --- | ------------------- | ----- |
| url_hash | page url hash                                       | binary(64) | NO   | PRI | NULL                |       |
| guessing | is the 'guessing' mode active?                      | boolean    | NO   | PRI | 0                   |       |
| oembed   | is the data the result of oembed? - Obsolete field. | boolean    | NO   | PRI | 0                   |       |
| url      | page url                                            | text       | NO   |     | NULL                |       |
| content  | page data                                           | mediumtext | YES  |     | NULL                |       |
| created  | datetime of creation                                | datetime   | NO   |     | 0001-01-01 00:00:00 |       |
| expires  | datetime of expiration                              | datetime   | NO   |     | 0001-01-01 00:00:00 |       |

Indexes
------------

| Name    | Fields                     |
| ------- | -------------------------- |
| PRIMARY | url_hash, guessing, oembed |
| created | created                    |
| expires | expires                    |


Return to [database documentation](help/database)
