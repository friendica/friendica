<?php
/**
 * List all supported languages
 */
require_once 'Text/LanguageDetect.php';
$ld = new Text_LanguageDetect();

foreach ($ld->getLanguages() as $lang) {
    echo $lang . "\n";
}
?>
