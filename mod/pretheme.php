<?php

use Friendica\App;
use Friendica\Core\Theme;

function pretheme_init(App $a) {

	if ($_REQUEST['theme']) {
		$theme = $_REQUEST['theme'];
		$info = Theme::getInfo($theme);
		if ($info) {
			// unfortunately there will be no translation for this string
			$desc = $info['description'];
			$version = $info['version'];
			$credits = $info['credits'];
		} else {
			$desc = '';
			$version = '';
			$credits = '';
		}
		echo json_encode(['img' => Theme::getScreenshot($theme), 'desc' => $desc, 'version' => $version, 'credits' => $credits]);
	}

	killme();
}
