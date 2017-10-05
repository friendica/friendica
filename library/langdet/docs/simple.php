<?php
require_once 'Text/LanguageDetect.php';

$text = 'Was wäre, wenn ich Ihnen das jetzt sagen würde?';

$ld = new Text_LanguageDetect();
$result = $ld->detectSimple($text);
var_dump($result);
//output: german
?>
