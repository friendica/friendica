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

namespace Friendica\Test\Util;

use Friendica\Util\EMailer\MailBuilder;

class SampleMailBuilder extends MailBuilder
{
	/** @var string */
	protected $subject;
	/** @var string */
	protected $html;
	/** @var string */
	protected $text;

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
