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

namespace Friendica\Factory\Api\Mastodon;

use Friendica\App\BaseURL;
use Friendica\BaseFactory;
use Friendica\Collection\Api\Mastodon\Fields;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException;
use Friendica\Profile\ProfileField\Repository\ProfileField as ProfileFieldRepository;
use ImagickException;
use Psr\Log\LoggerInterface;

class Account extends BaseFactory
{
	/** @var BaseURL */
	private $baseUrl;
	/** @var ProfileFieldRepository */
	private $profileFieldRepo;
	/** @var Field */
	private $mstdnFieldFactory;

	public function __construct(LoggerInterface $logger, BaseURL $baseURL, ProfileFieldRepository $profileFieldRepo, Field $mstdnFieldFactory)
	{
		parent::__construct($logger);

		$this->baseUrl           = $baseURL;
		$this->profileFieldRepo  = $profileFieldRepo;
		$this->mstdnFieldFactory = $mstdnFieldFactory;
	}

	/**
	 * @param int $contactId
	 * @param int $uid        Public contact (=0) or owner user id
	 *
	 * @return \Friendica\Object\Api\Mastodon\Account
	 * @throws HTTPException\InternalServerErrorException
	 * @throws ImagickException|HTTPException\NotFoundException
	 */
	public function createFromContactId(int $contactId, int $uid = 0): \Friendica\Object\Api\Mastodon\Account
	{
		$contact = Contact::getById($contactId, ['uri-id']);

		if (empty($contact)) {
			throw new HTTPException\NotFoundException('Contact ' . $contactId . ' not found');
		}
		if (empty($contact['uri-id'])) {
			throw new HTTPException\NotFoundException('Contact ' . $contactId . ' has no uri-id set');
		}

		return self::createFromUriId($contact['uri-id'], $uid);
	}

	/**
	 * @param int $contactUriId
	 * @param int $uid          Public contact (=0) or owner user id
	 *
	 * @return \Friendica\Object\Api\Mastodon\Account
	 * @throws HTTPException\InternalServerErrorException
	 * @throws ImagickException|HTTPException\NotFoundException
	 */
	public function createFromUriId(int $contactUriId, int $uid = 0): \Friendica\Object\Api\Mastodon\Account
	{
		$account = DBA::selectFirst('account-user-view', [], ['uri-id' => $contactUriId, 'uid' => [0, $uid]], ['order' => ['id' => true]]);
		if (empty($account)) {
			throw new HTTPException\NotFoundException('Contact ' . $contactUriId . ' not found');
		}

		$fields = new Fields();

		if (Contact::isLocal($account['url'])) {
			$self_contact = Contact::selectFirst(['uid'], ['nurl' => $account['nurl'], 'self' => true]);
			if (!empty($self_contact['uid'])) {
				$profileFields = $this->profileFieldRepo->selectPublicFieldsByUserId($self_contact['uid']);
				$fields        = $this->mstdnFieldFactory->createFromProfileFields($profileFields);
			}
		}

		return new \Friendica\Object\Api\Mastodon\Account($this->baseUrl, $account, $fields);
	}

	/**
	 * @param int $userId
	 * @return \Friendica\Object\Api\Mastodon\Account
	 * @throws ImagickException|HTTPException\InternalServerErrorException
	 */
	public function createFromUserId(int $userId): \Friendica\Object\Api\Mastodon\Account
	{
		$account       = DBA::selectFirst('account-user-view', [], ['uid' => $userId, 'self' => true]);
		$profileFields = $this->profileFieldRepo->selectPublicFieldsByUserId($userId);
		$fields        = $this->mstdnFieldFactory->createFromProfileFields($profileFields);

		return new \Friendica\Object\Api\Mastodon\Account($this->baseUrl, $account, $fields);
	}
}
