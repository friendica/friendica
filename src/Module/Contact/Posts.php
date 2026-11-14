<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Contact;

use Friendica\Model;
use Friendica\Module\Contact;

/**
 *  Show a contact posts and comments
 */
class Posts extends BasePosts
{
	protected function getTab(): int
	{
		return Contact::TAB_POSTS;
	}

	protected function getProfileTab(): string
	{
		return 'posts';
	}

	protected function getPosts(array $contact, int $uid, array $request): string
	{
		return Model\Contact::getPostsFromId($contact['id'], $uid, false, $request);
	}
}
