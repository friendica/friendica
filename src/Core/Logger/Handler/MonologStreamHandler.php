<?php

namespace Friendica\Core\Logger\Handler;

use Friendica\Core\Logger\LogConfigTrait;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;

class MonologStreamHandler extends StreamHandler implements ILogHandler
{
	use LogConfigTrait;

	/**
	 * @var string The name of this handler
	 */
	protected $name;
	/**
	 * @var string The description of this handler
	 */
	protected $description;
	/**
	 * @var bool true if this handler is enabled, otherwise false
	 */
	protected $enabled;

	/**
	 * {@inheritdoc}
	 *
	 * @param $name
	 * @param string $description
	 * @param bool $enabled
	 * @param string $logfile
	 * @param string $loglevel
	 *
	 * @throws \Exception
	 */
	public function __construct($name, $description = '', $enabled = true, $logfile = '', $loglevel = LogLevel::NOTICE)
	{
		parent::__construct($logfile, $loglevel);

		$this->name = $name;
		$this->description = $description;
		$this->enabled = $enabled;

		$formatter = new LineFormatter("%datetime% %channel% [%level_name%]: %message% %context% %extra%\n");
		$this->setFormatter($formatter);
	}

	public function loadConfig()
	{
		$this->description = $this->getConfig('description', $this->description);
		$this->enabled = $this->getConfig('enabled', $this->enabled);
		$this->url = $this->getConfig('logfile', $this->url);
		$this->setLevel($this->getConfig('loglevel', $this->level));
	}

	public function saveConfig()
	{
		$this->setConfig('description', $this->description);
		$this->setConfig('enabled', $this->enabled);
		$this->setConfig('logfile', $this->url);
		$this->setConfig('loglevel', Logger::getLevelName($this->level));
	}

	public function toArray()
	{
		return [
			'name'        => $this->name,
			'enabled'     => $this->enabled,
			'description' => $this->description,
			'logfile'     => $this->url,
			'loglevel'    => Logger::getLevelName($this->level),
		];
	}

}
