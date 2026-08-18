<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when a message is unencapsulated with an unknown algorithm, to allow addons to provide the unencapsulated result.
 */
final class OtherUnencapsulateEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.other_unencapsulate';

	private array $result;

	/**
	 * @internal
	 */
	public function __construct(
		private readonly array $data,
		private readonly string $prvkey,
		private readonly string $alg,
	) {
		$this->result = $data;

		parent::__construct(self::NAME);
	}

	public function getDataArray(): array
	{
		return $this->data;
	}

	public function getPrivateKey(): string
	{
		return $this->prvkey;
	}

	public function getAlg(): string
	{
		return $this->alg;
	}

	public function getResultArray(): array
	{
		return $this->result;
	}

	public function setResultArray(array $result): void
	{
		$this->result = $result;
	}
}
