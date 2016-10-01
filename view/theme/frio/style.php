<?php
require_once('view/theme/frio/php/PHPColors/Color.php');


if (! $a->install) {
    // Get the UID of the channel owner
    $uid = get_theme_uid();
    if ($uid) {
        load_pconfig($uid, 'frio');
    }
    // Load the owners pconfig
    $schema        = get_pconfig($uid, "frio", "schema");
    $nav_bg        = get_pconfig($uid, "frio", "nav_bg");
    $nav_icon_color = get_pconfig($uid, "frio", "nav_icon_color");
    $link_color    = get_pconfig($uid, "frio", "link_color");
    $bgcolor    = get_pconfig($uid, "frio", "background_color");
    $contentbg_transp = get_pconfig($uid, "frio", "contentbg_transp");
    $background_image = get_pconfig($uid, "frio", "background_image");
    $bg_image_option = get_pconfig($uid, "frio", "bg_image_option");
}

// Now load the scheme.  If a value is changed above, we'll keep the settings
// If not, we'll keep those defined by the schema
// Setting $schema to '' wasn't working for some reason, so we'll check it's
// not --- like the mobile theme does instead.
// Allow layouts to over-ride the schema
if ($_REQUEST['schema']) {
    $schema = $_REQUEST['schema'];
}
if (($schema) && ($schema != '---')) {
    // Check it exists, because this setting gets distributed to clones
    if (file_exists('view/theme/frio/schema/' . $schema . '.php')) {
        $schemefile = 'view/theme/frio/schema/' . $schema . '.php';
        require_once($schemefile);
    }
    if (file_exists('view/theme/frio/schema/' . $schema . '.css')) {
        $schemecss = file_get_contents('view/theme/frio/schema/' . $schema . '.css');
    }
}

// If we haven't got a schema, load the default.  We shouldn't touch this - we
// should leave it for admins to define for themselves.
// default.php and default.css MUST be symlinks to existing schema files.
if (! $schema) {
    if (file_exists('view/theme/frio/schema/default.php')) {
        $schemefile = 'view/theme/frio/schema/default.php';
        require_once($schemefile);
    }
    if (file_exists('view/theme/frio/schema/default.css')) {
        $schemecss = file_get_contents('view/theme/frio/schema/default.css');
    }
}

//Set some defaults - we have to do this after pulling owner settings, and we have to check for each setting
//individually.  If we don't, we'll have problems if a user has set one, but not all options.
if (! $nav_bg) {
    $nav_bg = "#708fa0";
}
if (! $nav_icon_color) {
    $nav_icon_color = "#fff";
}
if (! $link_color) {
    $link_color = "#6fdbe8";
}
if (! $bgcolor) {
    $bgcolor = "#ededed";
}
if (! $contentbg_transp) {
    $contentbg_transp = 100;
}
if (! $background_image) {
    $background_image ='';
}

// Calculate some colors in dependance of existing colors
// Some colors are calculated to don't have too many selection
// fields in the theme settings
if (! $menu_background_hover_color) {
    $mbhc = new Color($nav_bg);
    $mcolor = $mbhc->getHex();

    if ($mbhc->isLight($mcolor, 75)) {
        $menu_is = 'light';
        $menu_background_hover_color = '#' . $mbhc->darken(5);
    } else {
        $menu_is = 'dark';
        $menu_background_hover_color = '#' . $mbhc->lighten(5);
    //$menu_background_hover_color = "#628394";
    }
}
if (! $nav_icon_hover_color) {
    $nihc = new Color($nav_bg);

    if ($nihc->isLight()) {
        $nav_icon_hover_color = '#' . $nihc->darken(10);
    } else {
        $nav_icon_hover_color = '#' . $nihc->lighten(10);
    }
}
if (! $link_hover_color) {
    $lhc = new Color($link_color);
    $lcolor = $lhc->getHex();

    if ($lhc->isLight($lcolor, 75)) {
        $link_hover_color = '#' . $lhc->darken(5);
    } else {
        $link_hover_color = '#' . $lhc->lighten(5);
    }
}

// Convert $bg_image_options into css
switch ($bg_image_option) {
    case "stretch":
        $background_size_img = "100%";
        break;
    case "cover":
        $background_size_img ="cover";
        break;
    case "repeat":
        $background_size_img = "auto";
        break;
    case "contain":
        $background_size_img = "contain";
        break;

    default:
        $background_size_img = "auto";
        break;
}

// Convert transparency level from percentage to opacity value
$contentbg_transp = $contentbg_transp / 100;


// Apply the settings
if (file_exists('view/theme/frio/css/style.css')) {
    $x = file_get_contents('view/theme/frio/css/style.css');

    $options = array(
        '$nav_bg'            => $nav_bg,
        '$nav_icon_color'        => $nav_icon_color,
        '$nav_icon_hover_color'        => $nav_icon_hover_color,
        '$link_color'            => $link_color,
        '$link_hover_color'        => $link_hover_color,
        '$menu_background_hover_color'    => $menu_background_hover_color,
        '$btn_primary_color'        => $nav_icon_color,
        '$btn_primary_hover_color'    => $menu_background_hover_color,
        '$bgcolor'            => $bgcolor,
        '$contentbg_transp'        => $contentbg_transp,
        '$background_image'        => $background_image,
        '$background_size_img'        => $background_size_img,
    );

    echo str_replace(array_keys($options), array_values($options), $x);
}

if ($schemecss) {
    echo $schemecss;
}
