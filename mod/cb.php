<?php

/**
 * General purpose landing page for plugins/addons
 */


function cb_init(&$a) {
	call_hooks('cb_init');
}

function cb_post(&$a) {
	call_hooks('cb_post', $_POST);
}

function cb_afterpost(&$a) {
	call_hooks('cb_afterpost');
}

function cb_content(&$a) {
	$o = '';
	call_hooks('cb_content', $o);
	return $o;
}