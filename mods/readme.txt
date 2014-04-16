Site speed can be improved when the following indexes are set. They 
cannot be set through the update script because on large sites they will 
block the site for several minutes.

CREATE INDEX `uid_commented` ON `item` (`uid`, `commented`);
CREATE INDEX `uid_created` ON `item` (`uid`, `created`);
CREATE INDEX `uid_unseen` ON `item` (`uid`, `unseen`);
CREATE INDEX `resource-id` ON `item` (`resource-id`);
CREATE INDEX `uri_received` ON item(`uri`, `received`);
CREATE INDEX `received_uri` ON item(`received`, `uri`);
CREATE INDEX `contact-id_created` ON item(`contact-id`, created);
CREATE INDEX `uid_network_received` ON item(`uid`, `network`, `received`);
CREATE INDEX `uid_parent` ON item(`uid`, `parent`);
CREATE INDEX `uid_received` ON item(`uid`, `received`);
CREATE INDEX `uid_network_commented` ON item(`uid`, `network`, `commented`);
CREATE INDEX `uid_commented` ON item(`uid`, `commented`);
CREATE INDEX `uid_title` ON item(uid, `title`);
CREATE INDEX `created_contact-id` ON item(`created`, `contact-id`);
