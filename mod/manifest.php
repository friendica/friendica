<?php

use Friendica\App;
use Friendica\Content\Text;
use Friendica\Core\System;
use Friendica\Core\Config;

function manifest_content(App $a) {

	$tpl = get_markup_template('manifest.tpl');

	header('Content-type: application/manifest+json');

	$touch_icon = Config::get('system', 'touch_icon', 'images/friendica-128.png');
	if ($touch_icon == '') {
		$touch_icon = 'images/friendica-128.png';
	}

	$o = Text::replaceMacros($tpl, [
		'$baseurl' => System::baseUrl(),
		'$touch_icon' => $touch_icon,
		'$title' => Config::get('config', 'sitename', 'Friendica'),
	]);

	echo $o;

	killme();
}
