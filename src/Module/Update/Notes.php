<?php

/* Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See update_profile.php for documentation
 */

namespace Friendica\Module\Update;

use Friendica\Content\Conversation;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\Profile\Notes as ProfileModule;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Network\HTTPException\NotFoundException;

/**
 * Asynchronous update module for the notes page
 *
 * @package Friendica\Module\Update
 */
final class Notes extends ProfileModule
{
	/**
	 * Handle an asynchronous request for a single personal note and output
	 * the rendered HTML for an update (AJAX) response.
	 *
	 * @param array $request Request data, expects an 'item' key with the note id
	 * @return void Exits after sending the HTML update
	 * @throws ForbiddenException If the current user is not authenticated
	 * @throws NotFoundException If the requested item cannot be found
	 */
	protected function rawContent(array $request = [])
	{
		if (!$this->userSession->getLocalUserId()) {
			throw new ForbiddenException();
		}

		if (!isset($request['item'])) {
			throw new NotFoundException();
		}

		$condition = [
			'uid'       => $this->userSession->getLocalUserId(),
			'post-type' => Item::PT_PERSONAL_NOTE,
			'gravity'   => Item::GRAVITY_PARENT,
			'id'        => $request['item'],
		];

		$r = Post::selectThreadForUser($this->userSession->getLocalUserId(), ['uri-id'], $condition);
		if (!DBA::isResult($r)) {
			throw new NotFoundException();
		}

		System::htmlUpdateExit($this->conversation->render(Post::toArray($r), Conversation::MODE_NOTES, true));
	}
}
