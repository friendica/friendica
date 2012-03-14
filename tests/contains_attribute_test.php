<?php
/**
 * this test tests the contains_attribute function
 *
 * @package test.util
 */

/** required, it is the file under test */
require_once('include/text.php');

/**
 * TestCase for the contains_attribute function
 *
 * @author Alexander Kampmann
 * @package test.util
 */
class ContainsAttributeTest extends PHPUnit_Framework_TestCase {
	/**
	 * test attribute contains
	 */
	public function testAttributeContains1() {
		$testAttr="class1 notclass2 class3";
		$this->assertTrue(attribute_contains($testAttr, "class3"));
		$this->assertFalse(attribute_contains($testAttr, "class2"));
	}
	
	/**
	 * test attribute contains
	 */
	public function testAttributeContains2() {
		$testAttr="class1 not-class2 class3";
		$this->assertTrue(attribute_contains($testAttr, "class3"));
		$this->assertFalse(attribute_contains($testAttr, "class2"));
	}
	
	/**
	 * test with empty input
	 */
	public function testAttributeContainsEmpty() {
		$testAttr="";
		$this->assertFalse(attribute_contains($testAttr, "class2"));
	}
	
	/**
	 * test input with special chars
	 */
	public function testAttributeContainsSpecialChars() {
		$testAttr="--... %\$ä() /(=?}";
		$this->assertFalse(attribute_contains($testAttr, "class2"));
	}
}