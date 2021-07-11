<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\DowngradePhp72\Rector\ClassMethod\DowngradeParameterTypeWideningRector;
use Rector\Set\ValueObject\DowngradeSetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
	$parameters = $containerConfigurator->parameters();

	$parameters->set(Option::PATHS, [
		__DIR__ . '/bin',
		__DIR__ . '/config',
		__DIR__ . '/include',
		__DIR__ . '/mod',
		__DIR__ . '/src',
		__DIR__ . '/static',
		__DIR__ . '/view',
	]);

	$parameters->set(Option::SKIP, [
		// doesn't work as expected
		DowngradeParameterTypeWideningRector::class,
		__DIR__ . '/bin/dev'
	]);

	$containerConfigurator->import(DowngradeSetList::PHP_80);
	$containerConfigurator->import(DowngradeSetList::PHP_74);
	$containerConfigurator->import(DowngradeSetList::PHP_73);
	$containerConfigurator->import(DowngradeSetList::PHP_72);
	$containerConfigurator->import(DowngradeSetList::PHP_71);
};
