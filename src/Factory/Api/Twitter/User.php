<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Twitter;

use Friendica\BaseFactory;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;
use Psr\Log\LoggerInterface;

class User extends BaseFactory
{
	public function __construct(LoggerInterface $logger, /** @var Status entity */
		private readonly Status $status)
	{
		parent::__construct($logger);

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
			$userContact   = Contact::getById($cdata['user']);
		} else {
			$publicContact = Contact::getById($contactId);
			$userContact   = [];
		}

		$apcontact = APContact::getByURL($publicContact['url'], false);

		$status = null;

		if (!$skip_status) {
			$post = Post::selectFirstPost(
				['uri-id'],
				['author-id' => $publicContact['id'], 'gravity' => [Item::GRAVITY_COMMENT, Item::GRAVITY_PARENT], 'private' => [Item::PUBLIC, Item::UNLISTED]],
				['order'     => ['uri-id' => true]],
			);
			if (!empty($post['uri-id'])) {
				$status = $this->status->createFromUriId($post['uri-id'], $uid);
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
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException If the $uid doesn't exist
	 * @throws \ImagickException
	 */
	public function createFromUserId(int $uid, bool $skip_status = true, bool $include_user_entities = true): \Friendica\Object\Api\Twitter\User
	{
		$cid = Contact::getPublicIdByUserId($uid);
		if (!$cid) {
			throw new HTTPException\NotFoundException();
		}

		return $this->createFromContactId($cid, $uid, $skip_status, $include_user_entities);
	}
}
