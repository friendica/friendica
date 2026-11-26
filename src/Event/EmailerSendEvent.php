<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired just before an email is sent, to allow addons to inspect the email data or report it as sent.
 */
final class EmailerSendEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.emailer_send';

	/**
	 * @internal
	 */
	public function __construct(
		private readonly string $to,
		private readonly string $subject,
		private readonly string $body,
		private readonly string $headers,
		private readonly ?string $parameters,
		private bool $sent = false,
	) {
		parent::__construct(self::NAME);
	}

	public function getToAddress(): string
	{
		return $this->to;
	}

	public function getSubject(): string
	{
		return $this->subject;
	}

	public function getBody(): string
	{
		return $this->body;
	}

	public function getHeaders(): string
	{
		return $this->headers;
	}

	public function getParameters(): ?string
	{
		return $this->parameters;
	}

	public function isSent(): bool
	{
		return $this->sent;
	}

	public function setSent(bool $sent): void
	{
		$this->sent = $sent;
	}
}
