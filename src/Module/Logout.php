<?php
/**
 * @file src/Module/Logout.php
 */
namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Addon;

require_once 'boot.php';
require_once 'include/pgettext.php';
require_once 'include/security.php';

/**
 * Logout module
 *
 * @author Hypolite Petovan mrpetovan@gmail.com
 */
class Logout extends BaseModule
{
	/**
	 * @brief Process logout requests
	 */
	public static function init()
	{
		Addon::callHooks("logging_out");
		nuke_session();
		info(t('Logged out.') . EOL);
		goaway(self::getApp()->get_baseurl());
	}
}
