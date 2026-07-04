<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object;

use Friendica\Object\EMail\IEmail;

/**
 * The default implementation of the IEmail interface
 *
 * Provides the possibility to reuse the email instance with new recipients (@see Email::withRecipient())
 */
class Email implements IEmail
{
	public function __construct(
		private string $fromName,
		private string $fromAddress,
		private string $replyTo,
		private string $toAddress,
		private string $subject,
		private string $msgHtml,
		private string $msgText,
		/** @var string[][] */
		private array $additionalMailHeader = [],
		private ?int $toUid = null,
	) {}

	/**
	 * {@inheritDoc}
	 */
	public function getFromName()
	{
		return $this->fromName;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFromAddress()
	{
		return $this->fromAddress;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getReplyTo()
	{
		return $this->replyTo;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getToAddress()
	{
		return $this->toAddress;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getSubject()
	{
		return $this->subject;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getMessage(bool $plain = false): string
	{
		if ($plain) {
			return $this->msgText;
		} else {
			return $this->msgHtml;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAdditionalMailHeader()
	{
		return $this->additionalMailHeader;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAdditionalMailHeaderString()
	{
		$headerString = '';

		foreach ($this->additionalMailHeader as $name => $values) {
			if (!is_array($values)) {
				$values = [$values];
			}

			foreach ($values as $value) {
				$headerString .= "$name: $value\r\n";
			}
		}

		return $headerString;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRecipientUid()
	{
		return $this->toUid;
	}

	/**
	 * {@inheritDoc}
	 */
	public function withRecipient(string $address, ?int $uid = null)
	{
		$newEmail            = clone $this;
		$newEmail->toAddress = $address;
		$newEmail->toUid     = $uid;

		return $newEmail;
	}

	/**
	 * {@inheritDoc}
	 */
	public function withMessage(string $plaintext, ?string $html = null)
	{
		$newMail          = clone $this;
		$newMail->msgText = $plaintext;
		$newMail->msgHtml = $html;

		return $newMail;
	}

	/**
	 * Returns the properties of the email as an array
	 *
	 * @return array
	 */
	private function toArray()
	{
		return get_object_vars($this);
	}

	/**
	 * @inheritDoc
	 */
	public function __toString(): string
	{
		return (string) json_encode($this->toArray());
	}

	/**
	 * @inheritDoc
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize()
	{
		return $this->toArray();
	}
}
