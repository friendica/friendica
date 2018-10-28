<?php
/**
 * @file view/theme/vier/style.php
 */
use Friendica\Content\Text;
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Model\Profile;

$uid = Profile::getThemeUid();

$style = PConfig::get($uid, 'vier', 'style');

if (empty($style)) {
	$style = Config::get('vier', 'style');
}

if (empty($style)) {
	$style = "plus";
}

$stylecss = '';
$modified = '';

foreach (['style', $style] as $file) {
	$stylecssfile = $THEMEPATH . DIRECTORY_SEPARATOR . $file .'.css';
	if (file_exists($stylecssfile)) {
		$stylecss .= file_get_contents($stylecssfile);
		$stylemodified = filemtime($stylecssfile);
		if ($stylemodified > $modified) {
			$modified = $stylemodified;
		}
	} else {
		//TODO: use LOGGER_ERROR?
		Text::logger('Error: missing file: "' . $stylecssfile .'" (userid: '. $uid .')');
	}
}
$modified = gmdate('r', $modified);

$etag = md5($stylecss);

// Only send the CSS file if it was changed
header('Cache-Control: public');
header('ETag: "'.$etag.'"');
header('Last-Modified: '.$modified);

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
	$cached_modified = gmdate('r', strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']));
	$cached_etag = str_replace(['"', "-gzip"], ['', ''],
				stripslashes($_SERVER['HTTP_IF_NONE_MATCH']));

	if (($cached_modified == $modified) && ($cached_etag == $etag)) {
		header('HTTP/1.1 304 Not Modified');
		exit();
	}
}
echo $stylecss;
