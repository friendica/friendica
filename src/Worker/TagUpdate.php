<?php

namespace Friendica\Worker;

use dba;

class TagUpdate
{
	public static function execute()
	{
		$messages = dba::p("SELECT `oid`,`item`.`guid`, `item`.`created`, `item`.`received` FROM `term` INNER JOIN `item` ON `item`.`id`=`term`.`oid` WHERE `term`.`otype` = 1 AND `term`.`guid` = ''");

		logger('fetched messages: ' . dba::num_rows($messages));
		while ($message = dba::fetch($messages)) {
			if ($message['uid'] == 0) {
				$global = true;

				dba::update('term', ['global' => true], ['otype' => TERM_OBJ_POST, 'guid' => $message['guid']]);
			} else {
				$global = (dba::count('term', ['uid' => 0, 'otype' => TERM_OBJ_POST, 'guid' => $message['guid']]) > 0);
			}

			$fields = ['guid' => $message['guid'], 'created' => $message['created'],
				'received' => $message['received'], 'global' => $global];
			dba::update('term', $fields, ['otype' => TERM_OBJ_POST, 'oid' => $message['oid']]);
		}

		dba::close($messages);

		$messages = dba::p("SELECT `guid` FROM `item` WHERE `uid` = 0");

		logger('fetched messages: ' . dba::num_rows($messages));
		while ($message = dba::fetch(messages)) {
			dba::update('item', ['global' => true], ['guid' => $message['guid']]);
		}

		dba::close($messages);
	}
}
