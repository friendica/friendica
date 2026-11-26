<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when the contact edit page is being built.
 *
 * Can be used by addons to modify the contact record and the generated output HTML.
 */
final class EditContactFormEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.edit_contact_form';

	/**
	 * @internal
	 *
	 * @param array<string, mixed> $contact
	 */
	public function __construct(
		private readonly array $contact,
		private string $output,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array<string, mixed> */
	public function getContactArray(): array
	{
		return $this->contact;
	}

	public function getOutput(): string
	{
		return $this->output;
	}

	public function setOutput(string $output): void
	{
		$this->output = $output;
	}
}
