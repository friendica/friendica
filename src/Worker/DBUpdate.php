<?php
/**
 * @file src/Worker/DBUpdate.php
 * @brief This file is called when the database structure needs to be updated
 */
namespace Friendica\Worker;

use Friendica\Core\Update;

class DBUpdate extends AbstractWorker
{
	public function execute(array $parameters = [])
	{
		Update::run($this->app->getBasePath(), $this->logger);
	}
}
