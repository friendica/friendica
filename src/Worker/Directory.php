<?php
/**
 * @file src/Worker/Directory.php
 * @brief Sends updated profile data to the directory
 */

namespace Friendica\Worker;

use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Util\Network;

class Directory extends AbstractWorker
{
	/**
	 * {@inheritdoc}
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function execute(array $parameters = [])
	{
		if (!$this->checkParameters($parameters, 1)) {
			return;
		}

		$url = $parameters[0];

		$dir = Config::get('system', 'directory');

		if (!strlen($dir)) {
			return;
		}

		if ($url == '') {
			$this->updateAll();
			return;
		}

		$dir .= "/submit";

		$arr = ['url' => $url];

		Hook::callAll('globaldir_update', $arr);

		$this->logger->info('Updating directory: ' . $arr['url']);
		if (strlen($arr['url'])) {
			Network::fetchUrl($dir . '?url=' . bin2hex($arr['url']));
		}

		return;
	}

	private function updateAll() {
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
