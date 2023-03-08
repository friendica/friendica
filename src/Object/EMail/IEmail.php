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

namespace Friendica\Object\EMail;

use Friendica\Util\Emailer;
use JsonSerializable;

/**
 * Interface for a single mail, which can be send through Emailer::send()
 *
 * @see Emailer::send()
 */
interface IEmail extends JsonSerializable
{
	/**
	 * Gets the senders name for this email
	 *
	 * @return string
	 */
	function getFromName();

	/**
	 * Gets the senders email address for this email
	 *
	 * @return string
	 */
	function getFromAddress();

	/**
	 * Gets the UID of the sender of this email
	 *
	 * @return int|null
	 */
	function getRecipientUid();

	/**
	 * Gets the reply-to address for this email
	 *
	 * @return string
	 */
	function getReplyTo();

	/**
	 * Gets the senders email address
	 *
	 * @return string
	 */
	function getToAddress();

	/**
	 * Gets the subject of this email
	 *
	 * @return string
	 */
	function getSubject();

	/**
	 * Gets the message body of this email (either html or plaintext)
	 *
	 * @param boolean $plain True, if returned as plaintext
	 *
	 * @return string
	 */
	function getMessage(bool $plain = false): string;

	/**
	 * Gets the additional mail header array
	 *
	 * @return string[][]
	 */
	function getAdditionalMailHeader();

	/**
	 * Gets the additional mail header as string - EOL separated
	 *
	 * @return string
	 */
	function getAdditionalMailHeaderString();

	/**
	 * Returns the current email with a new recipient
	 *
	 * @param string $address The email of the recipient
	 * @param int    $uid   The (optional) UID of the recipient for further infos
	 *
	 * @return static
	 */
	function withRecipient(string $address, int $uid);

	/**
	 * @param string $plaintext a new plaintext message for this email
	 * @param string $html      a new html message for this email (optional)
	 *
	 * @return static
	 */
	function withMessage(string $plaintext, string $html = null);

	/**
	 * @return string
	 */
	function __toString();
}
