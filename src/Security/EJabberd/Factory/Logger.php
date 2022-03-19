<?php

namespace Friendica\Security\EJabberd\Factory;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Database\Database;
use Friendica\Util\FileSystem;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Logger
{
	const CHANNEL = 'auth_jabberd';

	/** @var \Friendica\Core\Logger\Factory\Logger */
	protected $loggerFac;
	/** @var IManageConfigValues */
	protected $config;

	public function __construct(\Friendica\Core\Logger\Factory\Logger $loggerFac, IManageConfigValues $config)
	{
		$this->loggerFac = $loggerFac;
		$this->config    = $config;
	}

	public function create(Database $database, Profiler $profiler, FileSystem $filesystem): LoggerInterface
	{
		return $this->loggerFac->create($database, $this->config, $profiler, $filesystem, $this->config->get('jabber', 'debug', LogLevel::NOTICE));
	}
}
