<?php
/* ACL selector json backend */

require_once("include/acl_selectors.php");

function acl_init(App $a) {
	acl_lookup($a);
}


