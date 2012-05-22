<?php

/*
 * Name: Dispy Light
 * Description: Dispy Light: Light, Spartan, Sleek, and Functional
 * Version: 1.2.1
 * Author: Simon <http://simon.kisikew.org/>
 * Maintainer: Simon <http://simon.kisikew.org/>
 * Screenshot: <a href="screenshot.jpg">Screenshot</a>
 */

$a = get_app();
$a->theme_info = array(
    'family' => 'dispy',
	'name' => 'light',
	'version' => '1.2.1'
);

function dispy_light_init(&$a) {

    /** @purpose set some theme defaults
    */
    $cssFile = null;
    $colour = 'light';
	$colour_path = "/light/";

    // set css
    if (!is_null($cssFile)) {
        $a->page['htmlhead'] .= sprintf('<link rel="stylesheet" type="text/css" href="%s" />', $cssFile);
    }
}

