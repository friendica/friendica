<?php
/**
 * Demonstrates how to use ISO language codes.
 *
 * The "name mode" changes the way languages are accepted and returned.
 */ 
require_once 'Text/LanguageDetect.php';
$ld = new Text_LanguageDetect();

//will output the ISO 639-1 two-letter language code
// "de"
$ld->setNameMode(2);
echo $ld->detectSimple('Das ist ein kleiner Text') . "\n";

//will output the ISO 639-2 three-letter language code
// "deu"
$ld->setNameMode(3);
echo $ld->detectSimple('Das ist ein kleiner Text') . "\n";
?>
