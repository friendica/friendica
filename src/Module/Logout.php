<?php
/**
 * @file src/Module/Logout.php
 */
namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Addon;
use Friendica\Core\L10n;

require_once 'boot.php';
require_once 'include/security.php';

/**
 * Logout module
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
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
		info(L10n::t('Logged out.') . EOL);
		goaway(self::getApp()->get_baseurl());
	}
}
