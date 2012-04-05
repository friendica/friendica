<?php

require_once('mod/friendica.php');

function friendika_init(&$a) {
	friendica_init($a);
}

function friendika_content(&$a) {
	return friendica_content($a);
}
