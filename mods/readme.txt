Site speed can be improved when the following indexes are set. They cannot be set through the update script because on large sites they will block the site for several minutes.

CREATE INDEX `uid_commented` ON `item` (`uid`, `commented`);
CREATE INDEX `uid_created` ON `item` (`uid`, `created`);
CREATE INDEX `uid_unseen` ON `item` (`uid`, `unseen`);
CREATE INDEX `resource-id` ON `item` (`resource-id`);
