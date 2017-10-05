<?php

/**
 * example usage (web)
 *
 * @package Text_LanguageDetect
 * @version CVS: $Id$
 */

// browsers will encode multi-byte characters wrong unless they think the page is utf8-encoded
header('Content-type: text/html; charset=utf-8', true);

require_once 'Text/LanguageDetect.php';

$l = new Text_LanguageDetect;
if (isset($_REQUEST['q'])) {
    $q = stripslashes($_REQUEST['q']);
}

?>
<html>
<head>
<title>Text_LanguageDetect demonstration</title>
</head>
<body>
<h2>Text_LanguageDetect</h2>
<?
echo "<small>Supported languages:\n";
$langs = $l->getLanguages();
sort($langs);
foreach ($langs as $lang) {
    echo ucfirst($lang), ', ';
    $i++;
}

echo "<br />total $i</small><br /><br />";

?>
<form method="post">
Enter text to identify language (at least a couple of sentences):<br />
<textarea name="q" wrap="virtual" cols="80" rows="8"><?= $q ?></textarea>
<br />
<input type="submit" value="Submit" />
</form>
<?
if (isset($q) && strlen($q)) {
    $len = $l->utf8strlen($q);
    if ($len < 20) { // this value picked somewhat arbitrarily
        echo "Warning: string not very long ($len chars)<br />\n";
    }

    $result = $l->detectConfidence($q);

    if ($result == null) {
        echo "Text_LanguageDetect cannot identify this piece of text. <br /><br />\n";
    } else {
        echo "Text_LanguageDetect thinks this text is written in <b>{$result['language']}</b> ({$result['similarity']}, {$result['confidence']})<br /><br />\n";
    }

    $result = $l->detectUnicodeBlocks($q, false);
    if (!empty($result)) {
        arsort($result);
        echo "Unicode blocks present: ", join(', ', array_keys($result)), "\n<br /><br />";
    }
}

unset($l);

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

?>
</body></html>
