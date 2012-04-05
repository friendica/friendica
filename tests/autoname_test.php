<?php
/**
 * this file contains tests for the autoname function
 * 
 * @package test.util
 */

/** required, it is the file under test */
require_once('include/text.php');

/**
 * TestCase for the autoname function
 * 
 * @author Alexander Kampmann
 * @package test.util
 */
class AutonameTest extends PHPUnit_Framework_TestCase {
	/**
	 *autonames should be random, even length
	 */
	public function testAutonameEven() {
		$autoname1=autoname(10);
		$autoname2=autoname(10);
	
		$this->assertNotEquals($autoname1, $autoname2);
	}
	
	/**
	 *autonames should be random, odd length
	 */
	public function testAutonameOdd() {
		$autoname1=autoname(9);
		$autoname2=autoname(9);
	
		$this->assertNotEquals($autoname1, $autoname2);
	}
	
	/**
	 * try to fail autonames
	 */
	public function testAutonameNoLength() {
		$autoname1=autoname(0);
		$this->assertEquals(0, count($autoname1));
	}
	
	/**
	 * try to fail it with invalid input
	 * 
	 * TODO: What's corect behaviour here? An exception?
	 */
	public function testAutonameNegativeLength() {
		$autoname1=autoname(-23);
		$this->assertEquals(0, count($autoname1));
	}
	
	// 	public function testAutonameMaxLength() {
	// 		$autoname2=autoname(PHP_INT_MAX);
	// 		$this->assertEquals(PHP_INT_MAX, count($autoname2));
	// 	}
	
	/**
	 * test with a length, that may be too short
	 */
	public function testAutonameLength1() {
		$autoname1=autoname(1);
		$this->assertEquals(1, count($autoname1));
		
		$autoname2=autoname(1);
		$this->assertEquals(1, count($autoname2));
		
		$this->assertFalse($autoname1==$autoname2); 
	}
}