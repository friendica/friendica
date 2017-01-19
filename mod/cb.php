<?php

/**
 * General purpose landing page for plugins/addons
 */


function cb_init(App $a) {
	call_hooks('cb_init');
}

function cb_post(App $a) {
	call_hooks('cb_post', $_POST);
}

function cb_afterpost(App $a) {
	call_hooks('cb_afterpost');
}

function cb_content(App $a) {
	$o = '';
	call_hooks('cb_content', $o);
	return $o;
}