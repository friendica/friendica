<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
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

use Dice\Dice;
use Friendica\App;
use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Lock\ILock;
use Friendica\Core\Process;
use Friendica\Core\Session\ISession;
use Friendica\Core\StorageManager;
use Friendica\Database\Database;
use Friendica\Factory;
use Friendica\Model\Storage\IWritableStorage;
use Friendica\Model\User\Cookie;
use Friendica\Model\Log\ParsedLogIterator;
use Friendica\Network;
use Friendica\Util;
use Psr\Log\LoggerInterface;

return [
	'*'                             => [
		// marks all class result as shared for other creations, so there's just
		// one instance for the whole execution
		'shared' => true,
	],
	'$basepath'                     => [
		'instanceOf'      => Util\BasePath::class,
		'call'            => [
			['getPath', [], Dice::CHAIN_CALL],
		],
		'constructParams' => [
			dirname(__FILE__, 2),
			$_SERVER
		]
	],
	Util\BasePath::class         => [
		'constructParams' => [
			dirname(__FILE__, 2),
			$_SERVER
		]
	],
	Util\ConfigFileLoader::class => [
		'shared'          => true,
		'constructParams' => [
			[Dice::INSTANCE => '$basepath'],
		],
	],
	Config\Cache::class          => [
		'instanceOf' => Factory\ConfigFactory::class,
		'call'       => [
			['createCache', [$_SERVER], Dice::CHAIN_CALL],
		],
	],
	App\Mode::class              => [
		'call' => [
			['determineRunMode', [true, $_SERVER], Dice::CHAIN_CALL],
			['determine', [], Dice::CHAIN_CALL],
		],
	],
	Config\IConfig::class                   => [
		'instanceOf' => Factory\ConfigFactory::class,
		'call'       => [
			['createConfig', [], Dice::CHAIN_CALL],
		],
	],
	\Friendica\Core\PConfig\IPConfig::class => [
		'instanceOf' => Factory\ConfigFactory::class,
		'call'       => [
			['createPConfig', [], Dice::CHAIN_CALL],
		]
	],
	Database::class                         => [
		'constructParams' => [
			[Dice::INSTANCE => \Psr\Log\NullLogger::class],
		],
	],
	/**
	 * Creates the App\BaseURL
	 *
	 * Same as:
	 *   $baseURL = new App\BaseURL($configuration, $_SERVER);
	 */
	App\BaseURL::class             => [
		'constructParams' => [
			$_SERVER,
		],
	],
	App\Page::class => [
		'constructParams' => [
			[Dice::INSTANCE => '$basepath'],
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
	LoggerInterface::class          => [
		'instanceOf' => Factory\LoggerFactory::class,
		'constructParams' => [
			'index',
		],
		'call'       => [
			['create', ['index'], Dice::CHAIN_CALL],
		],
	],
	'$devLogger'                    => [
		'instanceOf' => Factory\LoggerFactory::class,
		'constructParams' => [
			'dev',
		],
		'call'       => [
			['createDev', [], Dice::CHAIN_CALL],
		]
	],
	Cache\ICache::class             => [
		'instanceOf' => Factory\CacheFactory::class,
		'call'       => [
			['create', [], Dice::CHAIN_CALL],
		],
	],
	Cache\IMemoryCache::class       => [
		'instanceOf' => Factory\CacheFactory::class,
		'call'       => [
			['create', [], Dice::CHAIN_CALL],
		],
	],
	ILock::class                    => [
		'instanceOf' => Factory\LockFactory::class,
		'call'       => [
			['create', [], Dice::CHAIN_CALL],
		],
	],
	App\Arguments::class => [
		'instanceOf' => App\Arguments::class,
		'call' => [
			['determine', [$_SERVER, $_GET], Dice::CHAIN_CALL],
		],
	],
	App\Module::class => [
		'instanceOf' => App\Module::class,
		'call' => [
			['determineModule', [], Dice::CHAIN_CALL],
		],
	],
	Process::class => [
		'constructParams' => [
			[Dice::INSTANCE => '$basepath'],
			getmypid(),
		],
	],
	App\Router::class => [
		'constructParams' => [
			$_SERVER,
			__DIR__ . '/routes.config.php',
			null
		],
	],
	L10n::class => [
		'constructParams' => [
			$_SERVER, $_GET
		],
	],
	ISession::class => [
		'instanceOf' => Factory\SessionFactory::class,
		'call' => [
			['createSession', [$_SERVER], Dice::CHAIN_CALL],
			['start', [], Dice::CHAIN_CALL],
		],
	],
	Cookie::class => [
		'constructParams' => [
			$_SERVER, $_COOKIE
		],
	],
	IWritableStorage::class => [
		'instanceOf' => StorageManager::class,
		'call' => [
			['getBackend', [], Dice::CHAIN_CALL],
		],
	],
	Network\IHTTPClient::class => [
		'instanceOf' => Factory\HTTPClientFactory::class,
		'call'       => [
			['createClient', [], Dice::CHAIN_CALL],
		],
	],
	Factory\Api\Mastodon\Error::class => [
		'constructParams' => [
			$_SERVER
		],
	],
	ParsedLogIterator::class => [
		'constructParams' => [
			[Dice::INSTANCE => Util\ReversedFileReader::class],
		]
	],
];
