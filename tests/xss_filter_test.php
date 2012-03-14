<?php
/**
 * tests several functions which are used to prevent xss attacks
 * 
 * @package test.util
 */

require_once('include/text.php');

class AntiXSSTest extends PHPUnit_Framework_TestCase {

	/**
	 * test, that tags are escaped
	 */
	public function testEscapeTags() {
		$invalidstring='<submit type="button" onclick="alert(\'failed!\');" />';

		$validstring=notags($invalidstring);
		$escapedString=escape_tags($invalidstring);

		$this->assertEquals('[submit type="button" onclick="alert(\'failed!\');" /]', $validstring);
		$this->assertEquals("&lt;submit type=&quot;button&quot; onclick=&quot;alert('failed!');&quot; /&gt;", $escapedString);
	}

	/**
	 *xmlify and unxmlify
	 */
	public function testXmlify() {
		$text="<tag>I want to break\n this!11!<?hard?></tag>";
		$xml=xmlify($text); //test whether it actually may be part of a xml document
		$retext=unxmlify($text);

		$this->assertEquals($text, $retext);
	}

	/**
	 * test hex2bin and reverse
	 */
	public function testHex2Bin() {
		$this->assertEquals(-3, hex2bin(bin2hex(-3)));
		$this->assertEquals(0, hex2bin(bin2hex(0)));
		$this->assertEquals(12, hex2bin(bin2hex(12)));
		$this->assertEquals(PHP_INT_MAX, hex2bin(bin2hex(PHP_INT_MAX)));
	}

	//function qp, quick and dirty??
	//get_mentions
	//get_contact_block, bis Zeile 538
}
?>
