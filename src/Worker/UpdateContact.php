<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Util\DateTimeFormat;

class UpdateContact
{
	/**
	 * Update contact data via probe
	 *
	 * @param int $contact_id Contact ID
	 * @return void
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function execute(int $contact_id)
	{
		// Silently dropping the task if the contact is blocked
		if (Contact::isBlocked($contact_id)) {
			return;
		}

		$success = Contact::updateFromProbe($contact_id);

		DI::logger()->info('Updated from probe', ['id' => $contact_id, 'success' => $success]);
	}

	/**
	 * @param array|int $run_parameters Priority constant or array of options described in Worker::add
	 * @param int       $contact_id
	 * @return int
	 * @throws InternalServerErrorException
	 */
	public static function add($run_parameters, int $contact_id): int
	{
		if (!$contact_id) {
			throw new \InvalidArgumentException('Invalid value provided for contact_id');
		}

		// Dropping the task if the contact is blocked
		if (Contact::isBlocked($contact_id)) {
			return 0;
		}

		DI::logger()->debug('Update contact', ['id' => $contact_id]);
		return Worker::add($run_parameters, 'UpdateContact', $contact_id);
	}

	public static function isUpdatable(int $contact_id): bool
	{
		$contact = Contact::selectFirst(['next-update', 'local-data', 'url', 'network', 'uid'], ['id' => $contact_id]);
		if (empty($contact)) {
			return false;
		}

		if ($contact['next-update'] > DateTimeFormat::utcNow()) {
			return false;
		}

		if (DI::config()->get('system', 'update_known_contacts') && ($contact['uid'] == 0) && !Contact::hasRelations($contact_id)) {
			DI::logger()->debug('No local relations, contact will not be updated', ['id' => $contact_id, 'url' => $contact['url'], 'network' => $contact['network']]);
			return false;
		}

		if (DI::config()->get('system', 'update_active_contacts') && $contact['local-data']) {
			DI::logger()->debug('No local data, contact will not be updated', ['id' => $contact_id, 'url' => $contact['url'], 'network' => $contact['network']]);
			return false;
		}

		if (Contact::isLocal($contact['url'])) {
			DI::logger()->debug('Local contact will not be updated', ['id' => $contact_id, 'url' => $contact['url'], 'network' => $contact['network']]);
			return false;
		}

		if (!Protocol::supportsProbe($contact['network'])) {
			DI::logger()->debug('Contact does not support probe, it will not be updated', ['id' => $contact_id, 'url' => $contact['url'], 'network' => $contact['network']]);
			return false;
		}

		return true;
	}
}
