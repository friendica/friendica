<?php
/**
 * How to handle errors
 */
require_once 'Text/LanguageDetect.php';
require_once 'Text/LanguageDetect/Exception.php';

try {
    $ld = new Text_LanguageDetect();
    $lang = $ld->detectSimple('Das ist ein kleiner Text');
    echo "Language is: $lang\n";
} catch (Text_LanguageDetect_Exception $e) {
    echo 'An error occured! Message: ' . $e . "\n";
}
?>