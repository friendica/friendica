<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Content\Conversation\Factory;

use Friendica\Content\Conversation\Collection\Timelines;
use Friendica\Content\Conversation\Entity\Channel as ChannelEntity;
use Friendica\Model\User;

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
		$iso639 = new \Matriphe\ISO639\ISO639;
		$native = $iso639->nativeByCode1(User::getLanguageCode($uid));

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
