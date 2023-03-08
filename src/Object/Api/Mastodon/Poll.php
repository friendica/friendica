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

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;
use Friendica\Util\DateTimeFormat;

/**
 * Class Poll
 *
 * @see https://docs.joinmastodon.org/entities/poll/
 */
class Poll extends BaseDataTransferObject
{
	/** @var string */
	protected $id;
	/** @var string|null (Datetime) */
	protected $expires_at;
	/** @var bool */
	protected $expired = false;
	/** @var bool */
	protected $multiple = false;
	/** @var int */
	protected $votes_count = 0;
	/** @var int|null */
	protected $voters_count = 0;
	/** @var bool|null */
	protected $voted = false;
	/** @var array|null */
	protected $own_votes = false;
	/** @var array */
	protected $options = [];
	/** @var Emoji[] */
	protected $emojis = [];

	/**
	 * Creates a poll record.
	 *
	 * @param array $question Array with the question
	 * @param array $options  Array of question options
	 * @param bool  $expired  "true" if the question is expired
	 * @param int   $votes    Number of total votes
	 * @param array $ownvotes Own vote
	 */
	public function __construct(array $question, array $options, bool $expired, int $votes, array $ownvotes = null)
	{
		$this->id           = (string)$question['id'];
		$this->expires_at   = !empty($question['end-time']) ? DateTimeFormat::utc($question['end-time'], DateTimeFormat::JSON) : null;
		$this->expired      = $expired;
		$this->multiple     = (bool)$question['multiple'];
		$this->votes_count  = $votes;
		$this->voters_count = $this->multiple ? $question['voters'] : null;
		$this->voted        = null;
		$this->own_votes    = $ownvotes;
		$this->options      = $options;
		$this->emojis       = [];
	}
}
