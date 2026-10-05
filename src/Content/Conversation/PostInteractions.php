<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Conversation;

use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
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
	 * @return array<int, array{type: string, total: int, label: string, url: string}> label is HTML: the count is wrapped in <strong>
	 */
	public function getSummary(int $uriId, string $guid, int $uid): array
	{
		$summary = [];
		foreach ($this->getTypes($uid) as $type) {
			$total = $this->count($type, $uriId, $uid);
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
			self::RESHARES  => $this->l10n->tt('<strong>%d</strong> Reshare', '<strong>%d</strong> Reshares', $total),
			self::QUOTES    => $this->l10n->tt('<strong>%d</strong> Quote', '<strong>%d</strong> Quotes', $total),
			self::LIKES     => $this->l10n->tt('<strong>%d</strong> Like', '<strong>%d</strong> Likes', $total),
			self::DISLIKES  => $this->l10n->tt('<strong>%d</strong> Dislike', '<strong>%d</strong> Dislikes', $total),
			self::REACTIONS => $this->l10n->tt('<strong>%d</strong> Reaction', '<strong>%d</strong> Reactions', $total),
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

	public function count(string $type, int $uriId, int $uid): int
	{
		if ($type === self::QUOTES) {
			return Post::countPosts($this->getQuoteCondition($uriId));
		}

		return Post::count($this->getActivityCondition($type, $uriId, $uid));
	}

	/**
	 * One page of the actors of an interaction type (public contact ids), most recent first.
	 * Contacts the viewer blocked or ignored are left out of the page, so a page can be shorter than the limit.
	 *
	 * @return array<int, string> Public contact id => emoji of the reaction(s), empty for other types
	 */
	public function getActorIds(string $type, int $uriId, int $uid, int $start, int $limit): array
	{
		$params = ['order' => ['uri-id' => true], 'limit' => [$start, $limit]];

		if ($type === self::QUOTES) {
			$rows = Post::selectPosts(['author-id', 'body'], $this->getQuoteCondition($uriId), $params);
		} else {
			$rows = Post::select(['author-id', 'body'], $this->getActivityCondition($type, $uriId, $uid), $params);
		}

		$ids = [];
		while ($row = Post::fetch($rows)) {
			$ids[$row['author-id']] = ($ids[$row['author-id']] ?? '') . ($type === self::REACTIONS ? $row['body'] : '');
		}
		DBA::close($rows);

		if ($uid) {
			$ids = array_filter($ids, fn (int $cid): bool => !Contact\User::isBlocked($cid, $uid) && !Contact\User::isIgnored($cid, $uid), ARRAY_FILTER_USE_KEY);
		}

		return $ids;
	}

	/**
	 * One page of the posts that quote the given post, most recent first
	 *
	 * @return int[]
	 */
	public function getQuoteUriIds(int $uriId, int $start, int $limit): array
	{
		$rows = Post::selectPosts(['uri-id'], $this->getQuoteCondition($uriId), ['order' => ['uri-id' => true], 'limit' => [$start, $limit]]);

		return array_column(Post::toArray($rows), 'uri-id');
	}

	/**
	 * Likes and dislikes that carry a single character as content are emoji reactions,
	 * like it is done for the emoji display and the counts (see Model\Item and Model\Post\Counts).
	 */
	private function getActivityCondition(string $type, int $uriId, int $uid): array
	{
		$condition = [
			'thr-parent-id' => $uriId,
			'gravity'       => Item::GRAVITY_ACTIVITY,
			'deleted'       => false,
		];

		$condition = DBA::mergeConditions($condition, ["((`uid` = ? AND `global`) OR (`uid` = ? AND NOT `global`))", 0, $uid]);

		$like    = Verb::getID(Activity::LIKE);
		$dislike = Verb::getID(Activity::DISLIKE);

		return match ($type) {
			self::RESHARES  => DBA::mergeConditions($condition, ['vid' => Verb::getID(Activity::ANNOUNCE)]),
			self::LIKES     => DBA::mergeConditions($condition, ['vid' => $like], ["COALESCE(CHAR_LENGTH(`body`), 0) != 1"]),
			self::DISLIKES  => DBA::mergeConditions($condition, ['vid' => $dislike], ["COALESCE(CHAR_LENGTH(`body`), 0) != 1"]),
			self::REACTIONS => DBA::mergeConditions($condition, ["(`vid` = ? OR (`vid` IN (?, ?) AND CHAR_LENGTH(`body`) = 1))", Verb::getID(Activity::EMOJIREACT), $like, $dislike]),
			default         => throw new \InvalidArgumentException('Unsupported interaction type ' . $type),
		};
	}

	/**
	 * Quotes with and without text: reshares from Diaspora are stored as quotes as well.
	 * The deletion state is not stored in post-quote, but in the post of the quoting item.
	 */
	private function getQuoteCondition(int $uriId): array
	{
		return [
			'quote-uri-id' => $uriId,
			'deleted'      => false,
			'private'      => [Item::PUBLIC, Item::UNLISTED],
		];
	}
}
