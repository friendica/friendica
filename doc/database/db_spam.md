Table spam
==========

| Field | Description | Type         | Null | Key | Default             | Extra           |
| ----- | ----------- | ------------ | ---- | --- | ------------------- | --------------- |
| id    |             | int(11)      | NO   | PRI | NULL                | auto_increment  |
| uid   |             | int(11)      | NO   | MUL | 0                   |                 |
| spam  |             | int(11)      | NO   | MUL | 0                   |                 |
| ham   |             | int(11)      | NO   | MUL | 0                   |                 |
| term  |             | varchar(255) | NO   | MUL |                     |                 |
| date  |             | datetime     | NO   |     | 0001-01-01 00:00:00 |                 |

Return to [database documentation](help/database)
