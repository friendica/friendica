<?php
/**
 * @file mod/phpinfo.php
 */

require_once 'boot.php';

function phpinfo_content()
{
	if (!is_site_admin()) {
		return false;
	}

	phpinfo();
	killme();
}
