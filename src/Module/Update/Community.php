<?php

/* Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See update_profile.php for documentation
 */

namespace Friendica\Module\Update;

use Friendica\Content\Conversation\ConversationRenderer;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Module\Conversation\Community as CommunityModule;

/**
 * Asynchronous update module for the community page
 *
 * @package Friendica\Module\Update
 */
class Community extends CommunityModule
{
	/** @var ConversationRenderer */
	protected $conversationRenderer;

	public function __construct(ConversationRenderer $conversationRenderer)
	{
		$this->conversationRenderer = $conversationRenderer;
	}

	protected function rawContent(array $request = [])
	{
		$this->parseRequest($request);

		$o = '';
		if ($this->update || $this->force) {
			$o = $this->conversationRenderer->renderThreaded($this->getCommunityItems(), ConversationRenderer::MODE_COMMUNITY, true, ConversationRenderer::ORDER_COMMENTED, DI::userSession()->getLocalUserId(), $request);
		}

		System::htmlUpdateExit($o);
	}
}
