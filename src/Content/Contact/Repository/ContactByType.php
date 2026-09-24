<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Content\Contact\Repository;

use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\Database\DBA;

/**
 * Read repository for user contacts grouped by contact type.
 */
class ContactByType
{
	private const FIELDS = ['id', 'pid', 'uri-id', 'url', 'addr', 'alias', 'name', 'micro', 'thumb', 'avatar', 'network', 'uid', 'about', 'gsid'];

	public function __construct(private readonly Database $database) {}

	/**
	 * Selects visible user contacts for the provided contact types.
	 *
	 * @return array<int, array{url: string, alias: string, name: string, id: int, pid: int, micro: string, thumb: string, network: string, about: string}>
	 * @throws \Exception
	 */
	public function selectForUser(int $uid, array $contactTypes, bool $lastItem, bool $showHidden = true, bool $showPrivate = false): array
	{
		if (empty($contactTypes)) {
			return [];
		}

		$params = ['order' => $lastItem ? ['last-item' => true] : ['name']];

		$condition = [
			'contact-type' => $contactTypes,
			'network'      => [Protocol::DFRN, Protocol::ACTIVITYPUB],
			'uid'          => $uid,
			'blocked'      => false,
			'pending'      => false,
			'archive'      => false,
		];

		$condition = DBA::mergeConditions($condition, ["`platform` NOT IN (?, ?)", 'peertube', 'wordpress']);

		if (!$showPrivate) {
			$condition = DBA::mergeConditions($condition, ['manually-approve' => false]);
		}

		if (!$showHidden) {
			$condition = DBA::mergeConditions($condition, ['hidden' => false]);
		}

		$contacts = $this->database->selectToArray('account-user-view', self::FIELDS, $condition, $params);
		foreach ($contacts as $key => $contact) {
			$contacts[$key] = [
				'url'     => $contact['url'],
				'addr'    => $contact['addr'],
				'alias'   => $contact['alias'],
				'name'    => $contact['name'],
				'id'      => $contact['id'],
				'pid'     => $contact['pid'],
				'micro'   => $contact['micro'],
				'thumb'   => $contact['thumb'],
				'network' => $contact['network'],
				'about'   => $contact['about'] ?? '',
				'gsid'    => $contact['gsid'],
			];
		}

		return $contacts;
	}

	/**
	 * Counts unseen posts for the provided contact types.
	 *
	 * @return array<int, array{id: int, name: string, count: int}>
	 * @throws \Exception
	 */
	public function countUnseenItems(int $uid, array $contactTypes): array
	{
		if (empty($contactTypes)) {
			return [];
		}

		$typePlaceholders = implode(', ', array_fill(0, count($contactTypes), '?'));
		$parameters       = array_merge(
			[$uid, Protocol::DFRN, Protocol::ACTIVITYPUB],
			$contactTypes,
			[$uid],
		);

		$stmtContacts = $this->database->p(
			"SELECT `contact`.`id`, `contact`.`name`, COUNT(*) AS `count` FROM `post-user-view`
				INNER JOIN `contact` ON `post-user-view`.`contact-id` = `contact`.`id`
				WHERE `post-user-view`.`uid` = ? AND `post-user-view`.`visible` AND NOT `post-user-view`.`deleted` AND `post-user-view`.`unseen`
				AND `contact`.`network` IN (?, ?) AND `contact`.`contact-type` IN (" . $typePlaceholders . ")
				AND NOT `contact`.`blocked` AND NOT `contact`.`hidden`
				AND NOT `contact`.`pending` AND NOT `contact`.`archive`
				AND `contact`.`uid` = ?
				GROUP BY `contact`.`id`",
			...$parameters,
		);

		return $this->database->toArray($stmtContacts);
	}
}
