<?php

/**
 * @file src/Worker/CheckVersion.php
 *
 * @brief save Friendica upstream version to the DB
 **/
namespace Friendica\Worker;

use Friendica\Core\Config;
use Friendica\Database\DBA;
use Friendica\Util\Network;

/**
 * @brief check the git repository VERSION file and save the version to the DB
 *
 * Checking the upstream version is optional (opt-in) and can be done to either
 * the master or the develop branch in the repository.
 */
class CheckVersion extends AbstractWorker
{
	/**
	 * {@inheritdoc}
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function execute(array $parameters = [])
	{
		$this->logger->info('Start.', ['cmd' => 'checkversion']);

		$checkurl = Config::get('system', 'check_new_version_url', 'none');

		switch ($checkurl) {
			case 'master':
				$checked_url = 'https://raw.githubusercontent.com/friendica/friendica/master/VERSION';
				break;
			case 'develop':
				$checked_url = 'https://raw.githubusercontent.com/friendica/friendica/develop/VERSION';
				break;
			default:
				// don't check
				return;
		}
		$this->logger->debug("Checking VERSION.", ['url' => $checked_url]);

		// fetch the VERSION file
		$gitversion = DBA::escape(trim(Network::fetchUrl($checked_url)));
		$this->logger->debug("Upstream VERSION checked.", ['version' => $gitversion]);

		Config::set('system', 'git_friendica_version', $gitversion);

		$this->logger->info('End.');
	}
}
