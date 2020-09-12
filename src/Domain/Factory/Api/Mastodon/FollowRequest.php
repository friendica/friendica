<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Domain\Factory\Api\Mastodon;

use Friendica\App\BaseURL;
use Friendica\Domain\BaseFactory;
use Friendica\Domain\Entity\Api\Mastodon\FollowRequest as MastodonFollowRequestEntity;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Domain\Model\Introduction;
use Friendica\Network\HTTPException;
use Psr\Log\LoggerInterface;

class FollowRequest extends BaseFactory
{
	/** @var BaseURL */
	protected $baseUrl;

	public function __construct(LoggerInterface $logger, BaseURL $baseURL)
	{
		parent::__construct($logger);

		$this->baseUrl = $baseURL;
	}

	/**
	 * @param Introduction $introduction
	 *
	 * @return MastodonFollowRequestEntity
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function createFromIntroduction(Introduction $introduction)
	{
		$cdata = Contact::getPublicAndUserContacID($introduction->{'contact-id'}, $introduction->uid);

		if (empty($cdata)) {
			$this->logger->warning('Wrong introduction data', ['Introduction' => $introduction]);
			throw new HTTPException\InternalServerErrorException('Wrong introduction data');
		}

		$publicContact = Contact::getById($cdata['public']);
		$userContact = Contact::getById($cdata['user']);

		$apcontact = APContact::getByURL($publicContact['url'], false);

		return new MastodonFollowRequestEntity($this->baseUrl, $introduction->id, $publicContact, $apcontact, $userContact);
	}
}
