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

namespace Friendica\Moderation\Entity;

/**
 * @property-read int            $id
 * @property-read int            $reporterId
 * @property-read int            $cid
 * @property-read string         $comment
 * @property-read string|null    $category
 * @property-read bool           $forward
 * @property-read array          $postUriIds
 * @property-read int            $uid
 * @property-read \DateTime|null $created
 */
class Report extends \Friendica\BaseEntity
{
	/** @var int|null */
	protected $id;
	/** @var int ID of the contact making a moderation report*/
	protected $reporterId;
	/** @var int ID of the contact being reported*/
	protected $cid;
	/** @var string Optional comment */
	protected $comment;
	/** @var string Optional category */
	protected $category;
	/** @var string Violated rules */
	protected $rules;
	/** @var bool Whether this report should be forwarded to the remote server */
	protected $forward;
	/** @var \DateTime|null When the report was created */
	protected $created;
	/** @var array Optional list of URI IDs of posts supporting the report*/
	protected $postUriIds;
	/** @var int ID of the user making a moderation report*/
	protected $uid;

	public function __construct(int $reporterId, int $cid, \DateTime $created, string $comment = '', string $category = null, string $rules = '', bool $forward = false, array $postUriIds = [], int $uid = null, int $id = null)
	{
		$this->reporterId = $reporterId;
		$this->cid        = $cid;
		$this->created    = $created;
		$this->comment    = $comment;
		$this->category   = $category;
		$this->rules      = $rules;
		$this->forward    = $forward;
		$this->postUriIds = $postUriIds;
		$this->uid        = $uid;
		$this->id         = $id;
	}
}
