<?php

namespace Friendica\Core\Logger;

use Friendica\Core\Logger\Handler\ILogHandler;
use Monolog;

/**
 * A Logger instance of Friendica and extends the Monolog logger
 * Implements an interface for the possibility to switch to other logging-frameworks if necessary
 */
class MonologLogger extends Monolog\Logger implements IFriendicaLogger
{
	use LogConfigTrait;

	private $handlerNames;
	private $error;

	public function __construct($name, $handlerNames = [], $error = false)
	{
		$this->config_category = 'log_channel';

		parent::__construct($name);
		$this->handlerNames = $handlerNames;
		$this->error = $error;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getChannel()
	{
		return $this->name;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return Monolog\Handler\TestHandler the Handling for tests
	 */
	function enableTest()
	{
		// enable the test handler
		$fileHandler = new Monolog\Handler\TestHandler();
		$formatter = new Monolog\Formatter\LineFormatter("%datetime% %channel% [%level_name%]: %message% %context% %extra%\n");
		$fileHandler->setFormatter($formatter);

		$this->setHandlers(
			[$fileHandler]
		);

		return $fileHandler;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return array
	 */
	function getHandlerNames()
	{
		return $this->handlerNames;
	}

	/**
	 * {@inheritdoc}
	 */
	public function addHandler(ILogHandler $logHandler)
	{
		if ($logHandler instanceof Monolog\Handler\HandlerInterface) {
			$this->pushHandler($logHandler);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	function loadConfig()
	{
		$handlerNames = $this->getConfig('handler', []);
		if (is_array($handlerNames)) {
			$this->handlerNames = $handlerNames;
		}
		$this->error = $this->getConfig('errors', false);
	}

	/**
	 * {@inheritdoc}
	 */
	function saveConfig()
	{
		$this->setConfig('handler', $this->handlerNames);
		$this->setConfig('errors', $this->error);
	}

	/**
	 * {@inheritdoc}
	 */
	function toArray()
	{
		return [
			'channel' => $this->name,
			'handler' => $this->handlerNames,
			'errors'  => $this->error,
		];
	}
}
