<?php
/**
 * @file mod/update_display.php
 * See update_profile.php for documentation
 */

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;

require_once "mod/display.php";

function update_display_content(App $a)
{
	$profile_uid = intval($_GET["p"]);

	header("Content-type: text/html");
	echo "<!DOCTYPE html><html><body>\r\n";
	echo "<section>";

	$text = display_content($a, true, $profile_uid);
	$pattern = "/<img([^>]*) src=\"([^\"]*)\"/";
	$replace = "<img\${1} dst=\"\${2}\"";
	$text = preg_replace($pattern, $replace, $text);

	if (PConfig::get(local_user(), "system", "bandwith_saver")) {
		$replace = "<br />" . L10n::t("[Embedded content - reload page to view]") . "<br />";
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
