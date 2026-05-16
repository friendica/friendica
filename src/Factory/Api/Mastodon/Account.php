<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Mastodon;

use Friendica\App\BaseURL;
use Friendica\BaseFactory;
use Friendica\Collection\Api\Mastodon\Fields;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Widget;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\User;
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
	/** @var IManagePersonalConfigValues */
	private $pConfig;

	public function __construct(IManagePersonalConfigValues $pConfig, LoggerInterface $logger, BaseURL $baseURL, ProfileFieldRepository $profileFieldRepo, Field $mstdnFieldFactory)
	{
		parent::__construct($logger);

		$this->baseUrl           = $baseURL;
		$this->profileFieldRepo  = $profileFieldRepo;
		$this->mstdnFieldFactory = $mstdnFieldFactory;
		$this->pConfig           = $pConfig;
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
		$source = null;

		if (Contact::isLocal($account['url'])) {
			$profile_uid = User::getIdForContactId($account['id']);
			if ($profile_uid) {
				$profileFields = $this->profileFieldRepo->selectPublicFieldsByUserId($profile_uid);
				$fields        = $this->mstdnFieldFactory->createFromProfileFields($profileFields);

				if ($profile_uid == $uid) {
					$account['ap-followers_count'] = $this->getContactRelationCountForUid($uid, [Contact::FOLLOWER, Contact::FRIEND]);
					$account['ap-following_count'] = $this->getContactRelationCountForUid($uid, [Contact::SHARING, Contact::FRIEND]);
				}
				// Create a default source object if the request is done by the profile owner
				// Friendica doesn't support roles, so we create a dummy role object
				$role = new \Friendica\Object\Api\Mastodon\Role(1, 'User', '#000000', '0000000', false);
				$note = BBCode::toPlaintext($account['about'] ?? '', true);

				$user = User::getById($profile_uid, ['allow_gid', 'allow_cid', 'deny_gid', 'deny_cid']);
				if ($user['allow_gid'] || $user['allow_cid'] || $user['deny_gid'] || $user['deny_cid']) {
					$privacy = 'private';
				} elseif ($this->pConfig->get($uid, 'system', 'unlisted')) {
					$privacy = 'unlisted';
				} else {
					$privacy = 'public';
				}

				$requests = count(\Friendica\Model\Register::getPending());

				$source = new \Friendica\Object\Api\Mastodon\Source([''], $note, $fields, $privacy, false, '', $requests, null, !$account['unsearchable'], !$account['unsearchable'], $role);
			}
		}

		return new \Friendica\Object\Api\Mastodon\Account($this->baseUrl, $account, $fields, $source);
	}

	private function getContactRelationCountForUid(int $uid, array $rel): int
	{
		$condition = [
			'uid'     => $uid,
			'rel'     => $rel,
			'self'    => false,
			'deleted' => false,
			'archive' => false,
			'pending' => false,
			'blocked' => false,
			'network' => Widget::availableNetworks(),
		];

		return DBA::count('contact', $condition);
	}
}
