<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Friendica\Circle;

use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Circle;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\BadRequestException;

/**
 * API endpoint: /api/friendica/circle_update
 * API endpoint: /api/friendica/group_update
 */
class Update extends BaseApi
{
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_WRITE);
		$uid = BaseApi::getCurrentUserID();

		// params
		$gid  = $this->getRequestValue($request, 'gid', 0);
		$name = $this->getRequestValue($request, 'name', '');

		// error if no name specified
		if (!$name) {
			throw new BadRequestException($this->t('circle name not specified'));
		}

		// error if no gid specified
		if (!$gid) {
			throw new BadRequestException($this->t('gid not specified'));
		}

		// error message if specified gid is not in database
		if (!Circle::exists((int) $gid, $uid)) {
			throw new BadRequestException($this->t('gid not available'));
		}

		$json = json_decode((string) $this->getRequestValue($request, 'json', ''), true);
		if (!is_array($json) || !isset($json['user']) || !is_array($json['user'])) {
			throw new BadRequestException($this->t('no valid user list submitted'));
		}

		$users = $json['user'];

		// remove members
		$members = Contact\Circle::getById($gid);
		foreach ($members as $member) {
			$cid   = $member['id'];
			$found = false;
			foreach ($users as $user) {
				if (($user['cid'] ?? null) == $cid) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				$gid = Circle::getIdByName($uid, $name);
				Circle::removeMember($gid, $cid);
			}
		}

		// add members
		$erroraddinguser = false;
		$errorusers      = [];
		foreach ($users as $user) {
			$cid = $user['cid'] ?? 0;

			if (DBA::exists('contact', ['id' => $cid, 'uid' => $uid])) {
				Circle::addMember($gid, $cid);
			} else {
				$erroraddinguser = true;
				$errorusers[]    = $cid;
			}
		}

		// return success message incl. missing users in array
		$status  = ($erroraddinguser ? 'missing user' : 'ok');
		$success = ['success' => true, 'gid' => $gid, 'name' => $name, 'status' => $status, 'wrong users' => $errorusers];
		$this->response->addFormattedContent('group_update', ['$result' => $success], $this->parameters['extension'] ?? null);
	}
}
