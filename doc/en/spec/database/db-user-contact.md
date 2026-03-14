# Table user-contact

User specific public contact data

## Fields

| Field                     | Description                                                                | Type               | Null | Key | Default | Extra |
| ------------------------- | -------------------------------------------------------------------------- | ------------------ | ---- | --- | ------- | ----- |
| cid                       | Contact id of the linked public contact                                    | int unsigned       | NO   | PRI | 0       |       |
| uid                       | User id                                                                    | mediumint unsigned | NO   | PRI | 0       |       |
| uri-id                    | Id of the item-uri table entry that contains the contact url               | int unsigned       | YES  |     | NULL    |       |
| blocked                   | Contact is completely blocked for this user                                | boolean            | YES  |     | NULL    |       |
| ignored                   | Posts from this contact are ignored                                        | boolean            | YES  |     | NULL    |       |
| collapsed                 | Posts from this contact are collapsed                                      | boolean            | YES  |     | NULL    |       |
| hidden                    | This contact is hidden from the others                                     | boolean            | YES  |     | NULL    |       |
| channel-only              | This contact is displayed only in channels, but not in the network stream. | boolean            | YES  |     | NULL    |       |
| is-blocked                | User is blocked by this contact                                            | boolean            | YES  |     | NULL    |       |
| channel-frequency         | Controls the frequency of the appearance of this contact in channels       | tinyint unsigned   | YES  |     | NULL    |       |
| pending                   |                                                                            | boolean            | YES  |     | NULL    |       |
| rel                       | The kind of the relation between the user and the contact                  | tinyint unsigned   | YES  |     | NULL    |       |
| info                      |                                                                            | mediumtext         | YES  |     | NULL    |       |
| notify_new_posts          |                                                                            | boolean            | YES  |     | NULL    |       |
| remote_self               | 0 => No mirroring, 1-2 => Mirror as own post, 3 => Mirror as reshare       | tinyint unsigned   | YES  |     | NULL    |       |
| fetch_further_information | 0 => None, 1 => Fetch information, 3 => Fetch keywords, 2 => Fetch both    | tinyint unsigned   | YES  |     | NULL    |       |
| ffi_keyword_denylist      |                                                                            | text               | YES  |     | NULL    |       |
| hub-verify                |                                                                            | varbinary(383)     | YES  |     | NULL    |       |
| protocol                  | Protocol of the contact                                                    | char(4)            | YES  |     | NULL    |       |
| rating                    | Automatically detected feed poll frequency                                 | tinyint            | YES  |     | NULL    |       |
| priority                  | Feed poll priority                                                         | tinyint unsigned   | YES  |     | NULL    |       |

## Indexes

| Name       | Fields              |
| ---------- | ------------------- |
| PRIMARY    | uid, cid            |
| cid        | cid                 |
| uri-id_uid | UNIQUE, uri-id, uid |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| cid | [contact](help/spec/database/db-contact) | id |
| uid | [user](help/spec/database/db-user) | uid |
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |

Return to [database documentation](help/spec/database/index)
