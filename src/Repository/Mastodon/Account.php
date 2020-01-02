<?php

namespace Friendica\Repository\Mastodon;

use Friendica\DI;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException;
use Friendica\Repository;

class Account extends Repository
{
	/**
	 * @param int $contactId
	 * @param int $uid        User Id
	 * @return \Friendica\Api\Entity\Mastodon\Account
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function createFromContactId(int $contactId, $uid = 0)
	{
		$cdata = Contact::getPublicAndUserContacID($contactId, $uid);
		if (!empty($cdata)) {
			$publicContact = Contact::getById($cdata['public']);
			$userContact = Contact::getById($cdata['user']);
		} else {
			$publicContact = Contact::getById($contactId);
			$userContact = [];
		}

		$apcontact = APContact::getByURL($publicContact['url'], false);

		return new \Friendica\Api\Entity\Mastodon\Account(DI::baseUrl(), $publicContact, $apcontact, $userContact);
	}
}
