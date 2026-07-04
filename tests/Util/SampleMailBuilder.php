<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Util;

use Friendica\Util\EMailer\MailBuilder;

class SampleMailBuilder extends MailBuilder
{
	protected string $subject = '';

	protected string $html = '';

	protected string $text = '';

	/**
	 * Adds a test message
	 *
	 * @param string $subject The subject of the email
	 * @param string $html    The preamble of the email
	 * @param string $text    The body of the email (if not set, the preamble will get used as body)
	 *
	 * @return static
	 */
	public function withMessage(string $subject, string $html, string $text)
	{
		$this->subject = $subject;
		$this->html    = $html;
		$this->text    = $text;

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	protected function getSubject()
	{
		return $this->subject;
	}

	/**
	 * @inheritDoc
	 */
	protected function getHtmlMessage()
	{
		return $this->html;
	}

	/**
	 * @inheritDoc
	 */
	protected function getPlaintextMessage()
	{
		return $this->text;
	}
}
