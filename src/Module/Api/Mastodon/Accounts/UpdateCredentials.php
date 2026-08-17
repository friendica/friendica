<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Accounts;

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
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$owner = User::getOwnerDataById($uid);

		$request = $this->getRequest([
			'bot'               => ($owner['contact-type'] == Contact::TYPE_NEWS),
			// (bool) casts matter here: BaseModule::getRequestValue() picks its coercion
			// branch off the *type* of the default, not the field's meaning. `net-publish`/
			// `manually-approve` come back from the DB as ints, not native bools, so without
			// the cast they fell into the is_int() branch, which runs FILTER_VALIDATE_INT on
			// the incoming "true"/"false" string -- that fails to parse as an int and silently
			// collapses to false, so `locked`/`discoverable` could never actually be set to
			// true from this endpoint. `bot`'s default is already a real bool (comparison
			// result), which is why it was never affected.
			'discoverable'      => (bool) $owner['net-publish'],
			'display_name'      => $owner['name'],
			'fields_attributes' => [],
			'locked'            => (bool) $owner['manually-approve'],
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
			// @todo Support for PAGE_FLAGS_COMM_MAN
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

		$this->logger->debug('Patch data', ['data' => $request, 'files' => $_FILES]);

		$this->logger->info('Update profile and user', ['uid' => $uid, 'user' => $user, 'profile' => $profile]);

		if (!empty($request['avatar'])) {
			Photo::uploadAvatar($uid, $request['avatar']);
		}

		if (!empty($request['header'])) {
			Photo::uploadBanner($uid, $request['header']);
		}

		User::update($user, $uid);
		Profile::update($profile, $uid);

		$ucid = Contact::getUserContactId($owner['id'], $uid);
		if (!$ucid) {
			DI::mstdnError()->InternalError();
		}

		$account = DI::mstdnAccount()->createFromContactId($ucid, $uid);
		$this->jsonExit($account->toArray());
	}
}
