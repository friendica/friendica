<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Worker;
use Friendica\DI;
use Friendica\Model\Contact;

class ContactDiscovery
{
	/**
	 * Discover contact relations
	 * @param string $url
	 */
	public static function execute(string $url)
	{
		Contact\Relation::discoverByUrl($url);
	}

	/**
	 * @param array|int $run_parameters Priority constant or array of options described in Worker::add
	 * @param string    $url
	 * @return int
	 */
	public static function add($run_parameters, string $url): int
	{
		DI::logger()->debug('Contact discovery', ['url' => $url]);
		return Worker::add($run_parameters, 'ContactDiscovery', $url);
	}

	/**
	 * Checks if the maximum number of allowed workers for this task is reached
	 *
	 * @return boolean
	 */
	public static function workerLimitReached(): bool
	{
		$discovery_limit = (int)DI::config()->get('system', 'contact_discovery_limit');
		$discovering     = Worker::countWorkersByCommand('ContactDiscovery');
		if ($discovering >= $discovery_limit) {
			DI::logger()->info('The number of currently running jobs exceed the limit', ['discovering' => $discovering, 'limit' => $discovery_limit]);
		}
		return ($discovering >= $discovery_limit);
	}
}
