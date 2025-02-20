Updating Friendica
===============

* [Home](help)

## Using a Friendica archive

If you installed Friendica in the ``path/to/friendica`` folder:

1. Unpack the new Friendica archive in ``path/to/friendica_new``.
2. Copy  the following items from ``path/to/friendica`` to ``path/to/friendica_new``:
   * ``config/local.config.php``
   * ``proxy/``
   * ``.htaccess`` if using Apache web server

    The following items only need to be copied if they are located inside your friendica path:
   * your storage folder as set in **Admin -> Site -> File Upload -> Storage base path**
   * your item cache as set in **Admin -> Site -> Performance -> Path to item cache**
   * your temp folder as set in **Admin -> Site -> Advanced -> Temp path**
3. Rename the ``path/to/friendica`` folder to ``path/to/friendica_old``.
4. Rename the ``path/to/friendica_new`` folder to ``path/to/friendica``.
5. Check your site. Note: it may go into maintenance mode to update the database schema.
6. If everything works, just delete the ``path/to/friendica_old`` folder.

To update Addons from an archive, simply delete the ``path/to/friendica/addon`` and replace it with the provided archive.

## Using Git

You can get the latest changes at any time with

    cd path/to/friendica
    git pull
    bin/composer.phar run install:prod

The addon tree has to be updated separately like so:

    cd path/to/friendica/addon
    git pull

For both repositories:
The default branch to use is the ``stable`` branch, which is the stable version of Friendica.
It is updated about four times a year on a fixed schedule.

If you want to use and test bleeding edge code please checkout the ``develop`` branch.
The new features and fixes will be merged from ``develop`` into ``stable`` after a release candidate period before each release.

Warning: The ``develop`` branch is unstable, and breaks on average once a month for at most 24 hours until a patch is submitted and merged.
Be sure to pull frequently if you choose the ``develop`` branch.

### Considerations before upgrading Friendica

#### MySQL >= 5.7.4

Starting from MySQL version 5.7.4, the IGNORE keyword in ALTER TABLE statements is ignored.
This prevents automatic table deduplication if a UNIQUE index is added to a Friendica table's structure.
If a DB update fails for you while creating a UNIQUE index, make sure to manually deduplicate the table before trying the update again.

#### Manual deduplication

There are two main ways of doing it, either by manually removing the duplicates or by recreating the table.
Manually removing the duplicates is usually faster if they're not too numerous.
To manually remove the duplicates, you need to know the UNIQUE index columns available in `database.sql`.

```SQL
SELECT GROUP_CONCAT(id), <index columns>, count(*) as count FROM users
GROUP BY <index columns> HAVING count >= 2;

/* delete or merge duplicate from above query */;
```

If there are too many rows to handle manually, you can create a new table with the same structure as the table with duplicates and insert the existing content with INSERT IGNORE.
To recreate the table you need to know the table structure available in `database.sql`.

```SQL
CREATE TABLE <table_name>_new <rest of the CREATE TABLE>;
INSERT IGNORE INTO <table_name>_new SELECT * FROM <table_name>;
DROP TABLE <table_name>;
RENAME TABLE <table_name>_new TO <table_name>;
```

This method is slower overall, but it is better suited for large numbers of duplicates.

### Resolving Possible Database Issues Post Upgrading

#### Foreign Keys

Some of the updates include the use of foreign keys now that will bump into issues with previous versions, which would sometimes shove bad data into tables, preventing, causing errors such as below.

```
Error 1452 occurred during database update:
Cannot add or update a child row: a foreign key constraint fails (`friendica`.`#sql-10ea6_5a6d`, CONSTRAINT `#sql-10ea6_5a6d_ibfk_1` FOREIGN KEY (`contact-id`) REFERENCES `contact` (`id`))
ALTER TABLE `thread` ADD FOREIGN KEY (`iid`) REFERENCES `item` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE;
```

All current known fixes for possible items that can go wrong are as below.

```SQL
DELETE FROM `item` WHERE `owner-id` NOT IN (SELECT `id` FROM `contact`);
DELETE FROM `item` WHERE `contact-id` NOT IN (SELECT `id` FROM `contact`);
DELETE FROM `notify` WHERE `uri-id` NOT IN (SELECT `id` FROM `item-uri`);
DELETE FROM `photo` WHERE `contact-id` NOT IN (SELECT `id` FROM `contact`);
DELETE FROM `thread` WHERE `iid` NOT IN (SELECT `id` FROM `item`);
DELETE FROM `item` WHERE `author-id` NOT IN (SELECT `id` FROM `contact`);
DELETE FROM `diaspora-interaction` WHERE `uri-id` NOT IN (SELECT `id` FROM `item-uri`);
```

This all has been compiled as of currently from issue #9746, #9753, and #9878.
