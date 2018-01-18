Table addon
===========

| Field         | Description                                   | Type       | Null | Key | Default | Extra           |
| ------------- | --------------------------------------------- | ---------- | ---- | --- | ------- | --------------- |
| id            |                                               | int(11)    | NO   | PRI | NULL    | auto_increment  |
| name          | addon base (file)name                        | char(255)  | NO   |     |         |                 |
| version       | currently unused                              | char(255)  | NO   |     |         |                 |
| installed     | currently always 1                            | tinyint(1) | NO   |     | 0       |                 |
| hidden        | currently unused                              | tinyint(1) | NO   |     | 0       |                 |
| timestamp     | file timestamp to check for reloads           | bigint(20) | NO   |     | 0       |                 |
| plugin_admin  | 1 = has admin config, 0 = has no admin config | tinyint(1) | NO   |     | 0       |                 |

Notes:
These are addons which have been enabled by the site administrator on the admin/addon page

Return to [database documentation](help/database)
