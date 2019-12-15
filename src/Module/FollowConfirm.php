<?php
namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Model\Introduction;
use Friendica\DI;

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

		/** @var Introduction $Intro */
		$Intro = self::getClass(Introduction::class);
		$Intro->fetch(['id' => $intro_id, 'uid' => local_user()]);

		$cid = $Intro->{'contact-id'};

		$Intro->confirm($duplex, $hidden);

		DI::baseUrl()->redirect('contact/' . intval($cid));
	}
}
