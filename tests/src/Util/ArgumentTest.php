<?php

namespace Friendica\Test\src\Util;

use Friendica\Util\Argument;
use PHPUnit\Framework\TestCase;

class ArgumentTest extends TestCase
{
	/**
	 * @small
	 */
	function testValidParameter() {
		$args = ['--test', 'temp1', '--test2', 'temp2'];

		$value = Argument::get('test', $args);

		$this->assertEquals($args[1], $value);
	}

	/**
	 * @small
	 */
	function testInvalidParameter() {
		$args = ['--test', 'temp1', '--test2', 'temp2'];

		$value = Argument::get('not', $args);

		$this->assertEquals('', $value);
	}

	/**
	 * @small
	 */
	function testBooleanParameterAtMiddle() {
		$args = ['--test', '--value'];

		$value = Argument::get('test', $args);

		$this->assertEquals(true, $value);
	}

	/**
	 * @small
	 */
	function testBooleanParameterAtEnd() {
		$args = ['--test'];

		$value = Argument::get('test', $args);

		$this->assertEquals(true, $value);
	}

	/**
	 * @small
	 * @expectedException Friendica\Network\HTTPException\InternalServerErrorException
	 */
	function testInvalidType() {
		$args = ['--test', '12fs'];

		$value = Argument::get('test', $args, '', 'int');
	}

	/**
	 * @small
	 */
	function testTypeSafe() {
		$args = ['--test', '12'];
		$value = Argument::get('test', $args, '', 'integer');
		$this->assertEquals(12, $value);

		$args = ['--test', 'true'];
		$value = Argument::get('test', $args, '', 'boolean');
		$this->assertEquals(true, $value);

		$args = ['--test', 'testit'];
		$value = Argument::get('test', $args, '', 'string');
		$this->assertEquals('testit', $value);
	}

	/**
	 * @small
	 */
	function testSetArgument() {
		$key = 'key';
		$value = 'value';

		Argument::set($args, $key, $value);

		$this->assertEquals(' --key value', $args);
	}

	/**
	 * @small
	 */
	function testSetArguments() {
		$args = [ 'key' => 'value', 'key2' => true, 'key3' => false, 'key4' => 12 ];

		Argument::setArgs($testArg, $args);

		$this->assertEquals(' --key value --key2 --key4 12', $testArg);
	}

	/**
	 * @small
	 */
	function testAddArguments() {
		$args = [ 'key' => 'value' ];

		$cmdLineTest = '/usr/bin/php execute.php';

		Argument::setArgs($cmdLineTest, $args);

		$this->assertEquals('/usr/bin/php execute.php --key value', $cmdLineTest);
	}
}
