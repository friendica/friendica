<?php

namespace Friendica\Core\Logger\Handler;

use Friendica\Core\Config\IConfigurable;
use Psr\Log\LogLevel;

/**
 * Simple handler for Friendica developers to use for deeper logging
 *
 * If you want to debug only interactions from your IP or the IP of a remote server for federation debug,
 * you'll use Logger::develop() for the duration of your work, and you clean it up when you're done before submitting your PR.
 */
class MonologDevelopHandler extends MonologStreamHandler implements IConfigurable
{
	/**
	 * @var string The IP of the developer who wants to debug
	 */
	private $developerIp;

	/**
	 * {@inheritdoc}
	 * @param string $developerIp  The IP of the developer who wants to debug
	 */
	public function __construct($name, $developerIp = '', $description = '', $enabled = true, $logfile = '', $loglevel = LogLevel::NOTICE)
	{
		parent::__construct($name, $description, $enabled, $logfile, $loglevel);

		$this->developerIp = $developerIp;
	}

	/**
	 * {@inheritdoc}
	 */
	public function handle(array $record)
	{
		if (!$this->isHandling($record)) {
			return false;
		}

		/// Just in case the remote IP is the same as the developer IP log the output
		if (!is_null($this->developerIp) && $_SERVER['REMOTE_ADDR'] != $this->developerIp)
		{
			return false;
		}

		return false === $this->bubble;
	}

	/**
	 * {@inheritdoc}
	 */
	function loadConfig()
	{
		parent::loadConfig();
		$this->developerIp = $this->getConfig('dlogip', '');
	}

	/**
	 * {@inheritdoc}
	 */
	function saveConfig()
	{
		parent::saveConfig();
		$this->setConfig('dlogip', $this->developerIp);
	}

	/**
	 * {@inheritdoc}
	 */
	function toArray()
	{
		return [
			'enabled' => $this->enabled,
			'developer_ip' => $this->developerIp,
		];
	}
}
