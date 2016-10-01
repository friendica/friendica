<?php


/**
 * @brief: Get info header of the shema
 *
 * This function parses the header of the shemename.php file for inormations like
 * Author, Description and Overwrites. Most of the code comes from the get_plugin_info()
 * function. We use this to get the variables which get overwritten through the shema.
 * All color variables which get overwritten through the theme have to be
 * listed (comma seperated) in the shema header under Overwrites:
 * This seemst not to be the best solution. We need to investigate further.
 *
 * @param string $schema Name of the shema
 * @return array With theme information
 *    'author' => Author Name
 *    'description' => Schema description
 *    'version' => Schema version
 *    'overwrites' => Variables which overwriting custom settings
 */
function get_schema_info($schema)
{
    $theme = current_theme();
    $themepath = "view/theme/" . $theme . "/";
    $schema = get_pconfig(local_user(), 'frio', 'schema');

    $info=array(
        'name' => $schema,
        'description' => "",
        'author' => array(),
        'version' => "",
        'overwrites' => ""
    );

    if (!is_file($themepath . "schema/" . $schema . ".php")) {
        return $info;
    }

    $f = file_get_contents($themepath . "schema/" . $schema . ".php");

    $r = preg_match("|/\*.*\*/|msU", $f, $m);

    if ($r) {
        $ll = explode("\n", $m[0]);
        foreach ($ll as $l) {
            $l = trim($l, "\t\n\r */");
            if ($l!="") {
                list($k, $v) = array_map("trim", explode(":", $l, 2));
                $k= strtolower($k);
                if ($k=="author") {
                    $r=preg_match("|([^<]+)<([^>]+)>|", $v, $m);
                    if ($r) {
                        $info['author'][] = array('name'=>$m[1], 'link'=>$m[2]);
                    } else {
                        $info['author'][] = array('name'=>$v);
                    }
                } elseif ($k == "overwrites") {
                    $theme_settings = explode(',', str_replace(' ', '', $v));
                    foreach ($theme_settings as $key => $value) {
                        $info["overwrites"][$value] = true;
                    }
                } else {
                    if (array_key_exists($k, $info)) {
                        $info[$k]=$v;
                    }
                }
            }
        }
    }
    return $info;
}
