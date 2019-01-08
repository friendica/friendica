<?php

use Psr\Container\ContainerInterface;

$container['logger'] = function (ContainerInterface $c) {
	$loglevel = \Psr\Log\LogLevel::DEBUG;

	$logger = new Monolog\Logger('test');
	$logger->pushProcessor(new Monolog\Processor\ProcessIdProcessor());
	$logger->pushProcessor(new \Monolog\Processor\UidProcessor());
	$logger->pushProcessor(new Monolog\Processor\IntrospectionProcessor(\Psr\Log\LogLevel::DEBUG, [], 1));

	$formatter = new Monolog\Formatter\LineFormatter("\"%datetime% %channel% [%level_name%]: %message% %context% %extra%\n");
	$handler = new Monolog\Handler\TestHandler($loglevel);
	$handler->setFormatter($formatter);
	$logger->pushHandler($handler);
	return $logger;
};

$container['dlogger'] = function (ContainerInterface $c) {
	$loglevel = \Psr\Log\LogLevel::DEBUG;

	$logger = new Monolog\Logger('develop');
	$logger->pushProcessor(new \Monolog\Processor\UidProcessor());
	$logger->pushProcessor(new Monolog\Processor\ProcessIdProcessor());
	$logger->pushProcessor(new Monolog\Processor\IntrospectionProcessor(\Psr\Log\LogLevel::DEBUG, [], 1));

	$formatter = new Monolog\Formatter\LineFormatter("%datetime% %channel% [%level_name%]: %message% %context% %extra%\n");
	$handler = new Monolog\Handler\TestHandler($loglevel);
	$handler->setFormatter($formatter);
	$logger->pushHandler($handler);

	return $logger;
};
