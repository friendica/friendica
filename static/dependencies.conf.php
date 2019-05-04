<?php

use Dice\Dice;
use Friendica\App;
use Friendica\Core\Config;
use Friendica\Database\Database;
use Friendica\Factory;
use Friendica\Util;
use Psr\Log\LoggerInterface;

/**
 * The configuration defines "complex" dependencies inside Friendica
 * So this classes shouldn't be simple or their dependencies are already defined here.
 *
 * This kind of dependencies are NOT required to be defined here:
 *   - $a = new ClassA(new ClassB());
 *   - $a = new ClassA();
 *   - $a = new ClassA(Configuration $configuration);
 *
 * This kind of dependencies SHOULD be defined here:
 *   - $a = new ClassA();
 *     $b = $a->create();
 *
 *   - $a = new ClassA($creationPassedVariable);
 *
 */
return [
	'*' => [
		'substitutions' => [
			Config\Cache\IConfigCache::class      => Config\Cache\ConfigCache::class,
			Config\Cache\IPConfigCache::class     => Config\Cache\ConfigCache::class,
			Config\Adapter\IConfigAdapter::class  => Config\Adapter\JITConfigAdapter::class,
			Config\Adapter\IPConfigAdapter::class => Config\Adapter\JITPConfigAdapter::class,
		],
	],
	Util\BasePath::class => [
		'shared'          => true,
		'constructParams' => [
			dirname(__FILE__, 2),
			$_SERVER
		]
	],
	Config\Cache\ConfigCache::class => [
		'instanceOf' => Factory\ConfigFactory::class,
		'call'       => [
			['createCache', [], Dice::CHAIN_CALL],
		],
		'shared'     => true,
	],
	App\Mode::class => [
		'call'   => [
			['determine', [], Dice::CHAIN_CALL],
		],
		// marks the result as shared for other creations, so there's just
		// one instance for the whole execution
		'shared' => true,
	],
	Config\Configuration::class => [
		'shared' => true,
	],
	Config\PConfiguration::class => [
		'shared' => true,
	],
	Database::class => [
		'shared' => true,
	],
	/**
	 * Creates the Util\BaseURL
	 *
	 * Same as:
	 *   $baseURL = new Util\BaseURL($configuration, $_SERVER);
	 */
	Util\BaseURL::class => [
		'shared'          => true,
		'constructParams' => [
			$_SERVER,
		],
	],
	/**
	 * Create a Logger, which implements the LoggerInterface
	 *
	 * Same as:
	 *   $loggerFactory = new Factory\LoggerFactory();
	 *   $logger = $loggerFactory->create($channel, $configuration, $profiler);
	 *
	 * Attention1: We can use DICE for detecting dependencies inside "chained" calls too
	 * Attention2: The variable "$channel" is passed inside the creation of the dependencies per:
	 *    $app = $dice->create(App::class, [], ['$channel' => 'index']);
	 *    and is automatically passed as an argument with the same name
	 */
	LoggerInterface::class => [
		'shared'     => true,
		'instanceOf' => Factory\LoggerFactory::class,
		'call'       => [
			['create', [], Dice::CHAIN_CALL],
		],
	],
	'$devLogger' => [
		'shared'     => true,
		'instanceOf' => Factory\LoggerFactory::class,
		'call'       => [
			['createDev', [], Dice::CHAIN_CALL],
		]
	]
];
