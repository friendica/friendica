<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Object;

use Friendica\Object\EMail\IEmail;

/**
 * The default implementation of the IEmail interface
 *
 * Provides the possibility to reuse the email instance with new recipients (@see Email::withRecipient())
 */
class Email implements IEmail
{
	/** @var string */
	private $fromName;
	/** @var string */
	private $fromAddress;
	/** @var string */
	private $replyTo;

	/** @var string */
	private $toAddress;

	/** @var string */
	private $subject;
	/** @var string|null */
	private $msgHtml;
	/** @var string */
	private $msgText;

	/** @var string[][] */
	private $additionalMailHeader;
	/** @var int|null */
	private $toUid;

	public function __construct(string $fromName, string $fromAddress, string $replyTo, string $toAddress,
	                            string $subject, string $msgHtml, string $msgText,
	                            array $additionalMailHeader = [], int $toUid = null)
	{
		$this->fromName             = $fromName;
		$this->fromAddress          = $fromAddress;
		$this->replyTo              = $replyTo;
		$this->toAddress            = $toAddress;
		$this->subject              = $subject;
		$this->msgHtml              = $msgHtml;
		$this->msgText              = $msgText;
		$this->additionalMailHeader = $additionalMailHeader;
		$this->toUid                = $toUid;
	}

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
			return $this->msgHtml ?? '';
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
	public function withRecipient(string $address, int $uid = null)
	{
		$newEmail            = clone $this;
		$newEmail->toAddress = $address;
		$newEmail->toUid     = $uid;

		return $newEmail;
	}

	/**
	 * {@inheritDoc}
	 */
	public function withMessage(string $plaintext, string $html = null)
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
	public function __toString()
	{
		return json_encode($this->toArray());
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
