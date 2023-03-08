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

namespace Friendica\Navigation\Notifications\ValueObject;

/**
 * A view-only object for printing introduction notifications to the frontend
 */
class Introduction implements \JsonSerializable
{
	/** @var string */
	private $label;
	/** @var string */
	private $type;
	/** @var int */
	private $intro_id;
	/** @var string */
	private $madeBy;
	/** @var string */
	private $madeByUrl;
	/** @var string */
	private $madeByZrl;
	/** @var string */
	private $madeByAddr;
	/** @var int */
	private $contactId;
	/** @var string */
	private $photo;
	/** @var string */
	private $name;
	/** @var string */
	private $url;
	/** @var string */
	private $zrl;
	/** @var boolean */
	private $hidden;
	/** @var int */
	private $postNewFriend;
	/** @var boolean */
	private $knowYou;
	/** @var string */
	private $note;
	/** @var string */
	private $request;
	/** @var int */
	private $dfrnId;
	/** @var string */
	private $addr;
	/** @var string */
	private $network;
	/** @var int */
	private $uid;
	/** @var string */
	private $keywords;
	/** @var string */
	private $location;
	/** @var string */
	private $about;

	public function getLabel(): string
	{
		return $this->label;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getIntroId(): int
	{
		return $this->intro_id;
	}

	public function getMadeBy(): string
	{
		return $this->madeBy;
	}

	public function getMadeByUrl(): string
	{
		return $this->madeByUrl;
	}

	public function getMadeByZrl(): string
	{
		return $this->madeByZrl;
	}

	public function getMadeByAddr(): string
	{
		return $this->madeByAddr;
	}

	public function getContactId(): int
	{
		return $this->contactId;
	}

	public function getPhoto(): string
	{
		return $this->photo;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getUrl(): string
	{
		return $this->url;
	}

	public function getZrl(): string
	{
		return $this->zrl;
	}

	public function isHidden(): bool
	{
		return $this->hidden;
	}

	public function getPostNewFriend(): int
	{
		return $this->postNewFriend;
	}

	public function getKnowYou(): string
	{
		return $this->knowYou;
	}

	public function getNote(): string
	{
		return $this->note;
	}

	public function getRequest(): string
	{
		return $this->request;
	}

	public function getDfrnId(): int
	{
		return $this->dfrnId;
	}

	public function getAddr(): string
	{
		return $this->addr;
	}

	public function getNetwork(): string
	{
		return $this->network;
	}

	public function getUid(): int
	{
		return $this->uid;
	}

	public function getKeywords(): string
	{
		return $this->keywords;
	}

	public function getLocation(): string
	{
		return $this->location;
	}

	public function getAbout(): string
	{
		return $this->about;
	}

	public function __construct(array $data = [])
	{
		$this->label         = $data['label'] ?? '';
		$this->type          = $data['str_type'] ?? '';
		$this->intro_id      = $data['intro_id'] ?? -1;
		$this->madeBy        = $data['madeBy'] ?? '';
		$this->madeByUrl     = $data['madeByUrl'] ?? '';
		$this->madeByZrl     = $data['madeByZrl'] ?? '';
		$this->madeByAddr    = $data['madeByAddr'] ?? '';
		$this->contactId     = $data['contactId'] ?? -1;
		$this->photo         = $data['photo'] ?? '';
		$this->name          = $data['name'] ?? '';
		$this->url           = $data['url'] ?? '';
		$this->zrl           = $data['zrl'] ?? '';
		$this->hidden        = $data['hidden'] ?? false;
		$this->postNewFriend = $data['postNewFriend'] ?? '';
		$this->knowYou       = $data['knowYou'] ?? false;
		$this->note          = $data['note'] ?? '';
		$this->request       = $data['request'] ?? '';
		$this->dfrnId        = -1;
		$this->addr          = $data['addr'] ?? '';
		$this->network       = $data['network'] ?? '';
		$this->uid           = $data['uid'] ?? -1;
		$this->keywords      = $data['keywords'] ?? '';
		$this->location      = $data['location'] ?? '';
		$this->about         = $data['about'] ?? '';
	}

	/**
	 * @inheritDoc
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize()
	{
		return $this->toArray();
	}

	/**
	 * @return array
	 */
	public function toArray(): array
	{
		return get_object_vars($this);
	}
}
