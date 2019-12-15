<?php
/**
 * @file src/Module/Logout.php
 */

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\App\Authentication;
use Friendica\Core\Cache;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Profile;

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
	public static function init(array $parameters = [])
	{
		$visitor_home = null;
		if (remote_user()) {
			$visitor_home = Profile::getMyURL();
			Cache::delete('zrlInit:' . $visitor_home);
		}

		Hook::callAll("logging_out");
		Session::clear();

		if ($visitor_home) {
			System::externalRedirect($visitor_home);
		} else {
			info(L10n::t('Logged out.'));
			DI::app()->internalRedirect();
		}
	}
}
