<?php

/**
 * @file mod/smilies.php
 */

require_once("include/Smilies.php");

function smilies_content(App &$a) {
	if ($a->argv[1]==="json"){
		$tmp = Smilies::get_list();
		$results = array();
		for($i = 0; $i < count($tmp['texts']); $i++) {
			$results[] = array('text' => $tmp['texts'][$i], 'icon' => $tmp['icons'][$i]);
		}
		json_return_and_die($results);
	}
	else {
		return Smilies::replace('',true);
	}
}
