<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Contact;

use Friendica\Model;
use Friendica\Module\Contact;

/**
 *  Show the posts a contact has reshared
 */
class Reshares extends BasePosts
{
	protected function getTab(): int
	{
		return Contact::TAB_RESHARES;
	}

	protected function getProfileTab(): string
	{
		return 'reshares';
	}

	protected function getPosts(array $contact, int $uid, array $request): string
	{
		return Model\Contact::getResharesFromId($contact['id'], $uid, $request);
	}
}
