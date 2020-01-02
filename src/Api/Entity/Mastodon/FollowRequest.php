<?php

namespace Friendica\Api\Entity\Mastodon;

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
	 * @param BaseURL $baseUrl
	 * @param int     $introduction_id Introduction record id
	 * @param array   $publicContact   Full contact table record with uid = 0
	 * @param array   $apcontact       Optional full apcontact table record
	 * @param array   $userContact     Optional full contact table record with uid != 0
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(BaseURL $baseUrl, int $introduction_id, array $publicContact, array $apcontact = [], array $userContact = [])
	{
		parent::__construct($baseUrl, $publicContact, $apcontact, $userContact);

		$this->id = $introduction_id;
	}
}
