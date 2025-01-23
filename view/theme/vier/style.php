<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 */

use Friendica\DI;
use Friendica\Network\HTTPException\NotModifiedException;

/*
 * This script can be included when the maintenance mode is on, which requires us to avoid any config call and
 * use the following hardcoded default
 */
$style = 'plus';

if (DI::mode()->has(\Friendica\App\Mode::MAINTENANCEDISABLED)) {
	$uid = $_REQUEST['puid'] ?? 0;

	$style = DI::pConfig()->get($uid, 'vier', 'style', DI::config()->get('vier', 'style', $style));
}

$stylecss = '';
$modified = '';

$style = \Friendica\Util\Strings::sanitizeFilePathItem($style);

foreach (['style', $style] as $file) {
	$stylecssfile = $THEMEPATH . DIRECTORY_SEPARATOR . $file .'.css';
	if (file_exists($stylecssfile)) {
		$stylecss .= file_get_contents($stylecssfile);
		$stylemodified = filemtime($stylecssfile);
		if ($stylemodified > $modified) {
			$modified = $stylemodified;
		}
	} else {
		DI::logger()->warning('Missing CSS file', ['file' => $stylecssfile, 'uid' => $uid]);
	}
}
$modified = gmdate('r', $modified);

$etag = md5($stylecss);

// Only send the CSS file if it was changed
header('Cache-Control: public');
header('ETag: "'.$etag.'"');
header('Last-Modified: '.$modified);

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
	$cached_modified = gmdate('r', strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']));
	$cached_etag     = str_replace(
		['"', "-gzip"],
		['', ''],
		stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])
	);

	if (($cached_modified == $modified) && ($cached_etag == $etag)) {
		throw new NotModifiedException();
	}
}
echo $stylecss;
