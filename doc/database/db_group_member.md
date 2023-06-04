Table group_member
===========

privacy circles, member info

Fields
------

| Field      | Description                                                | Type         | Null | Key | Default | Extra          |
| ---------- | ---------------------------------------------------------- | ------------ | ---- | --- | ------- | -------------- |
| id         | sequential ID                                              | int unsigned | NO   | PRI | NULL    | auto_increment |
| gid        | group.id of the associated circle                          | int unsigned | NO   |     | 0       |                |
| contact-id | contact.id of the member assigned to the associated circle | int unsigned | NO   |     | 0       |                |

Indexes
------------

| Name          | Fields                  |
| ------------- | ----------------------- |
| PRIMARY       | id                      |
| contactid     | contact-id              |
| gid_contactid | UNIQUE, gid, contact-id |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| gid | [group](help/database/db_group) | id |
| contact-id | [contact](help/database/db_contact) | id |

Return to [database documentation](help/database)
