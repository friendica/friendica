<?php
/**
 * This file is loaded by PHPUnit before any test.
 */

use Friendica\App;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../boot.php';
require_once __DIR__.'/../include/api.php';

new App(dirname(__DIR__));

\Friendica\Core\Config::set('system', 'url', 'http://localhost');
\Friendica\Core\Config::set('system', 'hostname', 'localhost');
\Friendica\Core\Config::set('system', 'worker_dont_fork', true);

// Backward compatibility
if (!class_exists(TestCase::class)) {
	class_alias(PHPUnit_Framework_TestCase::class, TestCase::class);
}
if (!trait_exists(TestCaseTrait::class)) {
	class_alias(PHPUnit_Extensions_Database_TestCase_Trait::class, TestCaseTrait::class);
}
if (!class_exists(YamlDataSet::class)) {
	class_alias(PHPUnit_Extensions_Database_DataSet_YamlDataSet::class, YamlDataSet::class);
}
