<?php
require_once 'Text/LanguageDetect.php';

$text = 'Was wäre, wenn ich Ihnen das jetzt sagen würde?';

$ld = new Text_LanguageDetect();
//3 most probable languages
$results = $ld->detect($text, 3);

foreach ($results as $language => $confidence) {
    echo $language . ': ' . number_format($confidence, 2) . "\n";
}

//output:
//german: 0.35
//dutch: 0.25
//swedish: 0.20
?>