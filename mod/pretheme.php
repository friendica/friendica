<?php
/**
 * @file mod/pretheme.php
 */
use Friendica\App;
use Friendica\Core\Addon;

function pretheme_init(App $a) {

	if ($_REQUEST['theme']) {
		$theme = $_REQUEST['theme'];
		$info = Addon::getThemeInfo($theme);
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
		echo json_encode(['img' => Addon::getThemeScreenshot($theme), 'desc' => $desc, 'version' => $version, 'credits' => $credits]);
	}

	killme();
}
