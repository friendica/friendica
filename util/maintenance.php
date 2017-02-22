<?php

use \Friendica\Core\Config;

require_once("boot.php");

$a = new App;
@include(".htconfig.php");

$lang = get_browser_language();
load_translation_table($lang);

require_once("include/dba.php");
$db = new dba($db_host, $db_user, $db_pass, $db_data, false);
unset($db_host, $db_user, $db_pass, $db_data);

Config::load();

$maint_mode = 1;
if($argc > 1)
	$maint_mode = intval($argv[1]);
set_config('system', 'maintenance', $maint_mode);

if($maint_mode)
	$mode_str = "maintenance mode";
else
	$mode_str = "normal mode";

echo "\n\tSystem set in $mode_str\n\n";
echo "Usage:\n\n";
echo "\tphp {$argv[0]} [1]\tSet the system in maintenance mode\n";
echo "\tphp {$argv[0]} 0  \tSet the system in normal mode\n\n";

