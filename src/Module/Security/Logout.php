<?php
/**
 * @file src/Module/Logout.php
 */

namespace Friendica\Module\Security;

use Friendica\BaseModule;
use Friendica\Core\Hook;
use Friendica\Core\System;
use Friendica\Model\Profile;
use Friendica\Registry\App;
use Friendica\Registry\Core;
use Friendica\Registry\Model;

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
			Core::cache()->delete('zrlInit:' . $visitor_home);
		}

		Hook::callAll("logging_out");
		Model::cookie()->clear();
		Core::session()->clear();

		if ($visitor_home) {
			System::externalRedirect($visitor_home);
		} else {
			info(Core::l10n()->t('Logged out.'));
			App::baseUrl()->redirect();
		}
	}
}
