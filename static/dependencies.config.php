<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
 * @link https://r.je/dice
 */

use Dice\Dice;

/**
 * @param string $basepath The base path of the Friendica installation without trailing slash
 */
return (function(string $basepath, array $getVars, array $serverVars, array $cookieVars): array {
	return [
		'*' => [
			// marks all class result as shared for other creations, so there's just
			// one instance for the whole execution
			'shared' => true,
		],
		\Friendica\Core\Addon\Capability\ICanLoadAddons::class => [
			'instanceOf' => \Friendica\Core\Addon\Model\AddonLoader::class,
			'constructParams' => [
				$basepath,
				[Dice::INSTANCE => Dice::SELF],
			],
		],
		\Friendica\Core\Addon\AddonHelper::class => [
			'instanceOf' => \Friendica\Core\Addon\AddonProxy::class,
			'constructParams' => [
				$basepath . '/addon',
			],
		],
		\Friendica\Util\BasePath::class => [
			'constructParams' => [
				$basepath,
				$serverVars,
			]
		],
		\Friendica\Core\Hooks\Model\DiceInstanceManager::class => [
			'constructParams' => [
				[Dice::INSTANCE => Dice::SELF],
			]
		],
		\Friendica\Core\Hooks\Util\StrategiesFileManager::class => [
			'constructParams' => [
				$basepath,
			],
			'call' => [
				['loadConfig'],
			],
		],
		\Friendica\Core\Hooks\Capability\ICanRegisterStrategies::class => [
			'instanceOf' => \Friendica\Core\Hooks\Model\DiceInstanceManager::class,
			'constructParams' => [
				[Dice::INSTANCE => Dice::SELF],
			],
		],
		\Friendica\AppHelper::class => [
			'instanceOf' => \Friendica\AppLegacy::class,
		],
		\Friendica\Core\Hooks\Capability\ICanCreateInstances::class => [
			'instanceOf' => \Friendica\Core\Hooks\Model\DiceInstanceManager::class,
			'constructParams' => [
				[Dice::INSTANCE => Dice::SELF],
			],
		],
		\Friendica\Core\Config\Util\ConfigFileManager::class => [
			'instanceOf' => \Friendica\Core\Config\Factory\Config::class,
			'call' => [
				['createConfigFileManager', [
					$basepath,
					$basepath . '/addon',
					$serverVars,
				], Dice::CHAIN_CALL],
			],
		],
		\Friendica\Core\Config\ValueObject\Cache::class => [
			'instanceOf' => \Friendica\Core\Config\Factory\Config::class,
			'call' => [
				['createCache', [], Dice::CHAIN_CALL],
			],
		],
		\Friendica\App\Mode::class => [
			'call' => [
				['determineRunMode', [true, $serverVars], Dice::CHAIN_CALL],
				['determine', [
					$basepath,
				], Dice::CHAIN_CALL],
			],
		],
		\Friendica\Core\Config\Capability\IManageConfigValues::class => [
			'instanceOf' => \Friendica\Core\Config\Model\DatabaseConfig::class,
			'constructParams' => [
				$serverVars,
			],
		],
		\Friendica\Core\PConfig\Capability\IManagePersonalConfigValues::class => [
			'instanceOf' => \Friendica\Core\PConfig\Factory\PConfig::class,
			'call' => [
				['create', [], Dice::CHAIN_CALL],
			]
		],
		\Friendica\Database\Definition\DbaDefinition::class => [
			'constructParams' => [
				$basepath,
			],
			'call' => [
				['load', [false], Dice::CHAIN_CALL],
			],
		],
		\Friendica\Database\Definition\ViewDefinition::class => [
			'constructParams' => [
				$basepath,
			],
			'call' => [
				['load', [false], Dice::CHAIN_CALL],
			],
		],
		\Friendica\Database\Database::class => [
			'constructParams' => [
				[Dice::INSTANCE => \Friendica\Core\Config\Model\ReadOnlyFileConfig::class],
			],
		],
		\Friendica\App\BaseURL::class => [
			'constructParams' => [
				$serverVars,
			],
		],
		'$hostname' => [
			'instanceOf' => \Friendica\App\BaseURL::class,
			'constructParams' => [
				$serverVars,
			],
			'call' => [
				['getHost', [], Dice::CHAIN_CALL],
			],
		],
		\Friendica\Core\Cache\Type\AbstractCache::class => [
			'constructParams' => [
				[Dice::INSTANCE => '$hostname'],
			],
		],
		\Friendica\App\Page::class => [
			'constructParams' => [
				$basepath,
			],
		],
		\Psr\Log\LoggerInterface::class => [
			'instanceOf' => \Friendica\Core\Logger\LoggerManager::class,
			'call' => [
				['getLogger', [], Dice::CHAIN_CALL],
			],
		],
		\Friendica\Core\Logger\LoggerManager::class => [
			'substitutions' => [
				\Friendica\Core\Logger\Factory\LoggerFactory::class => \Friendica\Core\Logger\Factory\LegacyLoggerFactory::class,
			],
		],
		\Friendica\Core\Logger\Factory\LoggerFactory::class => [
			'instanceOf' => \Friendica\Core\Logger\Factory\LegacyLoggerFactory::class,
		],
		\Friendica\Core\Logger\Type\SyslogLogger::class => [
			'instanceOf' => \Friendica\Core\Logger\Factory\SyslogLogger::class,
			'call' => [
				['create', [], Dice::CHAIN_CALL],
			],
		],
		\Friendica\Core\Logger\Type\StreamLogger::class => [
			'instanceOf' => \Friendica\Core\Logger\Factory\StreamLogger::class,
			'call' => [
				['create', [], Dice::CHAIN_CALL],
			],
		],
		\Psr\EventDispatcher\EventDispatcherInterface::class => [
			'instanceOf' => \Friendica\Event\EventDispatcher::class,
		],
		\Friendica\Core\Logger\Capability\IHaveCallIntrospections::class => [
			'instanceOf' => \Friendica\Core\Logger\Util\Introspection::class,
			'constructParams' => [
				\Friendica\Core\Logger\Capability\IHaveCallIntrospections::IGNORE_CLASS_LIST,
			],
		],
		\Friendica\Core\Cache\Capability\ICanCache::class => [
			'instanceOf' => \Friendica\Core\Cache\Factory\Cache::class,
			'call' => [
				['createLocal', [], Dice::CHAIN_CALL],
			],
		],
		\Friendica\Core\Cache\Capability\ICanCacheInMemory::class => [
			'instanceOf' => \Friendica\Core\Cache\Factory\Cache::class,
			'call' => [
				['createLocal', [], Dice::CHAIN_CALL],
			],
		],
		\Friendica\Core\Lock\Capability\ICanLock::class => [
			'instanceOf' => \Friendica\Core\Lock\Factory\Lock::class,
			'call' => [
				['create', [], Dice::CHAIN_CALL],
			],
		],
		\Friendica\App\Arguments::class => [
			'instanceOf' => \Friendica\App\Arguments::class,
			'call' => [
				['determine', [$serverVars, $getVars], Dice::CHAIN_CALL],
			],
		],
		\Friendica\Core\System::class => [
			'constructParams' => [
				$basepath,
			],
		],
		\Friendica\App\Router::class => [
			'constructParams' => [
				$serverVars,
				__DIR__ . '/routes.config.php',
				null
			],
		],
		\Friendica\Core\L10n::class => [
			'constructParams' => [
				$serverVars, $getVars
			],
		],
		\Friendica\Core\Session\Capability\IHandleSessions::class => [
			'instanceOf' => \Friendica\Core\Session\Factory\Session::class,
			'call' => [
				['create', [$serverVars], Dice::CHAIN_CALL],
				['start', [], Dice::CHAIN_CALL],
			],
		],
		\Friendica\Core\Session\Capability\IHandleUserSessions::class => [
			'instanceOf' => \Friendica\Core\Session\Model\UserSession::class,
		],
		\Friendica\Model\User\Cookie::class => [
			'constructParams' => [
				$cookieVars,
			],
		],
		\Friendica\Core\Storage\Capability\ICanWriteToStorage::class => [
			'instanceOf' => \Friendica\Core\Storage\Repository\StorageManager::class,
			'call' => [
				['getBackend', [], Dice::CHAIN_CALL],
			],
		],
		\Friendica\Core\KeyValueStorage\Capability\IManageKeyValuePairs::class => [
			'instanceOf' => \Friendica\Core\KeyValueStorage\Factory\KeyValueStorage::class,
			'call' => [
				['create', [], Dice::CHAIN_CALL],
			],
		],
		\Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests::class => [
			'instanceOf' => \Friendica\Network\HTTPClient\Factory\HttpClient::class,
			'call' => [
				['createClient', [], Dice::CHAIN_CALL],
			],
		],
		\Friendica\Model\Log\ParsedLogIterator::class => [
			'constructParams' => [
				[Dice::INSTANCE => \Friendica\Util\ReversedFileReader::class],
			]
		],
		\Friendica\Core\Worker\Repository\Process::class => [
			'constructParams' => [
				$serverVars
			],
		],
		\Friendica\App\Request::class => [
			'constructParams' => [
				$serverVars
			],
		],
		\Psr\Clock\ClockInterface::class => [
			'instanceOf' => \Friendica\Util\Clock\SystemClock::class
		],
		\Friendica\Module\Special\HTTPException::class => [
			'constructParams' => [
				$serverVars
			],
		],
		\Friendica\Module\Api\ApiResponse::class => [
			'constructParams' => [
				$serverVars,
				$getVars['callback'] ?? '',
			],
		],
	];
})(
	dirname(__FILE__, 2),
	$_GET,
	$_SERVER,
	$_COOKIE
);
