<?php

/**
 * example usage (CLI)
 *
 * @package Text_LanguageDetect
 * @version CVS: $Id$
 */

require_once 'Text/LanguageDetect.php';

$l = new Text_LanguageDetect;

$stdin = fopen('php://stdin', 'r');

echo "Supported languages:\n";
$langs = $l->getLanguages();
sort($langs);
echo join(', ', $langs);

echo "\ntotal ", count($langs), "\n\n";

while ($line = fgets($stdin)) {
    $result = $l->detect($line, 4);
    print_r($result);
    $blocks = $l->detectUnicodeBlocks($line, true);
    print_r($blocks);
}

fclose($stdin);
unset($l);

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

?>
