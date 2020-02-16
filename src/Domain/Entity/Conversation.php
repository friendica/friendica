<?php

/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU APGL version 3 or any later version
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
 * Used to check/generate entities for the Friendica codebase
 */

declare(strict_types=1);

namespace Friendica\Domain\Entity;

use Friendica\BaseEntity;

/**
 * Entity class for table conversation
 *
 * Raw data and structure information for messages
 */
class Conversation extends BaseEntity
{
	/**
	 * @var string
	 * Original URI of the item - unrelated to the table with the same name
	 */
	private $itemUri;

	/**
	 * @var string
	 * URI to which this item is a reply
	 */
	private $replyToUri = '';

	/**
	 * @var string
	 * GNU Social conversation URI
	 */
	private $conversationUri = '';

	/**
	 * @var string
	 * GNU Social conversation link
	 */
	private $conversationHref = '';

	/**
	 * @var string
	 * The protocol of the item
	 */
	private $protocol = '255';

	/**
	 * @var string
	 * Original source
	 */
	private $source;

	/**
	 * @var string
	 * Receiving date
	 */
	private $received = '0001-01-01 00:00:00';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'item-uri' => $this->itemUri,
			'reply-to-uri' => $this->replyToUri,
			'conversation-uri' => $this->conversationUri,
			'conversation-href' => $this->conversationHref,
			'protocol' => $this->protocol,
			'source' => $this->source,
			'received' => $this->received,
		];
	}

	/**
	 * @return string
	 * Get Original URI of the item - unrelated to the table with the same name
	 */
	public function getItemUri()
	{
		return $this->itemUri;
	}

	/**
	 * @return string
	 * Get URI to which this item is a reply
	 */
	public function getReplyToUri()
	{
		return $this->replyToUri;
	}

	/**
	 * @param string $replyToUri
	 * Set URI to which this item is a reply
	 */
	public function setReplyToUri(string $replyToUri)
	{
		$this->replyToUri = $replyToUri;
	}

	/**
	 * @return string
	 * Get GNU Social conversation URI
	 */
	public function getConversationUri()
	{
		return $this->conversationUri;
	}

	/**
	 * @param string $conversationUri
	 * Set GNU Social conversation URI
	 */
	public function setConversationUri(string $conversationUri)
	{
		$this->conversationUri = $conversationUri;
	}

	/**
	 * @return string
	 * Get GNU Social conversation link
	 */
	public function getConversationHref()
	{
		return $this->conversationHref;
	}

	/**
	 * @param string $conversationHref
	 * Set GNU Social conversation link
	 */
	public function setConversationHref(string $conversationHref)
	{
		$this->conversationHref = $conversationHref;
	}

	/**
	 * @return string
	 * Get The protocol of the item
	 */
	public function getProtocol()
	{
		return $this->protocol;
	}

	/**
	 * @param string $protocol
	 * Set The protocol of the item
	 */
	public function setProtocol(string $protocol)
	{
		$this->protocol = $protocol;
	}

	/**
	 * @return string
	 * Get Original source
	 */
	public function getSource()
	{
		return $this->source;
	}

	/**
	 * @param string $source
	 * Set Original source
	 */
	public function setSource(string $source)
	{
		$this->source = $source;
	}

	/**
	 * @return string
	 * Get Receiving date
	 */
	public function getReceived()
	{
		return $this->received;
	}

	/**
	 * @param string $received
	 * Set Receiving date
	 */
	public function setReceived(string $received)
	{
		$this->received = $received;
	}
}
