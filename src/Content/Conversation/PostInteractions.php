<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Content\Conversation;

use Friendica\Content\Smilies;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Verb;
use Friendica\Protocol\Activity;

/**
 * Counts and lists the actors that interacted with a post (reshares, quotes, likes, ...).
 *
 * Every interaction type is described by an entry of getTypes(), so that further types
 * (e.g. emoji reactions) only need a new entry and no changes to the callers.
 */
final readonly class PostInteractions
{
	public const RESHARES  = 'reshares';
	public const QUOTES    = 'quotes';
	public const LIKES     = 'likes';
	public const DISLIKES  = 'dislikes';
	public const REACTIONS = 'reactions';

	public function __construct(
		private L10n $l10n,
		private IManagePersonalConfigValues $pConfig,
	) {}

	/**
	 * Interaction types that are available for the given viewer
	 *
	 * @return string[]
	 */
	public function getTypes(int $uid): array
	{
		$types = [self::RESHARES, self::QUOTES, self::LIKES, self::REACTIONS];

		if (!$this->pConfig->get($uid, 'system', 'hide_dislike')) {
			$types[] = self::DISLIKES;
		}

		return $types;
	}

	public function isAvailable(string $type, int $uid): bool
	{
		return in_array($type, $this->getTypes($uid), true);
	}

	/**
	 * Summary for the links below a post: only the types that have at least one interaction
	 *
	 * @return array<int, array{type: string, total: int, label: string, url: string}>
	 */
	public function getSummary(int $uriId, string $guid, int $uid): array
	{
		$types = $this->getTypes($uid);

		// Likes, dislikes and reactions share one query
		$activities = array_intersect($types, [self::LIKES, self::DISLIKES, self::REACTIONS]) ? $this->getLikeActors($uriId, $uid) : [];

		$summary = [];
		foreach ($types as $type) {
			$total = isset($activities[$type]) ? count($activities[$type]) : $this->count($type, $uriId, $uid);
			if ($total > 0) {
				$summary[] = [
					'type'  => $type,
					'total' => $total,
					'label' => $this->getLabel($type, $total),
					'url'   => 'display/' . $guid . '/' . $type,
				];
			}
		}

		return $summary;
	}

	public function getLabel(string $type, int $total): string
	{
		return match ($type) {
			self::RESHARES  => $this->l10n->tt('reshare', 'reshares', $total),
			self::QUOTES    => $this->l10n->tt('quote', 'quotes', $total),
			self::LIKES     => $this->l10n->tt('like', 'likes', $total),
			self::DISLIKES  => $this->l10n->tt('dislike', 'dislikes', $total),
			self::REACTIONS => $this->l10n->tt('reaction', 'reactions', $total),
			default         => '',
		};
	}

	public function getTitle(string $type): string
	{
		return match ($type) {
			self::RESHARES  => $this->l10n->t('Reshared by'),
			self::QUOTES    => $this->l10n->t('Quotes'),
			self::LIKES     => $this->l10n->t('Liked by'),
			self::DISLIKES  => $this->l10n->t('Disliked by'),
			self::REACTIONS => $this->l10n->t('Reacted by'),
			default         => '',
		};
	}

	/**
	 * Number of entries of the list: quoting posts, or actors (each actor is counted once)
	 */
	public function count(string $type, int $uriId, int $uid): int
	{
		if ($type === self::QUOTES) {
			return Post::countPosts($this->getQuoteCondition($uriId, $uid));
		}

		return count($this->getActors($type, $uriId, $uid));
	}

	/**
	 * One page of the actors of an interaction type (public contact ids), most recent first.
	 * Contacts the viewer blocked or ignored are not part of the list.
	 *
	 * @return array<int, string> Public contact id => emoji of the reaction(s), empty for other types
	 */
	public function getActorIds(string $type, int $uriId, int $uid, int $start, int $limit): array
	{
		return array_slice($this->getActors($type, $uriId, $uid), $start, $limit, true);
	}

	/**
	 * One page of the posts that quote the given post, most recent first
	 *
	 * @return int[]
	 */
	public function getQuoteUriIds(int $uriId, int $uid, int $start, int $limit): array
	{
		$rows = Post::selectPosts(['uri-id'], $this->getQuoteCondition($uriId, $uid), ['order' => ['received' => true], 'limit' => [$start, $limit]]);

		return array_column(Post::toArray($rows), 'uri-id');
	}

	/**
	 * All actors of an interaction type, most recent first. Every actor is listed once.
	 *
	 * @return array<int, string> Public contact id => emoji of the reaction(s), empty for other types
	 */
	private function getActors(string $type, int $uriId, int $uid): array
	{
		if ($type !== self::RESHARES) {
			return $this->getLikeActors($uriId, $uid)[$type] ?? [];
		}

		$actors = [];
		foreach (array_column(Post::selectToArray(['author-id'], $this->getActivityCondition($type, $uriId, $uid), ['order' => ['received' => true]]), 'author-id') as $authorId) {
			$actors[$authorId] = '';
		}

		return $actors;
	}

	/**
	 * Actors of the likes, dislikes and reactions, from a single query.
	 *
	 * The decision whether a like or dislike is an emoji reaction can't be made in SQL (see isEmojiReaction),
	 * so the split is done on the result. This way the lists and the counts always show the same numbers.
	 *
	 * @return array<string, array<int, string>> Actors per interaction type (public contact id => emoji, empty for likes and dislikes)
	 */
	private function getLikeActors(int $uriId, int $uid): array
	{
		$rows = Post::select(['author-id', 'vid', 'body'], $this->getActivityCondition(self::REACTIONS, $uriId, $uid), ['order' => ['received' => true]]);

		$emojiReact = Verb::getID(Activity::EMOJIREACT);
		$dislike    = Verb::getID(Activity::DISLIKE);

		$actors = [self::LIKES => [], self::DISLIKES => [], self::REACTIONS => []];
		while ($row = Post::fetch($rows)) {
			$emoji = $row['vid'] === $emojiReact ? (string) $row['body'] : ($this->isEmojiReaction((string) $row['body']) ? (string) $row['body'] : '');

			if ($emoji !== '') {
				$actors[self::REACTIONS][$row['author-id']] = ($actors[self::REACTIONS][$row['author-id']] ?? '') . $emoji;
			} else {
				$actors[$row['vid'] === $dislike ? self::DISLIKES : self::LIKES][$row['author-id']] = '';
			}
		}
		DBA::close($rows);

		return $actors;
	}

	/**
	 * Likes and dislikes that carry a single emoji as content are emoji reactions (see Model\Post\Counts)
	 */
	private function isEmojiReaction(string $body): bool
	{
		return mb_strlen($body) === 1 && Smilies::isEmojiPost($body);
	}

	private function getActivityCondition(string $type, int $uriId, int $uid): array
	{
		$condition = [
			'thr-parent-id' => $uriId,
			'gravity'       => Item::GRAVITY_ACTIVITY,
			'deleted'       => false,
		];

		$condition = DBA::mergeConditions($condition, ["((`uid` = ? AND `global`) OR (`uid` = ? AND NOT `global`))", 0, $uid]);
		$condition = DBA::mergeConditions($condition, $this->getAuthorCondition($uid));

		$like    = Verb::getID(Activity::LIKE);
		$dislike = Verb::getID(Activity::DISLIKE);

		return match ($type) {
			self::RESHARES  => DBA::mergeConditions($condition, ['vid' => Verb::getID(Activity::ANNOUNCE)]),
			self::LIKES     => DBA::mergeConditions($condition, ['vid' => $like]),
			self::DISLIKES  => DBA::mergeConditions($condition, ['vid' => $dislike]),
			self::REACTIONS => DBA::mergeConditions($condition, ['vid' => [Verb::getID(Activity::EMOJIREACT), $like, $dislike]]),
			default         => throw new \InvalidArgumentException('Unsupported interaction type ' . $type),
		};
	}

	/**
	 * Quotes with and without text: reshares from Diaspora are stored as quotes as well.
	 * The deletion state is not stored in post-quote, but in the post of the quoting item.
	 * The same visibility is used for the counter at the post button (see ConversationDataProvider::getQuoteShares)
	 */
	private function getQuoteCondition(int $uriId, int $uid): array
	{
		$condition = [
			'quote-uri-id' => $uriId,
			'deleted'      => false,
			'private'      => [Item::PUBLIC, Item::UNLISTED],
		];

		return DBA::mergeConditions($condition, $this->getAuthorCondition($uid));
	}

	/**
	 * Leave out the authors that are blocked by the server or blocked or ignored by the user
	 */
	private function getAuthorCondition(int $uid): array
	{
		$condition = ["NOT `author-blocked`"];

		if ($uid) {
			$condition[0] .= " AND NOT `author-id` IN (SELECT `cid` FROM `user-contact` WHERE `uid` = ? AND (`blocked` OR `ignored`))";
			$condition[] = $uid;
		}

		return $condition;
	}
}
