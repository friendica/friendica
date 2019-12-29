<?php

namespace Friendica\Api\Mastodon;

use Friendica\App\BaseURL;
use Friendica\Model\Introduction;

/**
 * Virtual entity to separate Accounts from Follow Requests.
 * In the Mastodon API they are one and the same.
 */
class FollowRequest extends Account
{
	/**
	 * Creates a follow request entity from an introduction record.
	 *
	 * The account ID is set to the Introduction ID to allow for later interaction with follow requests.
	 *
	 * @param BaseURL      $baseUrl
	 * @param Introduction $Introduction
	 * @param array        $publicContact Full contact table record with uid = 0
	 * @param array        $apcontact     Optional full apcontact table record
	 * @param array        $userContact   Optional full contact table record with uid != 0
	 * @return Account
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function createFromIntroduction(BaseURL $baseUrl, Introduction $Introduction, array $publicContact, array $apcontact = [], array $userContact = [])
	{
		$account = parent::create($baseUrl, $publicContact, $apcontact, $userContact);

		$account->id = $Introduction->id;

		return $account;
	}
}
