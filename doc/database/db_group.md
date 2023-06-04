Table group
===========

privacy circles, circle info

Fields
------

| Field   | Description                                                                               | Type               | Null | Key | Default | Extra          |
| ------- | ----------------------------------------------------------------------------------------- | ------------------ | ---- | --- | ------- | -------------- |
| id      | sequential ID                                                                             | int unsigned       | NO   | PRI | NULL    | auto_increment |
| uid     | Owner User id                                                                             | mediumint unsigned | NO   |     | 0       |                |
| visible | 1 indicates the member list is not private                                                | boolean            | NO   |     | 0       |                |
| deleted | 1 indicates the circle has been deleted                                                   | boolean            | NO   |     | 0       |                |
| cid     | Contact id of group. When this field is filled then the members are synced automatically. | int unsigned       | YES  |     | NULL    |                |
| name    | human readable name of circle                                                             | varchar(255)       | NO   |     |         |                |

Indexes
------------

| Name    | Fields |
| ------- | ------ |
| PRIMARY | id     |
| uid     | uid    |
| cid     | cid    |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/database/db_user) | uid |
| cid | [contact](help/database/db_contact) | id |

Return to [database documentation](help/database)
