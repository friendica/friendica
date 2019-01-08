<?php

use Psr\Container\ContainerInterface;

$container['logger'] = function (ContainerInterface $c) {
	$systemConf = $c->get('settings')['system'];
	$debugging = $systemConf['debugging'];
	$logfile = $systemConf['logfile'];
	$loglevel =  \Friendica\Core\Logger::mapLegacyConfigDebugLevel($systemConf['loglevel']);
	$channel = $c->get('settings')['channel'];

	$logger = new Monolog\Logger($channel);
	$logger->pushProcessor(new Monolog\Processor\ProcessIdProcessor());
	$logger->pushProcessor(new \Monolog\Processor\UidProcessor());
	$logger->pushProcessor(new Monolog\Processor\IntrospectionProcessor(\Psr\Log\LogLevel::WARNING, [], 1));

	if ($debugging && isset($logfile) && isset($loglevel)) {
		$formatter = new Monolog\Formatter\LineFormatter("\"%datetime% %channel% [%level_name%]: %message% %context% %extra%\n");
		$handler = new Monolog\Handler\StreamHandler($logfile, $loglevel);
		$handler->setFormatter($formatter);
		$logger->pushHandler($handler);
	}
	return $logger;
};

$container['dlogger'] = function (ContainerInterface $c) {
	$systemConf = $c->get('settings')['system'];
	$logfile = $systemConf['dlogfile'];
	$dlogip = $systemConf['dlogip'];

	$loglevel =  \Psr\Log\LogLevel::DEBUG;
	$logger = new Monolog\Logger('develop');

	$logger->pushProcessor(new \Monolog\Processor\UidProcessor());
	$logger->pushProcessor(new Monolog\Processor\ProcessIdProcessor());
	$logger->pushProcessor(new Monolog\Processor\IntrospectionProcessor(\Psr\Log\LogLevel::DEBUG, [], 1));

	$logger->pushHandler(new \Friendica\Util\Logger\FriendicaDevelopHandler($dlogip));

	if (isset($logfile) && isset($loglevel)) {
		$formatter = new Monolog\Formatter\LineFormatter("%datetime% %channel% [%level_name%]: %message% %context% %extra%\n");
		$handler = new Monolog\Handler\StreamHandler($logfile, $loglevel);
		$handler->setFormatter($formatter);
		$logger->pushHandler($handler);
	}

	return $logger;
};
