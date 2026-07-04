<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\Contact;

/**
 * Redirects to a random Friendica profile this node knows about
 */
class RandomProfile extends BaseModule
{
	/**
	 * @return never
	 */
	protected function content(array $request = []): string
	{
		$appHelper = DI::appHelper();

		$contact = Contact::getRandomContact();

		if (!empty($contact)) {
			$link = Contact::magicLinkByContact($contact);
			$appHelper->redirect($link);
		}

		DI::baseUrl()->redirect('profile');
	}
}
