<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Contact;

use Friendica\Model;
use Friendica\Module\Contact;

/**
 *  Show the posts of a contact without reshares and quotes
 */
class Original extends BasePosts
{
	protected function getTab(): int
	{
		return Contact::TAB_ORIGINAL;
	}

	protected function getProfileTab(): string
	{
		return 'original';
	}

	protected function getPosts(array $contact, int $uid, array $request): string
	{
		return Model\Contact::getThreadsFromId($contact['id'], $uid, 0, 0, $request, true);
	}
}
