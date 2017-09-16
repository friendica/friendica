Table profile
=============

| Field        | Description                                   | Type         | Null | Key | Default             | Extra          |
|--------------|-----------------------------------------------|--------------|------|-----|---------------------|----------------|
| id           | sequential ID                                 | int(11)      | NO   | PRI | NULL                | auto_increment |
| uid          | user.id of the owner of this data             | int(11)      | NO   |     | 0                   |                |
| profile-name | Name of the profile                           | varchar(255) | NO   |     |                     |                |
| is-default   | Mark this profile as default profile          | tinyint(1)   | NO   |     | 0                   |                |
| hide-friends | Hide friend list from viewers of this profile | tinyint(1)   | NO   |     | 0                   |                |
| name         |                                               | varchar(255) | NO   |     |                     |                |
| pdesc        | Title or description                          | varchar(255) | NO   |     |                     |                |
| dob          | Day of birth                                  | varchar(32)  | NO   |     | 0001-01-01          |                |
| address      |                                               | varchar(255) | NO   |     |                     |                |
| locality     |                                               | varchar(255) | NO   |     |                     |                |
| region       |                                               | varchar(255) | NO   |     |                     |                |
| postal-code  |                                               | varchar(32)  | NO   |     |                     |                |
| country-name |                                               | varchar(255) | NO   |     |                     |                |
| hometown     |                                               | varchar(255) | NO   | MUL |                     |                |
| gender       |                                               | varchar(32)  | NO   |     |                     |                |
| marital      |                                               | varchar(255) | NO   |     |                     |                |
| with         |                                               | text         | NO   |     | NULL                |                |
| howlong      |                                               | datetime     | NO   |     | 0001-01-01 00:00:00 |                |
| sexual       |                                               | varchar(255) | NO   |     |                     |                |
| politic      |                                               | varchar(255) | NO   |     |                     |                |
| religion     |                                               | varchar(255) | NO   |     |                     |                |
| pub_keywords |                                               | text         | NO   |     | NULL                |                |
| prv_keywords |                                               | text         | NO   |     | NULL                |                |
| likes        |                                               | text         | NO   |     | NULL                |                |
| dislikes     |                                               | text         | NO   |     | NULL                |                |
| about        |                                               | text         | NO   |     | NULL                |                |
| summary      |                                               | varchar(255) | NO   |     |                     |                |
| music        |                                               | text         | NO   |     | NULL                |                |
| book         |                                               | text         | NO   |     | NULL                |                |
| tv           |                                               | text         | NO   |     | NULL                |                |
| film         |                                               | text         | NO   |     | NULL                |                |
| interest     |                                               | text         | NO   |     | NULL                |                |
| romance      |                                               | text         | NO   |     | NULL                |                |
| work         |                                               | text         | NO   |     | NULL                |                |
| education    |                                               | text         | NO   |     | NULL                |                |
| contact      |                                               | text         | NO   |     | NULL                |                |
| homepage     |                                               | varchar(255) | NO   |     |                     |                |
| photo        |                                               | varchar(255) | NO   |     |                     |                |
| thumb        |                                               | varchar(255) | NO   |     |                     |                |
| publish      | publish default profile in local directory    | tinyint(1)   | NO   |     | 0                   |                |
| net-publish  | publish profile in global directory           | tinyint(1)   | NO   |     | 0                   |                |

Return to [database documentation](help/database)
