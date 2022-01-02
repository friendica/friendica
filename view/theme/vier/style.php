<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

use Friendica\Core\Logger;
use Friendica\DI;
use Friendica\Network\HTTPException\NotModifiedException;

$uid = $_REQUEST['puid'] ?? 0;

$style = DI::pConfig()->get($uid, 'vier', 'style');

if (empty($style)) {
	$style = DI::config()->get('vier', 'style');
}

if (empty($style)) {
	$style = "plus";
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
		//TODO: use Logger::ERROR?
		Logger::notice('Error: missing file: "' . $stylecssfile .'" (userid: '. $uid .')');
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
	$cached_etag = str_replace(['"', "-gzip"], ['', ''],
				stripslashes($_SERVER['HTTP_IF_NONE_MATCH']));

	if (($cached_modified == $modified) && ($cached_etag == $etag)) {
		throw new NotModifiedException();
	}
}
echo $stylecss;
