<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Factory\Api\Mastodon;

use Friendica\App\BaseURL;
use Friendica\BaseFactory;
use Friendica\Contact\Introduction\Entity\Introduction;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException;
use ImagickException;
use Psr\Log\LoggerInterface;

class FollowRequest extends BaseFactory
{
	/** @var BaseURL */
	private $baseUrl;

	public function __construct(LoggerInterface $logger, BaseURL $baseURL)
	{
		parent::__construct($logger);

		$this->baseUrl = $baseURL;
	}

	/**
	 * @param Introduction $introduction
	 * @return \Friendica\Object\Api\Mastodon\FollowRequest
	 * @throws ImagickException|HTTPException\InternalServerErrorException
	 */
	public function createFromIntroduction(Introduction $introduction): \Friendica\Object\Api\Mastodon\FollowRequest
	{
		$cdata = Contact::getPublicAndUserContactID($introduction->cid, $introduction->uid);

		if (empty($cdata)) {
			$this->logger->warning('Wrong introduction data', ['Introduction' => $introduction]);
			throw new HTTPException\InternalServerErrorException('Wrong introduction data');
		}

		$publicContact = Contact::getById($cdata['public']);
		$userContact   = Contact::getById($cdata['user']);

		$apContact = APContact::getByURL($publicContact['url'], false);

		return new \Friendica\Object\Api\Mastodon\FollowRequest($this->baseUrl, $introduction->id, $publicContact, $apContact, $userContact);
	}
}
