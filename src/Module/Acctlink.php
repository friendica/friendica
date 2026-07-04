<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException\NotFoundException;

/**
 * Redirects to another URL based on the parameter 'addr'
 */
class Acctlink extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$addr = trim($_GET['addr'] ?? '');
		if (!$addr) {
			throw new NotFoundException('Parameter "addr" is missing or empty');
		}

		$contact = Contact::getByURL($addr, null, ['url']);
		if (!$contact) {
			throw new NotFoundException('Contact not found');
		}

		System::externalRedirect($contact['url']);
	}
}
