<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Moderation\Factory;

use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Core\System;
use Friendica\Model\Contact;
use Friendica\Moderation\Collection;
use Friendica\Moderation\Entity;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

class Report extends \Friendica\BaseFactory implements ICanCreateFromTableRow
{
	public function __construct(
		LoggerInterface $logger,
		private readonly ClockInterface $clock,
	) {
		parent::__construct($logger);
	}

	/**
	 * @param array                        $row   `report` table row
	 * @param Collection\Report\Posts|null $posts List of posts attached to the report
	 * @param Collection\Report\Rules|null $rules List of rules from the terms of service, see System::getRules()
	 * @return Entity\Report
	 * @throws \Exception
	 */
	public function createFromTableRow(array $row, ?Collection\Report\Posts $posts = null, ?Collection\Report\Rules $rules = null): Entity\Report
	{
		return new Entity\Report(
			$row['reporter-id'],
			$row['cid'],
			$row['gsid'],
			new \DateTimeImmutable($row['created'], new \DateTimeZone('UTC')),
			$row['category-id'],
			$row['uid'],
			$row['comment'],
			$row['forward'],
			$posts ?? new Collection\Report\Posts(),
			$rules ?? new Collection\Report\Rules(),
			$row['public-remarks'],
			$row['private-remarks'],
			$row['edited'] ? new \DateTimeImmutable($row['edited'], new \DateTimeZone('UTC')) : null,
			$row['status'],
			$row['resolution'],
			$row['assigned-uid'],
			$row['last-editor-uid'],
			$row['id'],
		);
	}

	/**
	 * Creates a Report entity from a Mastodon API /reports request
	 *
	 * @param array  $rules      Line-number indexed node rules array, see System::getRules(true)
	 * @param int    $reporterId
	 * @param int    $cid
	 * @param int    $gsid
	 * @param string $comment
	 * @param string $category
	 * @param bool   $forward
	 * @param array  $postUriIds
	 * @param array  $ruleIds
	 * @param ?int   $uid
	 * @return Entity\Report
	 * @see \Friendica\Module\Api\Mastodon\Reports::post()
	 */
	public function createFromReportsRequest(array $rules, int $reporterId, int $cid, int $gsid, string $comment = '', string $category = '', bool $forward = false, array $postUriIds = [], array $ruleIds = [], ?int $uid = null): Entity\Report
	{
		if (count($ruleIds)) {
			$categoryId = Entity\Report::CATEGORY_VIOLATION;
		} elseif ($category == 'spam') {
			$categoryId = Entity\Report::CATEGORY_SPAM;
		} else {
			$categoryId = Entity\Report::CATEGORY_OTHER;
		}

		return new Entity\Report(
			$reporterId,
			$cid,
			$gsid,
			$this->clock->now(),
			$categoryId,
			$uid,
			$comment,
			$forward,
			new Collection\Report\Posts(array_map(function ($uriId) {
				return new Entity\Report\Post($uriId);
			}, $postUriIds)),
			new Collection\Report\Rules(array_map(function ($lineId) use ($rules) {
				return new Entity\Report\Rule($lineId, $rules[$lineId] ?? '');
			}, $ruleIds)),
		);
	}

	public function createFromForm(array $rules, int $cid, int $reporterId, int $categoryId, array $ruleIds, string $comment, array $uriIds, bool $forward): Entity\Report
	{
		$contact = Contact::getById($cid, ['gsid']);
		if (!$contact) {
			throw new \InvalidArgumentException('Contact with id: ' . $cid . ' not found');
		}

		if (!in_array($categoryId, Entity\Report::CATEGORIES)) {
			throw new \OutOfBoundsException('Category with id: ' . $categoryId . ' not found in set: [' . implode(', ', Entity\Report::CATEGORIES) . ']');
		}

		return new Entity\Report(
			Contact::getPublicIdByUserId($reporterId),
			$cid,
			$contact['gsid'],
			$this->clock->now(),
			$categoryId,
			$reporterId,
			$comment,
			$forward,
			new Collection\Report\Posts(array_map(function ($uriId) {
				return new Entity\Report\Post($uriId);
			}, $uriIds)),
			new Collection\Report\Rules(array_map(function ($lineId) use ($rules) {
				return new Entity\Report\Rule($lineId, $rules[$lineId] ?? '');
			}, $ruleIds)),
		);
	}
}
