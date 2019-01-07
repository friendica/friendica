<?php

// See update_profile.php for documentation

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Module\Contact;

function update_contact_content(App $a)
{
	header("Content-type: text/html");
	echo "<!DOCTYPE html><html><body>\r\n";
	echo "<section>";

	if ($_GET["force"] == 1) {
		$text = Contact::content(true);
	} else {
		$text = '';
	}

	if (PConfig::get(local_user(), "system", "bandwidth_saver")) {
		$replace = "<br />".L10n::t("[Embedded content - reload page to view]")."<br />";
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
	exit();
}
