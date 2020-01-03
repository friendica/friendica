<?php

namespace Friendica\Factory\Mastodon;

use Friendica\DI;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Model\Introduction;
use Friendica\Network\HTTPException;
use Friendica\Factory;

class FollowRequest extends Factory
{
	/**
	 * @param Introduction $Introduction
	 * @return \Friendica\Api\Entity\Mastodon\FollowRequest
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function createFromIntroduction(Introduction $Introduction)
	{
		$cdata = Contact::getPublicAndUserContacID($Introduction->{'contact-id'}, $Introduction->uid);

		if (empty($cdata)) {
			$this->logger->warning('Wrong introduction data', ['Introduction' => $Introduction]);
			throw new HTTPException\InternalServerErrorException('Wrong introduction data');
		}

		$publicContact = Contact::getById($cdata['public']);
		$userContact = Contact::getById($cdata['user']);

		$apcontact = APContact::getByURL($publicContact['url'], false);

		return new \Friendica\Api\Entity\Mastodon\FollowRequest(DI::baseUrl(), $Introduction->id, $publicContact, $apcontact, $userContact);
	}
}
