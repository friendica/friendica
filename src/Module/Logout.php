<?php
/**
 * @file src/Module/Logout.php
 */
namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Addon;
use Friendica\Core\Authentication;
use Friendica\Core\L10n;
use Friendica\Core\System;

require_once 'boot.php';

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
		Authentication::deleteSession();
		info(L10n::t('Logged out.') . EOL);
		self::getApp()->redirect();
	}
}
