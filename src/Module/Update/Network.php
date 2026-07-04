<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Update;

use Friendica\Content\Conversation\ConversationRenderer;
use Friendica\Core\System;
use Friendica\Module\Conversation\Network as NetworkModule;

class Network extends NetworkModule
{
	protected function rawContent(array $request = [])
	{
		if (!isset($request['p']) || !isset($request['item'])) {
			System::exit();
		}

		$this->parseRequest($request);

		$o = '';

		if (!$this->update && !$this->force) {
			System::htmlUpdateExit($o);
		}

		try {
			if ($this->channel->isTimeline($this->selectedTab) || $this->userDefinedChannel->isTimeline($this->selectedTab, $this->session->getLocalUserId())) {
				$items = $this->getChannelItems($request, $this->session->getLocalUserId());
			} elseif ($this->community->isTimeline($this->selectedTab)) {
				$items = $this->getCommunityItems();
			} else {
				$items = $this->getItems();
			}
		} catch (\Exception $e) {
			$this->logger->error('Exception when fetching items', ['code' => $e->getCode(), 'message' => $e->getMessage()]);
			$items = [];
		}

		$o = $this->conversationRenderer->renderThreaded($items, ConversationRenderer::MODE_NETWORK, true, $this->getOrder(), $this->session->getLocalUserId(), $request);

		System::htmlUpdateExit($o);
	}
}
