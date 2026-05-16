<?php

declare(strict_types=1);

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Conversation\Entity;

/**
 * Activity entity representing the activity table.
 */
final class Activity
{
	public int $uid;
	public string $network;
	public array $languages;
	public int $cid;
	public string $expires;
	public int $medianComments;
	public int $medianActivities;
	public int $medianViews;
	public int $medianThreadScore;
	public int $medianPostScore;

	/**
	 * Activity constructor.
	 *
	 * @param int $uid
	 * @param string $network
	 * @param array $languages
	 * @param int $cid
	 * @param string $expires
	 * @param int $medianComments
	 * @param int $medianActivities
	 * @param int $medianViews
	 * @param int $medianThreadScore
	 * @param int $medianPostScore
	 */
	public function __construct(
		int $uid,
		string $network,
		array $languages,
		int $cid,
		string $expires,
		int $medianComments,
		int $medianActivities,
		int $medianViews,
		int $medianThreadScore,
		int $medianPostScore
	) {
		$this->uid               = $uid;
		$this->network           = $network;
		$this->languages         = $languages;
		$this->cid               = $cid;
		$this->expires           = $expires;
		$this->medianComments    = $medianComments;
		$this->medianActivities  = $medianActivities;
		$this->medianViews       = $medianViews;
		$this->medianThreadScore = $medianThreadScore;
		$this->medianPostScore   = $medianPostScore;
	}

	/**
	 * Create an Activity instance from an array.
	 *
	 * @param array $data
	 * @return self
	 */
	public static function fromArray(array $data): self
	{
		$languages = [];
		if (isset($data['languages'])) {
			$decoded = json_decode($data['languages'], true);
			if (is_array($decoded)) {
				$languages = $decoded;
			}
		}

		return new self(
			$data['uid'],
			$data['network'],
			$languages,
			$data['cid'],
			$data['expires'],
			$data['median-comments'],
			$data['median-activities'],
			$data['median-views'],
			$data['median-thread-score'],
			$data['median-post-score'],
		);
	}

	/**
	 * Convert the Activity to an array.
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		return [
			'uid'                 => $this->uid,
			'network'             => $this->network,
			'languages'           => $this->languages,
			'cid'                 => $this->cid,
			'expires'             => $this->expires,
			'median-comments'     => $this->medianComments,
			'median-activities'   => $this->medianActivities,
			'median-views'        => $this->medianViews,
			'median-thread-score' => $this->medianThreadScore,
			'median-post-score'   => $this->medianPostScore,
		];
	}
}
