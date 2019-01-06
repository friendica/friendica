<?php

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Database\DBA;

class TagUpdate
{
	public static function execute()
	{
		$messages = DBA::p("SELECT `oid`,`item`.`guid`, `item`.`created`, `item`.`received` FROM `term` INNER JOIN `item` ON `item`.`id`=`term`.`oid` WHERE `term`.`otype` = 1 AND `term`.`guid` = ''");

		Logger::log('fetched messages: ' . DBA::numRows($messages));
		while ($message = DBA::fetch($messages)) {
			if ($message['uid'] == 0) {
				$global = true;

				DBA::update('term', ['global' => true], ['otype' => TERM_OBJ_POST, 'guid' => $message['guid']]);
			} else {
				$global = (DBA::count('term', ['uid' => 0, 'otype' => TERM_OBJ_POST, 'guid' => $message['guid']]) > 0);
			}

			$fields = ['guid' => $message['guid'], 'created' => $message['created'],
				'received' => $message['received'], 'global' => $global];
			DBA::update('term', $fields, ['otype' => TERM_OBJ_POST, 'oid' => $message['oid']]);
		}

		DBA::close($messages);

		$messages = DBA::select('item', ['guid'], ['uid' => 0]);

		Logger::log('fetched messages: ' . DBA::numRows($messages));
		while ($message = DBA::fetch($messages)) {
			DBA::update('item', ['global' => true], ['guid' => $message['guid']]);
		}

		DBA::close($messages);
	}
}
