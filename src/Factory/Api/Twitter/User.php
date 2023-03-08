<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Factory\Api\Twitter;

use Friendica\BaseFactory;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException;
use Friendica\Factory\Api\Twitter\Status;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Psr\Log\LoggerInterface;

class User extends BaseFactory
{
	/** @var Status entity */
	private $status;

	public function __construct(LoggerInterface $logger, Status $status)
	{
		parent::__construct($logger);
		$this->status = $status;

	}

	/**
	 * @param int  $contactId
	 * @param int  $uid Public contact (=0) or owner user id
	 * @param bool $skip_status
	 * @param bool $include_user_entities
	 *
	 * @return \Friendica\Object\Api\Twitter\User
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function createFromContactId(int $contactId, int $uid = 0, bool $skip_status = true, bool $include_user_entities = true): \Friendica\Object\Api\Twitter\User
	{
		$cdata = Contact::getPublicAndUserContactID($contactId, $uid);
		if (!empty($cdata)) {
			$publicContact = Contact::getById($cdata['public']);
			$userContact = Contact::getById($cdata['user']);
		} else {
			$publicContact = Contact::getById($contactId);
			$userContact = [];
		}

		$apcontact = APContact::getByURL($publicContact['url'], false);

		$status = null;

		if (!$skip_status) {
			$post = Post::selectFirstPost(['uri-id'],
				['author-id' => $publicContact['id'], 'gravity' => [Item::GRAVITY_COMMENT, Item::GRAVITY_PARENT], 'private'  => [Item::PUBLIC, Item::UNLISTED]],
				['order' => ['uri-id' => true]]);
			if (!empty($post['uri-id'])) {
				$status = $this->status->createFromUriId($post['uri-id'], $uid)->toArray();
			}
		}

		return new \Friendica\Object\Api\Twitter\User($publicContact, $apcontact, $userContact, $status, $include_user_entities);
	}

	/**
	 * @param int  $uid Public contact (=0) or owner user id
	 * @param bool $skip_status
	 * @param bool $include_user_entities
	 *
	 * @return \Friendica\Object\Api\Twitter\User
	 */
	public function createFromUserId(int $uid, bool $skip_status = true, bool $include_user_entities = true): \Friendica\Object\Api\Twitter\User
	{
		return $this->createFromContactId(Contact::getPublicIdByUserId($uid), $uid, $skip_status, $include_user_entities);
	}
}
