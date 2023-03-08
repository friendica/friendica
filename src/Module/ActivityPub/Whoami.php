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

namespace Friendica\Module\ActivityPub;

use Friendica\Content\Text\BBCode;
use Friendica\Core\System;
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
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$owner = User::getOwnerDataById($uid);

		$data = ['@context' => ActivityPub::CONTEXT];

		$data['id']                        = $owner['url'];
		$data['url']                       = $owner['url'];
		$data['type']                      = ActivityPub::ACCOUNT_TYPES[$owner['account-type']];
		$data['name']                      = $owner['name'];
		$data['preferredUsername']         = $owner['nick'];
		$data['alsoKnownAs']               = [];
		$data['manuallyApprovesFollowers'] = in_array($owner['page-flags'], [User::PAGE_FLAGS_NORMAL, User::PAGE_FLAGS_PRVGROUP]);
		$data['discoverable']              = (bool)$owner['net-publish'];
		$data['tag']                       = [];

		$data['icon'] = [
			'type' => 'Image',
			'url'  => User::getAvatarUrl($owner)
		];

		if (!empty($owner['about'])) {
			$data['summary'] = BBCode::convertForUriId($owner['uri-id'] ?? 0, $owner['about'], BBCode::EXTERNAL);
		}

		$custom_fields = [];

		foreach (DI::profileField()->selectByContactId(0, $uid) as $profile_field) {
			$custom_fields[] = [
				'type'  => 'PropertyValue',
				'name'  => $profile_field->label,
				'value' => BBCode::convertForUriId($owner['uri-id'], $profile_field->value)
			];
		};

		if (!empty($custom_fields)) {
			$data['attachment'] = $custom_fields;
		}

		$data['publicKey'] = [
			'id'           => $owner['url'] . '#main-key',
			'owner'        => $owner['url'],
			'publicKeyPem' => $owner['pubkey']
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
//			'uploadMedia'                => DI::baseUrl() . '/api/upload_media' // @todo Endpoint does not exist at the moment
		];

		$data['generator'] = ActivityPub\Transmitter::getService();
		System::jsonExit($data, 'application/activity+json');
	}
}
