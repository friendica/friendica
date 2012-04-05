<?php

$str = <<< EOT
	error_reporting(E_ERROR | E_WARNING | E_PARSE );
	ini_set('display_errors', '1');
	ini_set('log_errors','0');
EOT;

	$str .= str_replace('<?php', '', file_get_contents($argv[1]));
	
	eval($str);
