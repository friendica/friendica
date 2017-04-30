<?php
/* ACL selector json backend */

use Friendica\App;

require_once 'include/acl_selectors.php';

function acl_init(App $a) {
	acl_lookup($a);
}


