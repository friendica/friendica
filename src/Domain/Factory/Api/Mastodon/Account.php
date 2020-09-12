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
use Friendica\Domain\Collection\Api\Mastodon\Fields;
use Friendica\Domain\Entity\Api\Mastodon\Account as MastodonAccountEntity;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException;
use Friendica\Domain\Repository\PermissionSet;
use Friendica\Domain\Repository\ProfileField;
use Psr\Log\LoggerInterface;

class Account extends BaseFactory
{
	/** @var BaseURL */
	protected $baseUrl;
	/** @var ProfileField */
	protected $profileField;
	/** @var Field */
	protected $mstdnField;

	public function __construct(LoggerInterface $logger, BaseURL $baseURL, ProfileField $profileField, Field $mstdnField)
	{
		parent::__construct($logger);

		$this->baseUrl = $baseURL;
		$this->profileField = $profileField;
		$this->mstdnField = $mstdnField;
	}

	/**
	 * @param int $contactId
	 * @param int $uid        Public contact (=0) or owner user id
	 *
	 * @return MastodonAccountEntity
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

		return new MastodonAccountEntity($this->baseUrl, $publicContact, new Fields(), $apcontact, $userContact);
	}

	/**
	 * @param int $userId
	 *
	 * @return MastodonAccountEntity
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function createFromUserId(int $userId)
	{
		$publicContact = Contact::selectFirst([], ['uid' => $userId, 'self' => true]);

		$profileFields = $this->profileField->select(['uid' => $userId, 'psid' => PermissionSet::PUBLIC]);
		$fields        = $this->mstdnField->createFromProfileFields($profileFields);

		$apcontact     = APContact::getByURL($publicContact['url'], false);

		return new MastodonAccountEntity($this->baseUrl, $publicContact, $fields, $apcontact);
	}
}
