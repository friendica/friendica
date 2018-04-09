<?php

namespace Friendica\Worker;

class TagUpdate
{
	public static function execute()
	{
		$messages = dba::p("SELECT `oid`,`item`.`guid`, `item`.`created`, `item`.`received` FROM `term` INNER JOIN `item` ON `item`.`id`=`term`.`oid` WHERE `term`.`otype` = 1 AND `term`.`guid` = ''");

		logger('fetched messages: ' . dba::num_rows($messages));
		while ($message = dba::fetch($messages)) {
			if ($message['uid'] == 0) {
				$global = true;

				q("UPDATE `term` SET `global` = 1 WHERE `otype` = %d AND `guid` = '%s'",
					intval(TERM_OBJ_POST), dbesc($message['guid']));
			} else {
				$isglobal = q("SELECT `global` FROM `term` WHERE `uid` = 0 AND `otype` = %d AND `guid` = '%s'",
					intval(TERM_OBJ_POST), dbesc($message['guid']));

				$global = (count($isglobal) > 0);
			}

			q("UPDATE `term` SET `guid` = '%s', `created` = '%s', `received` = '%s', `global` = %d WHERE `otype` = %d AND `oid` = %d",
				dbesc($message['guid']), dbesc($message['created']), dbesc($message['received']),
				intval($global), intval(TERM_OBJ_POST), intval($message['oid']));
		}

		dba::close($messages);

		$messages = dba::p("SELECT `guid` FROM `item` WHERE `uid` = 0");

		logger('fetched messages: ' . dba::num_rows($messages));
		while ($message = dba::fetch(messages)) {
			q("UPDATE `item` SET `global` = 1 WHERE `guid` = '%s'", dbesc($message['guid']));
		}

		dba::close($messages);
	}
}
