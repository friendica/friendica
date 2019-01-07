<?php
/**
 * @file mod/smilies.php
 */
use Friendica\App;
use Friendica\Content\Smilies;
use Friendica\Core\System;

/**
 * @param App $a App
 * @return string
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function smilies_content(App $a)
{
	$smilies = Smilies::getList();
	if (!empty($a->argv[1]) && ($a->argv[1] === "json")) {
		$results = [];
		for ($i = 0; $i < count($smilies['texts']); $i++) {
			$results[] = ['text' => $smilies['texts'][$i], 'icon' => $smilies['icons'][$i]];
		}
		System::jsonExit($results);
	} else {
		$s = '<div class="smiley-sample">';
		for ($x = 0; $x < count($smilies['texts']); $x ++) {
			$s .= '<dl><dt>' . $smilies['texts'][$x] . '</dt><dd>' . $smilies['icons'][$x] . '</dd></dl>';
		}
		$s .= '</div>';

		return $s;
	}
}
