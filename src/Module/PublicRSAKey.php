<?php

namespace Friendica\Module;

use ASN_BASE;
use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Network\HTTPException\BadRequestException;

/**
 * prints the public RSA key of a user
 */
class PublicRSAKey extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$app = DI::app();

		// @TODO: Replace with parameter from router
		if ($app->argc !== 2) {
			throw new BadRequestException();
		}

		// @TODO: Replace with parameter from router
		$nick = $app->argv[1];

		$user = User::getByNickname($nick, ['spubkey']);
		if (empty($user) || empty($user['spubkey'])) {
			throw new BadRequestException();
		}

		$lines = explode("\n", $user['spubkey']);
		unset($lines[0]);
		unset($lines[count($lines)]);

		$asnString = base64_decode(implode('', $lines));
		$asnBase = ASN_BASE::parseASNString($asnString);

		$m = $asnBase[0]->asnData[1]->asnData[0]->asnData[0]->asnData;
		$e = $asnBase[0]->asnData[1]->asnData[0]->asnData[1]->asnData;

		header('Content-type: application/magic-public-key');
		echo 'RSA' . '.' . $m . '.' . $e;

		exit();
	}
}
