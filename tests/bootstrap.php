<?php
/**
 * This file is loaded by PHPUnit before any test.
 */

use PHPUnit\Framework\TestCase;

// Backward compatibility
if (!class_exists(TestCase::class)) {
	class_alias(PHPUnit_Framework_TestCase::class, TestCase::class);
}
