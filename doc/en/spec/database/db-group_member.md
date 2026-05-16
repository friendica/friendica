# Table group_member

privacy circles, member info

## Fields

| Field      | Description                                                | Type         | Null | Key | Default | Extra          |
| ---------- | ---------------------------------------------------------- | ------------ | ---- | --- | ------- | -------------- |
| id         | sequential ID                                              | int unsigned | NO   | PRI | NULL    | auto_increment |
| gid        | group.id of the associated circle                          | int unsigned | NO   |     | 0       |                |
| contact-id | contact.id of the member assigned to the associated circle | int unsigned | NO   |     | 0       |                |

## Indexes

| Name          | Fields                  |
| ------------- | ----------------------- |
| PRIMARY       | id                      |
| contactid     | contact-id              |
| gid_contactid | UNIQUE, gid, contact-id |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| gid | [group](help/spec/database/db-group) | id |
| contact-id | [contact](help/spec/database/db-contact) | id |

Return to [database documentation](help/spec/database/index)
