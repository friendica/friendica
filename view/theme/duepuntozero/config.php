<?php
/**
 * Theme settings
 */



function theme_content(&$a)
{
    if (!local_user()) {
        return;
    }

    $colorset = get_pconfig(local_user(), 'duepuntozero', 'colorset');
    $user = true;

    return clean_form($a, $colorset, $user);
}

function theme_post(&$a)
{
    if (! local_user()) {
        return;
    }
    
    if (isset($_POST['duepuntozero-settings-submit'])) {
        set_pconfig(local_user(), 'duepuntozero', 'colorset', $_POST['duepuntozero_colorset']);
    }
}


function theme_admin(&$a)
{
    $colorset = get_config('duepuntozero', 'colorset');
    $user = false;

    return clean_form($a, $colorset, $user);
}

function theme_admin_post(&$a)
{
    if (isset($_POST['duepuntozero-settings-submit'])) {
        set_config('duepuntozero', 'colorset', $_POST['duepuntozero_colorset']);
    }
}


function clean_form(&$a, &$colorset, $user)
{
    $colorset = array(
    'default'=>t('default'),
        'greenzero'=>t('greenzero'),
        'purplezero'=>t('purplezero'),
        'easterbunny'=>t('easterbunny'),
        'darkzero'=>t('darkzero'),
        'comix'=>t('comix'),
        'slackr'=>t('slackr'),
    );
    if ($user) {
        $color = get_pconfig(local_user(), 'duepuntozero', 'colorset');
    } else {
        $color = get_config('duepuntozero', 'colorset');
    }
    $t = get_markup_template("theme_settings.tpl");
    $o .= replace_macros($t, array(
        '$submit' => t('Submit'),
        '$baseurl' => $a->get_baseurl(),
        '$title' => t("Theme settings"),
        '$colorset' => array('duepuntozero_colorset', t('Variations'), $color, '', $colorset),
    ));
    return $o;
}
