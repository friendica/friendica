<?php

declare(strict_types=1);

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Conversation;

use Friendica\Content\Conversation\Repository\InteractionCounts;
use Friendica\Model\Contact;
use Friendica\Model\Item as ItemModel;
use Friendica\Model\Post;
use Friendica\Model\Verb;
use Friendica\Protocol\Activity;

/**
 * Counts and names of the interactions with posts (reshares, likes, quotes, emoji reactions, comments) as they are shown to a viewer.
 *
 * The numbers are used by the web interface and by the API, so that both show the same.
 * The public counts come from the "post-counts" table, the interactions that are only visible
 * for the viewer (private ones) are added from the activities of the post.
 */
final readonly class PostInteractionCounts
{
	public function __construct(private InteractionCounts $repository) {}

	/**
	 * Totals of reshares, likes and dislikes of a single post, as they are visible to the viewer
	 *
	 * @param int $uriId       URI-ID of the post
	 * @param int $parentUriId URI-ID of the thread the post belongs to
	 * @param int $uid         The user ID of the viewer
	 * @return array{reshares: int, likes: int, dislikes: int} Likes only contain the plain ones, emoji reactions are not part of them
	 */
	public function getTotals(int $uriId, int $parentUriId, int $uid): array
	{
		$emojis = $this->getEmojis([$parentUriId], $uid, [$uriId])[$uriId] ?? [];

		return [
			'reshares' => (int) ($emojis[Activity::ANNOUNCE]['total'] ?? 0),
			'likes'    => (int) ($emojis[Activity::LIKE]['total'] ?? 0),
			'dislikes' => (int) ($emojis[Activity::DISLIKE]['total'] ?? 0),
		];
	}

	/**
	 * Get emoji reactions for the given URI IDs.
	 *
	 * @param array<int, int> $uriIds The URI IDs to get emojis for
	 * @param int $uid The user ID of the viewer
	 * @param array<int, int> $targetUriIds Restrict the reactions to these reacted posts, empty for all posts of the threads
	 * @return array<int, array> The emoji reactions data
	 */
	public function getEmojis(array $uriIds, int $uid, array $targetUriIds = []): array
	{
		$targetCondition = $targetUriIds ? ['thr-parent-id' => $targetUriIds] : [];
		$countCondition  = $targetUriIds ? ['uri-id' => $targetUriIds] : [];

		$emojis = [];
		foreach (Post\Counts::get(array_merge(['parent-uri-id' => $uriIds], $countCondition)) as $count) {
			$emojis[$count['uri-id']][$count['reaction']]['emoji'] = $count['reaction'];
			$emojis[$count['uri-id']][$count['reaction']]['verb']  = Verb::getByID($count['vid']);
			$emojis[$count['uri-id']][$count['reaction']]['total'] = $count['count'];
			$emojis[$count['uri-id']][$count['reaction']]['count'] = 0;
			$emojis[$count['uri-id']][$count['reaction']]['title'] = [];
			$emojis[$count['uri-id']][$count['reaction']]['self']  = false;
		}

		$verbs = [
			Activity::LIKE,
			Activity::DISLIKE,
			Activity::ATTEND,
			Activity::ATTENDMAYBE,
			Activity::ATTENDNO,
			Activity::ANNOUNCE,
			Activity::VIEW,
			Activity::READ,
			Activity::EMOJIREACT,
			Activity::POST,
		];

		$separator = $this->repository->getSeparator();
		foreach ($this->repository->findActivities($uriIds, $uid, (int) Contact::getPublicIdByUserId($uid), $verbs, $targetUriIds) as $row) {
			$emoji = $row['gravity'] === ItemModel::GRAVITY_ACTIVITY ? ($row['body'] ?: $row['verb']) : '';
			if (!isset($emojis[$row['thr-parent-id']][$emoji]['title'])) {
				continue;
			}

			if (($emoji === Activity::VIEW) && ($row['private'] === ItemModel::PRIVATE)) {
				continue;
			}

			$names                                          = explode($separator, (string) $row['title']);
			$emojis[$row['thr-parent-id']][$emoji]['title'] = array_unique(array_merge($emojis[$row['thr-parent-id']][$emoji]['title'], $names));
			if ($row['private'] === ItemModel::PRIVATE) {
				$emojis[$row['thr-parent-id']][$emoji]['total'] += count($names);
			}
			$emojis[$row['thr-parent-id']][$emoji]['count'] += count($names);
			if ($row['self']) {
				$emojis[$row['thr-parent-id']][$emoji]['self'] = true;
			}
		}

		foreach ($emojis as $uriId => $row) {
			foreach ($row as $emoji => $value) {
				if (($value['count'] < $value['total']) && ($value['count'] < 50)) {
					$emojis[$uriId][$emoji]['total'] = $value['count'];
				}
				if ($emojis[$uriId][$emoji]['total'] === 0) {
					unset($emojis[$uriId][$emoji]);
				}
			}
		}

		return $emojis;
	}

	/**
	 * Get quote shares for the given URI IDs.
	 *
	 * Contacts the viewer blocked or ignored are left out, like in the interaction lists (see PostInteractions)
	 *
	 * @param array<int, int> $uriIds The URI IDs to get quote shares for
	 * @param int $uid The user ID of the viewer
	 * @return array<int, array> The quote shares data
	 */
	public function getQuoteShares(array $uriIds, int $uid): array
	{
		$separator = $this->repository->getSeparator();
		$quotes    = [];
		foreach ($this->repository->findQuoteShares($uriIds, $uid) as $row) {
			$quotes[$row['quote-uri-id']]['total'] = $row['total'];
			$quotes[$row['quote-uri-id']]['title'] = array_unique(explode($separator, (string) $row['title']));
		}

		return $quotes;
	}

	/**
	 * Get comment counts for the given URI IDs.
	 *
	 * @param array<int, int> $uriIds The URI IDs to get counts for
	 * @return array<int, int> The comment counts per URI ID
	 */
	public function getCounts(array $uriIds): array
	{
		$counts = [];
		foreach (Post\Counts::get(['parent-uri-id' => $uriIds, 'vid' => Verb::getID(Activity::POST)]) as $count) {
			$counts[$count['parent-uri-id']] = ($counts[$count['parent-uri-id']] ?? 0) + $count['count'];
		}

		return $counts;
	}
}
