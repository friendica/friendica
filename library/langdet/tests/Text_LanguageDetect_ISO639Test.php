<?php
set_include_path(
    __DIR__ . '/../' . PATH_SEPARATOR . get_include_path()
);

require_once 'Text/LanguageDetect/ISO639.php';

class Text_LanguageDetect_ISO639Test extends PHPUnit_Framework_TestCase
{
    public function testNameToCode2()
    {
        $this->assertEquals(
            'de', 
            Text_LanguageDetect_ISO639::nameToCode2('german')
        );
    }

    public function testNameToCode2Fail()
    {
        $this->assertNull(
            Text_LanguageDetect_ISO639::nameToCode2('doesnotexist')
        );
    }

    public function testNameToCode3()
    {
        $this->assertEquals(
            'fra', 
            Text_LanguageDetect_ISO639::nameToCode3('french')
        );
    }

    public function testNameToCode3Fail()
    {
        $this->assertNull(
            Text_LanguageDetect_ISO639::nameToCode3('doesnotexist')
        );
    }

    public function testCode2ToName()
    {
        $this->assertEquals(
            'english', 
            Text_LanguageDetect_ISO639::code2ToName('en')
        );
    }

    public function testCode2ToNameFail()
    {
        $this->assertNull(
            Text_LanguageDetect_ISO639::code2ToName('nx')
        );
    }

    public function testCode3ToName()
    {
        $this->assertEquals(
            'romanian', 
            Text_LanguageDetect_ISO639::code3ToName('rom')
        );
    }

    public function testCode3ToNameFail()
    {
        $this->assertNull(
            Text_LanguageDetect_ISO639::code3ToName('nxx')
        );
    }

}

?>