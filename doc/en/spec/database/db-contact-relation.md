# Table contact-relation

Contact relations

## Fields

| Field                 | Description                                                             | Type              | Null | Key | Default             | Extra |
| --------------------- | ----------------------------------------------------------------------- | ----------------- | ---- | --- | ------------------- | ----- |
| cid                   | contact the related contact had interacted with                         | int unsigned      | NO   | PRI | 0                   |       |
| relation-cid          | related contact who had interacted with the contact                     | int unsigned      | NO   | PRI | 0                   |       |
| network               | The network that is used between these contacts                         | char(4)           | YES  |     | NULL                |       |
| last-interaction      | Date of the last interaction by relation-cid on cid                     | datetime          | NO   |     | 0001-01-01 00:00:00 |       |
| follow-updated        | Date of the last update of the contact relationship                     | datetime          | NO   |     | 0001-01-01 00:00:00 |       |
| follows               | if true, relation-cid follows cid                                       | boolean           | NO   |     | 0                   |       |
| score                 | score for interactions of cid on relation-cid                           | smallint unsigned | YES  |     | NULL                |       |
| relation-score        | score for interactions of relation-cid on cid                           | smallint unsigned | YES  |     | NULL                |       |
| thread-score          | score for interactions of cid on threads of relation-cid                | smallint unsigned | YES  |     | NULL                |       |
| relation-thread-score | score for interactions of relation-cid on threads of cid                | smallint unsigned | YES  |     | NULL                |       |
| post-score            | score for the amount of posts from cid that can be seen by relation-cid | smallint unsigned | YES  |     | NULL                |       |

## Indexes

| Name                     | Fields                     |
| ------------------------ | -------------------------- |
| PRIMARY                  | cid, relation-cid          |
| relation-cid-network     | relation-cid, network      |
| cid_follows_relation-cid | cid, follows, relation-cid |
| cid_relation-cid         | cid, relation-cid          |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| cid | [contact](help/spec/database/db-contact) | id |
| relation-cid | [contact](help/spec/database/db-contact) | id |

Return to [database documentation](help/spec/database/index)
