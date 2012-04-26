<?php

/*
* Name: Dispy Dark
* Description: Dispy Dark: Dark, Spartan, Sleek, and Functional
* Version: 1.2
* Author: Simon <http://simon.kisikew.org/>
* Maintainer: Simon <http://simon.kisikew.org/>
* Screenshot: <a href="screenshot.jpg">Screenshot</a>
*/

$a = get_app();
$a->theme_info = array(
    'family' => 'dispy',
    'name' => 'dark',
    'version' => '1.2'
);

function dispy_dark_init(&$a) {
    /** @purpose set some theme defaults
    */
    $cssFile = null;
    $colour = 'dark';
	$colour_path = "/dark/";

    // set css
    if (!is_null($cssFile)) {
        $a->page['htmlhead'] .= sprintf('<link rel="stylesheet" type="text/css" href="%s" />', $cssFile);
    }
}

