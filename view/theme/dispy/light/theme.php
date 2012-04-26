<?php

/*
 * Name: Dispy
 * Description: <p style="white-space:pre;">            Dispy: Light, Spartan, Sleek, and Functional<br />            Dispy Dark: Dark, Spartan, Sleek, and Functional</p>
 * Version: 1.2
 * Author: Simon <http://simon.kisikew.org/>
 * Maintainer: Simon <http://simon.kisikew.org/>
 * Screenshot: <a href="screenshot.jpg">Screenshot</a>
 */

$a = get_app();
$a->theme_info = array(
    'family' => 'dispy',
	'name' => 'light',
	'version' => '1.2'
);

function dispy_light_init(&$a) {

    /** @purpose set some theme defaults
    */
    $cssFile = null;
    $colour = false;
    $colour = 'light';

    // custom css
    if (!is_null($cssFile)) {
        $a->page['htmlhead'] .= sprintf('<link rel="stylesheet" type="text/css" href="%s" />', $cssFile);
    }
}

