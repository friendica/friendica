<?php

// See update_profile.php for documentation

require_once('mod/network.php');
require_once('include/group.php');

function update_network_content(&$a) {

	$profile_uid = intval($_GET['p']);

	header("Content-type: text/html");
	echo "<!DOCTYPE html><html><body>\r\n";
	echo (($_GET['msie'] == 1) ? '<div>' : '<section>');


        $text = network_content($a,$profile_uid);
        $pattern = "/<img([^>]*) src=\"([^\"]*)\"/";
        $replace = "<img\${1} dst=\"\${2}\"";
        $text = preg_replace($pattern, $replace, $text);

		$replace = '<br />' . t('[Embedded content - reload page to view]') . '<br />';
        $pattern = "/<\s*audio[^>]*>(.*?)<\s*\/\s*audio>/i";
        $text = preg_replace($pattern, $replace, $text);
        $pattern = "/<\s*video[^>]*>(.*?)<\s*\/\s*video>/i";
        $text = preg_replace($pattern, $replace, $text);
        $pattern = "/<\s*embed[^>]*>(.*?)<\s*\/\s*embed>/i";
        $text = preg_replace($pattern, $replace, $text);
        $pattern = "/<\s*iframe[^>]*>(.*?)<\s*\/\s*iframe>/i";
        $text = preg_replace($pattern, $replace, $text);


        echo str_replace("\t",'       ',$text);
	echo (($_GET['msie'] == 1) ? '</div>' : '</section>');
	echo "</body></html>\r\n";
	killme();

}