<?php
namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Registry\App;
use Friendica\Registry\Factory;
use Friendica\Registry\Model;

/**
 * Process follow request confirmations
 */
class FollowConfirm extends BaseModule
{
	public static function post(array $parameters = [])
	{
		$uid = local_user();
		if (!$uid) {
			notice(L10n::t('Permission denied.') . EOL);
			return;
		}

		$intro_id = intval($_POST['intro_id']   ?? 0);
		$duplex   = intval($_POST['duplex']     ?? 0);
		$hidden   = intval($_POST['hidden']     ?? 0);

		$Intro = Factory::intro()->selectFirst(['id' => $intro_id, 'uid' => local_user()]);

		$cid = $intro->{'contact-id'};

		$intro->confirm($duplex, $hidden);

		App::baseUrl()->redirect('contact/' . intval($cid));
	}
}
