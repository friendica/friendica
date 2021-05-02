<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Set\ValueObject\DowngradeSetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
	// get parameters
	$parameters = $containerConfigurator->parameters();

	// paths to refactor; solid alternative to CLI arguments
	$parameters->set(Option::PATHS, [
		__DIR__ . '/src',
		__DIR__ . '/include',
		__DIR__ . '/mod',
		__DIR__ . '/static',
		__DIR__ . '/bin',
		__DIR__ . '/addon',
		__DIR__ . '/view',
		__DIR__
	]);

	$parameters->set(Option::SKIP, [
		__DIR__ . '/view/asset',
	]);

	// here we can define, what sets of rules will be applied
	$parameters->set(Option::SETS, [
		DowngradeSetList::PHP_73,
		DowngradeSetList::PHP_72,
		DowngradeSetList::PHP_71
	]);

	// is your PHP version different from the one your refactor to? [default: your PHP version]
	$parameters->set(Option::PHP_VERSION_FEATURES, '7.0');
};
