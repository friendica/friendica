<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when a message is encapsulated with an unknown algorithm, to allow addons to provide the encapsulated result.
 */
final class OtherEncapsulateEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.other_encapsulate';

	private string $result;

	/**
	 * @internal
	 */
	public function __construct(
		private readonly string $data,
		private readonly string $pubkey,
		private readonly string $alg,
	) {
		$this->result = $data;

		parent::__construct(self::NAME);
	}

	public function getData(): string
	{
		return $this->data;
	}

	public function getPubkey(): string
	{
		return $this->pubkey;
	}

	public function getAlg(): string
	{
		return $this->alg;
	}

	public function getResult(): string
	{
		return $this->result;
	}

	public function setResult(string $result): void
	{
		$this->result = $result;
	}
}
