# Considerations before upgrading Friendica

* [Home](help)

## MySQL >= 5.7.4

Starting from MySQL version 5.7.4, the IGNORE keyword in ALTER TABLE statements is ignored.
This prevents automatic table deduplication if a UNIQUE index is added to a Friendica table's structure.
If a DB update fails for you while creating a UNIQUE index, make sure to manually deduplicate the table before trying the update again.

### Manual deduplication

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