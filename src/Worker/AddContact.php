<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Worker;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\Network;

class AddContact
{
	/**
	 * Add contact data via probe
	 * @param int    $uid User ID
	 * @param string $url Contact link
	 */
	public static function execute(int $uid, string $url)
	{
		try {
			if ($uid == 0) {
				// Adding public contact
				$result = Contact::getIdForURL($url);
				DI::logger()->info('Added public contact', ['url' => $url, 'result' => $result]);
				return;
			}

			$result = Contact::createFromProbeForUser($uid, $url);
			DI::logger()->info('Added contact for user', ['uid' => $uid, 'url' => $url, 'result' => $result]);
		} catch (InternalServerErrorException $e) {
			DI::logger()->warning('Internal server error.', ['exception' => $e, 'uid' => $uid, 'url' => $url]);
		} catch (NotFoundException $e) {
			DI::logger()->notice('uid not found.', ['exception' => $e, 'uid' => $uid, 'url' => $url]);
		} catch (\ImagickException $e) {
			DI::logger()->notice('Imagick not found.', ['exception' => $e, 'uid' => $uid, 'url' => $url]);
		}
	}

	/**
	 * @param array|int $run_parameters Priority constant or array of options described in Worker::add
	 * @param int    $uid User ID
	 * @param string $url Contact link
	 * @return int
	 */
	public static function add($run_parameters, int $uid, string $url): int
	{
		if (Network::isUrlBlocked($url)) {
			return 0;
		}

		DI::logger()->debug('Add contact', ['uid' => $uid, 'url' => $url]);
		return Worker::add($run_parameters, 'AddContact', 0, $url);
	}
}
