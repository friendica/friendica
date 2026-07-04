<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\ActivityPub;

use Friendica\Content\Text\BBCode;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\BaseApi;
use Friendica\Protocol\ActivityPub;

/**
 * "who am i" endpoint for ActivityPub C2S
 */
class Whoami extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$owner = User::getOwnerDataById($uid);

		$data = ['@context' => ActivityPub::CONTEXT];

		$data['id']                        = $owner['url'];
		$data['url']                       = $owner['url'];
		$data['type']                      = ActivityPub::ACCOUNT_TYPES[$owner['account-type']];
		$data['name']                      = $owner['name'];
		$data['preferredUsername']         = $owner['nick'];
		$data['alsoKnownAs']               = [];
		$data['manuallyApprovesFollowers'] = in_array($owner['page-flags'], [User::PAGE_FLAGS_NORMAL, User::PAGE_FLAGS_PRVGROUP, User::PAGE_FLAGS_COMM_MAN]);
		$data['discoverable']              = (bool) $owner['net-publish'];
		$data['indexable']                 = (bool) $owner['net-publish'];
		$data['tag']                       = [];

		$data['icon'] = [
			'type' => 'Image',
			'url'  => User::getAvatarUrl($owner),
		];

		if (!empty($owner['about'])) {
			$data['summary'] = BBCode::convertForUriId($owner['uri-id'] ?? 0, $owner['about'], BBCode::EXTERNAL);
		}

		$custom_fields = [];

		foreach (DI::profileField()->selectByContactId(0, $uid) as $profile_field) {
			$custom_fields[] = [
				'type'  => 'PropertyValue',
				'name'  => $profile_field->label,
				'value' => BBCode::convertForUriId($owner['uri-id'], $profile_field->value),
			];
		};

		if (!empty($custom_fields)) {
			$data['attachment'] = $custom_fields;
		}

		$data['publicKey'] = [
			'id'           => $owner['url'] . '#main-key',
			'owner'        => $owner['url'],
			'publicKeyPem' => $owner['pubkey'],
		];

		$data['capabilities'] = [];
		$data['inbox']        = DI::baseUrl() . '/inbox/' . $owner['nick'];
		$data['outbox']       = DI::baseUrl() . '/outbox/' . $owner['nick'];
		$data['featured']     = DI::baseUrl() . '/featured/' . $owner['nick'];
		$data['followers']    = DI::baseUrl() . '/followers/' . $owner['nick'];
		$data['following']    = DI::baseUrl() . '/following/' . $owner['nick'];

		$data['endpoints'] = [
			'oauthAuthorizationEndpoint' => DI::baseUrl() . '/oauth/authorize',
			'oauthRegistrationEndpoint'  => DI::baseUrl() . '/api/v1/apps',
			'oauthTokenEndpoint'         => DI::baseUrl() . '/oauth/token',
			'sharedInbox'                => DI::baseUrl() . '/inbox',
			//'uploadMedia'                => DI::baseUrl() . '/api/upload_media' // @todo Endpoint does not exist at the moment
		];

		$data['generator'] = ActivityPub\Transmitter::getService();
		$this->jsonExit($data, 'application/activity+json');
	}
}
