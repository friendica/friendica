<?php
/**
 * this test tests the expand_acl function
 *
 * @package test.util
 */

/** required, it is the file under test */
require_once('include/text.php');

/**
 * TestCase for the expand_acl function
 *
 * @author Alexander Kampmann
 * @package test.util
 */
class ExpandAclTest extends PHPUnit_Framework_TestCase
{
    
    /**
     * test expand_acl, perfect input
     */
    public function testExpandAclNormal()
    {
        $text='<1><2><3>';
        $this->assertEquals(array(1, 2, 3), expand_acl($text));
    }
    
    /**
     * test with a big number
     */
    public function testExpandAclBigNumber()
    {
        $text='<1><'.PHP_INT_MAX.'><15>';
        $this->assertEquals(array(1, PHP_INT_MAX, 15), expand_acl($text));
    }
    
    /**
     * test with a string in it.
     *
     * TODO: is this valid input? Otherwise: should there be an exception?
     */
    public function testExpandAclString()
    {
        $text="<1><279012><tt>";
        $this->assertEquals(array(1, 279012), expand_acl($text));
    }
    
    /**
     * test with a ' ' in it.
     *
     * TODO: is this valid input? Otherwise: should there be an exception?
     */
    public function testExpandAclSpace()
    {
        $text="<1><279 012><32>";
        $this->assertEquals(array(1, "279", "32"), expand_acl($text));
    }
    
    /**
     * test empty input
     */
    public function testExpandAclEmpty()
    {
        $text="";
        $this->assertEquals(array(), expand_acl($text));
    }
    
    /**
     * test invalid input, no < at all
     *
     * TODO: should there be an exception?
     */
    public function testExpandAclNoBrackets()
    {
        $text="According to documentation, that's invalid. "; //should be invalid
        $this->assertEquals(array(), expand_acl($text));
    }
    
    /**
     * test invalid input, just open <
     *
     * TODO: should there be an exception?
     */
    public function testExpandAclJustOneBracket1()
    {
        $text="<Another invalid string"; //should be invalid
        $this->assertEquals(array(), expand_acl($text));
    }
    
    /**
     * test invalid input, just close >
     *
     * TODO: should there be an exception?
     */
    public function testExpandAclJustOneBracket2()
    {
        $text="Another invalid> string"; //should be invalid
        $this->assertEquals(array(), expand_acl($text));
    }
    
    /**
     * test invalid input, just close >
     *
     * TODO: should there be an exception?
     */
    public function testExpandAclCloseOnly()
    {
        $text="Another> invalid> string>"; //should be invalid
        $this->assertEquals(array(), expand_acl($text));
    }
    
    /**
     * test invalid input, just open <
     *
     * TODO: should there be an exception?
     */
    public function testExpandAclOpenOnly()
    {
        $text="<Another< invalid string<"; //should be invalid
        $this->assertEquals(array(), expand_acl($text));
    }
    
    /**
     * test invalid input, open and close do not match
     *
     * TODO: should there be an exception?
     */
    public function testExpandAclNoMatching1()
    {
        $text="<Another<> invalid <string>"; //should be invalid
        $this->assertEquals(array(), expand_acl($text));
    }
    
    /**
     * test invalid input, open and close do not match
     *
     * TODO: should there be an exception?
     */
    public function testExpandAclNoMatching2()
    {
        $text="<1>2><3>";
// The angles are delimiters which aren't important
// the important thing is the numeric content, this returns array(1,2,3) currently
// we may wish to eliminate 2 from the results, though it isn't harmful
// It would be a better test to figure out if there is any ACL input which can
// produce this $text and fix that instead.
//		$this->assertEquals(array(), expand_acl($text));
    }
    
    /**
     * test invalid input, empty <>
     *
     * TODO: should there be an exception? Or array(1, 3)
     * (This should be array(1,3) - mike)
     */
    public function testExpandAclEmptyMatch()
    {
        $text="<1><><3>";
        $this->assertEquals(array(1, 3), expand_acl($text));
    }
}
