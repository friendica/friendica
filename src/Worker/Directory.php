<?php
/**
 * @file src/Worker/Directory.php
 * @brief Sends updated profile data to the directory
 */

namespace Friendica\Worker;

use Friendica\App;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Util\Network;

class Directory
{
	public static function execute($url = '')
	{
		$dir = Config::get('system', 'directory');

		if (!strlen($dir)) {
			return;
		}

		if ($url == '') {
			self::updateAll();
			return;
		}

		$dir .= "/submit";

		$arr = ['url' => $url];

		Addon::callHooks('globaldir_update', $arr);

		App::logger('Updating directory: ' . $arr['url'], LOGGER_DEBUG);
		if (strlen($arr['url'])) {
			Network::fetchUrl($dir . '?url=' . bin2hex($arr['url']));
		}

		return;
	}

	private static function updateAll() {
		$r = q("SELECT `url` FROM `contact`
			INNER JOIN `profile` ON `profile`.`uid` = `contact`.`uid`
			INNER JOIN `user` ON `user`.`uid` = `contact`.`uid`
				WHERE `contact`.`self` AND `profile`.`net-publish` AND `profile`.`is-default` AND
					NOT `user`.`account_expired` AND `user`.`verified`");

		if (DBA::isResult($r)) {
			foreach ($r AS $user) {
				Worker::add(PRIORITY_LOW, 'Directory', $user['url']);
			}
		}
	}
}
