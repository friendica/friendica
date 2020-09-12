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

namespace Friendica\Domain\Factory\Api\Twitter;

use Friendica\Domain\BaseFactory;
use Friendica\Domain\Entity\Api\Twitter\User as TwitterUserEntity;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException;

class User extends BaseFactory
{
	/**
	 * @param int  $contactId
	 * @param int  $uid Public contact (=0) or owner user id
	 * @param bool $skip_status
	 * @param bool $include_user_entities
	 *
	 * @return TwitterUserEntity
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function createFromContactId(int $contactId, $uid = 0, $skip_status = false, $include_user_entities = true)
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

		return new TwitterUserEntity($publicContact, $apcontact, $userContact, $skip_status, $include_user_entities);
	}
}
