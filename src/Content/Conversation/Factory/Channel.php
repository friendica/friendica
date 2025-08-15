<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Conversation\Factory;

use Friendica\Content\Conversation\Collection\Timelines;
use Friendica\Content\Conversation\Entity\Channel as ChannelEntity;
use Friendica\Model\User;
use Friendica\Util\Strings;

final class Channel extends Timeline
{
	/**
	 * List of available channels
	 *
	 * @param integer $uid
	 * @return Timelines
	 */
	public function getTimelines(int $uid): Timelines
	{
		$iso639 = new \Matriphe\ISO639\ISO639();
		$native = Strings::ucFirst($iso639->nativeByCode1(User::getLanguageCode($uid)));

		$tabs = [
			new ChannelEntity(ChannelEntity::FORYOU, $this->l10n->t('For you'), $this->l10n->t('Posts from contacts you interact with and who interact with you'), 'y'),
			new ChannelEntity(ChannelEntity::DISCOVER, $this->l10n->t('Discover'), $this->l10n->t('Posts from accounts that you don\'t follow, but that you might like.'), 'o'),
			new ChannelEntity(ChannelEntity::WHATSHOT, $this->l10n->t('What\'s Hot'), $this->l10n->t('Posts with a lot of interactions'), 'h'),
			new ChannelEntity(ChannelEntity::LANGUAGE, $native, $this->l10n->t('Posts in %s', $native), 'g'),
			new ChannelEntity(ChannelEntity::FOLLOWERS, $this->l10n->t('Followers'), $this->l10n->t('Posts from your followers that you don\'t follow'), 'f'),
			new ChannelEntity(ChannelEntity::SHARERSOFSHARERS, $this->l10n->t('Sharers of sharers'), $this->l10n->t('Posts from accounts that are followed by accounts that you follow'), 'r'),
			new ChannelEntity(ChannelEntity::QUIETSHARERS, $this->l10n->t('Quiet sharers'), $this->l10n->t('Posts from accounts that you follow but who don\'t post very often'), 'q'),
			new ChannelEntity(ChannelEntity::IMAGE, $this->l10n->t('Images'), $this->l10n->t('Posts with images'), 'i'),
			new ChannelEntity(ChannelEntity::AUDIO, $this->l10n->t('Audio'), $this->l10n->t('Posts with audio'), 'd'),
			new ChannelEntity(ChannelEntity::VIDEO, $this->l10n->t('Videos'), $this->l10n->t('Posts with videos'), 'v'),
		];

		return new Timelines($tabs);
	}

	public function isTimeline(string $selectedTab): bool
	{
		return in_array($selectedTab, [ChannelEntity::WHATSHOT, ChannelEntity::FORYOU, ChannelEntity::DISCOVER, ChannelEntity::FOLLOWERS, ChannelEntity::SHARERSOFSHARERS, ChannelEntity::QUIETSHARERS, ChannelEntity::IMAGE, ChannelEntity::VIDEO, ChannelEntity::AUDIO, ChannelEntity::LANGUAGE]);
	}
}
