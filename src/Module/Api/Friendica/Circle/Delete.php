<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Friendica\Circle;

use Friendica\Database\DBA;
use Friendica\Model\Circle;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\BadRequestException;

/**
 * API endpoint: /api/friendica/group/delete
 * API endpoint: /api/friendica/circle/delete
 */
class Delete extends BaseApi
{
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'gid'  => 0,
			'name' => '',
		], $request);

		// params

		// error if no gid specified
		if ($request['gid'] == 0 || $request['name'] == '') {
			throw new BadRequestException('gid or name not specified');
		}

		// error message if specified gid is not in database
		if (!Circle::exists((int) $request['gid'], $uid)) {
			throw new BadRequestException('gid not available');
		}

		// error message if specified gid is not in database
		if (!DBA::exists('group', ['uid' => $uid, 'id' => $request['gid'], 'name' => $request['name']])) {
			throw new BadRequestException('wrong circle name');
		}

		// delete circle
		$gid = Circle::getIdByName($uid, $request['name']);
		if (empty($request['gid'])) {
			throw new BadRequestException('other API error');
		}

		$ret = Circle::remove($gid);

		if ($ret) {
			// return success
			$success = ['success' => $ret, 'gid' => $request['gid'], 'name' => $request['name'], 'status' => 'deleted', 'wrong users' => []];
			$this->response->addFormattedContent('group_delete', ['$result' => $success], $this->parameters['extension'] ?? null);
		} else {
			throw new BadRequestException('other API error');
		}
	}
}
