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

namespace Friendica\Module\Api\Mastodon\Accounts;

use Friendica\Core\Logger;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Photo;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/
 */
class UpdateCredentials extends BaseApi
{
	protected function patch(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$owner = User::getOwnerDataById($uid);

		$request = $this->getRequest([
			'bot'               => ($owner['contact-type'] == Contact::TYPE_NEWS),
			'discoverable'      => $owner['net-publish'],
			'display_name'      => $owner['name'],
			'fields_attributes' => [],
			'locked'            => $owner['manually-approve'],
			'note'              => $owner['about'],
			'avatar'            => [],
			'header'            => [],
		], $request);

		$user    = [];
		$profile = [];

		if ($request['bot']) {
			$user['account-type'] = Contact::TYPE_NEWS;
			$user['page-flags']   = User::PAGE_FLAGS_SOAPBOX;
		} elseif ($owner['contact-type'] == Contact::TYPE_NEWS) {
			$user['account-type'] = Contact::TYPE_PERSON;
		} else {
			$user['account-type'] = $owner['contact-type'];
		}

		$profile['net-publish'] = $request['discoverable'];

		if (!empty($request['display_name'])) {
			$user['username'] = $request['display_name'];
		}

		if ($user['account-type'] == Contact::TYPE_COMMUNITY) {
			$user['page-flags'] = $request['locked'] ? User::PAGE_FLAGS_PRVGROUP : User::PAGE_FLAGS_COMMUNITY;
		} elseif ($user['account-type'] == Contact::TYPE_PERSON) {
			if ($request['locked']) {
				$user['page-flags'] = User::PAGE_FLAGS_NORMAL;
			} elseif ($owner['page-flags'] == User::PAGE_FLAGS_NORMAL) {
				$user['page-flags'] = User::PAGE_FLAGS_SOAPBOX;
			}
		}

		if (!empty($request['note'])) {
			$profile['about'] = $request['note'];
		}

		Logger::debug('Patch data', ['data' => $request, 'files' => $_FILES]);

		Logger::info('Update profile and user', ['uid' => $uid, 'user' => $user, 'profile' => $profile]);

		if (!empty($request['avatar'])) {
			Photo::uploadAvatar($uid, $request['avatar']);
		}

		if (!empty($request['header'])) {
			Photo::uploadBanner($uid, $request['header']);
		}

		User::update($user, $uid);
		Profile::update($profile, $uid);

		$cdata = Contact::getPublicAndUserContactID($owner['id'], $uid);
		if (empty($cdata)) {
			DI::mstdnError()->InternalError();
		}

		$account = DI::mstdnAccount()->createFromContactId($cdata['user'], $uid);
		$this->response->exitWithJson($account->toArray());
	}
}
