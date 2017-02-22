<?php

// See update_profile.php for documentation

require_once("mod/network.php");
require_once("include/group.php");

function update_network_content(App $a) {

	$profile_uid = intval($_GET["p"]);

	header("Content-type: text/html");
	echo "<!DOCTYPE html><html><body>\r\n";
	echo "<section>";

	if (!get_pconfig($profile_uid, "system", "no_auto_update") OR ($_GET["force"] == 1)) {
		$text = network_content($a, $profile_uid);
	} else {
		$text = "";
	}

	$pattern = "/<img([^>]*) src=\"([^\"]*)\"/";
	$replace = "<img\${1} dst=\"\${2}\"";
	$text = preg_replace($pattern, $replace, $text);

	if (get_pconfig(local_user(), "system", "bandwith_saver")) {
		$replace = "<br />".t("[Embedded content - reload page to view]")."<br />";
		$pattern = "/<\s*audio[^>]*>(.*?)<\s*\/\s*audio>/i";
		$text = preg_replace($pattern, $replace, $text);
		$pattern = "/<\s*video[^>]*>(.*?)<\s*\/\s*video>/i";
		$text = preg_replace($pattern, $replace, $text);
		$pattern = "/<\s*embed[^>]*>(.*?)<\s*\/\s*embed>/i";
		$text = preg_replace($pattern, $replace, $text);
		$pattern = "/<\s*iframe[^>]*>(.*?)<\s*\/\s*iframe>/i";
		$text = preg_replace($pattern, $replace, $text);
	}

	echo str_replace("\t", "       ", $text);
	echo "</section>";
	echo "</body></html>\r\n";
	killme();
}
