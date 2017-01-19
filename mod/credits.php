<?php
/**
 * Show a credits page for all the developers who helped with the project
 * (only contributors to the git repositories for friendica core and the
 * addons repository will be listed though ATM)
 */

function credits_content (App $a) {
    /* fill the page with credits */
    $f = fopen('util/credits.txt','r');
    $names = fread($f, filesize('util/credits.txt'));
    $arr = explode("\n", htmlspecialchars($names));
    fclose($f);
    $tpl = get_markup_template('credits.tpl');
    return replace_macros( $tpl, array(
       '$title'		=> t('Credits'),
       '$thanks'		=> t('Friendica is a community project, that would not be possible without the help of many people. Here is a list of those who have contributed to the code or the translation of Friendica. Thank you all!'),
       '$names'         => $arr,
    ));
}
