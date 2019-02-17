<?php
/**
 * @file src/Worker/ForkHook.php
 */

namespace Friendica\Worker;

use Friendica\Core\Hook;

Class ForkHook extends AbstractWorker
{
	/**
	 * {@inheritdoc}
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function execute(array $parameters = [])
	{
		if (!$this->checkParameters($parameters, 3)) {
			return;
		}

		list($name, $hook, $data) = $parameters;

		Hook::callSingle($this->app, $name, $hook, $data);
	}
}
