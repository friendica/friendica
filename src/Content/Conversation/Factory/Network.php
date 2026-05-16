<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Conversation\Factory;

use Friendica\Content\Conversation\Collection\Timelines;
use Friendica\Content\Conversation\Entity\Network as NetworkEntity;

final class Network extends Timeline
{
	/**
	 * List of available network timelines
	 *
	 * @param string $command
	 * @return Timelines
	 */
	public function getTimelines(string $command): Timelines
	{
		$tabs = [
			new NetworkEntity(NetworkEntity::COMMENTED, $this->l10n->t('Latest Activity'), $this->l10n->t('Sort by latest activity'), 'e', $command . '?' . http_build_query(['order' => 'commented'])),
			new NetworkEntity(NetworkEntity::RECEIVED, $this->l10n->t('Latest Posts'), $this->l10n->t('Sort by post received date'), 't', $command . '?' . http_build_query(['order' => 'received'])),
			new NetworkEntity(NetworkEntity::CREATED, $this->l10n->t('Latest Creation'), $this->l10n->t('Sort by post creation date'), 'q', $command . '?' . http_build_query(['order' => 'created'])),
			new NetworkEntity(NetworkEntity::MENTION, $this->l10n->t('Personal'), $this->l10n->t('Posts that mention or involve you'), 'r', $command . '?' . http_build_query(['mention' => true])),
			new NetworkEntity(NetworkEntity::STAR, $this->l10n->t('Bookmarked'), $this->l10n->t('Favourite Posts'), 'm', $command . '?' . http_build_query(['star' => true])),
		];
		return new Timelines($tabs);
	}

	public function isTimeline(string $selectedTab): bool
	{
		return in_array($selectedTab, [NetworkEntity::COMMENTED, NetworkEntity::RECEIVED, NetworkEntity::CREATED, NetworkEntity::MENTION, NetworkEntity::STAR]);
	}
}
