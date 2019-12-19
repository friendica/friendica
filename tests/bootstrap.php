<?php
/**
 * This file is loaded by PHPUnit before any test.
 */

use Dice\Dice;
use Friendica\DI;
use PHPUnit\Framework\TestCase;

// Backward compatibility
if (!class_exists(TestCase::class)) {
	class_alias(PHPUnit_Framework_TestCase::class, TestCase::class);
}

$dice = new Dice();
$dice = $dice->addRules(include  __DIR__ . '/../static/dependencies.config.php');

DI::init($dice);
