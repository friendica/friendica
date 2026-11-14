<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Contact;

use Friendica\Model;
use Friendica\Module\Contact;

/**
 *  Show the posts and comments of a contact that quote other posts
 */
class Quotes extends BasePosts
{
	protected function getTab(): int
	{
		return Contact::TAB_QUOTES;
	}

	protected function getProfileTab(): string
	{
		return 'quotes';
	}

	protected function getPosts(array $contact, int $uid, array $request): string
	{
		return Model\Contact::getQuotesFromId($contact['id'], $uid, $request);
	}
}
