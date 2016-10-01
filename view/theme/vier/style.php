<?php
$uid = get_theme_uid();

$style = get_pconfig($uid, 'vier', 'style');

if ($style == "") {
    $style = get_config('vier', 'style');
}

if ($style == "") {
    $style = "plus";
}

if ($style == "flat") {
    $stylecssfile = 'view/theme/vier/flat.css';
} elseif ($style == "netcolour") {
    $stylecssfile = 'view/theme/vier/netcolour.css';
} elseif ($style == "breathe") {
    $stylecssfile = 'view/theme/vier/breathe.css';
} elseif ($style == "plus") {
    $stylecssfile = 'view/theme/vier/plus.css';
} elseif ($style == "dark") {
    $stylecssfile = 'view/theme/vier/dark.css';
}

if (file_exists($THEMEPATH."//style.css")) {
    $stylecss = file_get_contents($THEMEPATH."//style.css")."\n";
    $modified = filemtime($THEMEPATH."//style.css");
}

$stylemodified = filemtime($stylecssfile);
$stylecss .= file_get_contents($stylecssfile);

if ($stylemodified > $modified) {
    $modified = $stylemodified;
}

$modified = gmdate('r', $modified);

$etag = md5($stylecss);

// Only send the CSS file if it was changed
header('Cache-Control: public');
header('ETag: "'.$etag.'"');
header('Last-Modified: '.$modified);

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
    $cached_modified = gmdate('r', strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']));
    $cached_etag = str_replace(array('"', "-gzip"), array('', ''),
                stripslashes($_SERVER['HTTP_IF_NONE_MATCH']));

    if (($cached_modified == $modified) and ($cached_etag == $etag)) {
        header('HTTP/1.1 304 Not Modified');
        exit();
    }
}
echo $stylecss;
