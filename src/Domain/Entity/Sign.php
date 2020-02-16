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
use Friendica\Network\HTTPException\NotImplementedException;

/**
 * Entity class for table sign
 *
 * Diaspora signatures
 */
class Sign extends BaseEntity
{
	/**
	 * @var int
	 * sequential ID
	 */
	private $id;

	/**
	 * @var int
	 * item.id
	 */
	private $iid = '0';

	/**
	 * @var string
	 */
	private $signedText;

	/**
	 * @var string
	 */
	private $signature;

	/**
	 * @var string
	 */
	private $signer = '';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'iid' => $this->iid,
			'signed_text' => $this->signedText,
			'signature' => $this->signature,
			'signer' => $this->signer,
		];
	}

	/**
	 * @return int
	 * Get sequential ID
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return int
	 * Get item.id
	 */
	public function getIid()
	{
		return $this->iid;
	}

	/**
	 * @param int $iid
	 * Set item.id
	 */
	public function setIid(int $iid)
	{
		$this->iid = $iid;
	}

	/**
	 * Get Item
	 *
	 * @return Item
	 */
	public function getItem()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
	}

	/**
	 * @return string
	 * Get
	 */
	public function getSignedText()
	{
		return $this->signedText;
	}

	/**
	 * @param string $signedText
	 * Set
	 */
	public function setSignedText(string $signedText)
	{
		$this->signedText = $signedText;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getSignature()
	{
		return $this->signature;
	}

	/**
	 * @param string $signature
	 * Set
	 */
	public function setSignature(string $signature)
	{
		$this->signature = $signature;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getSigner()
	{
		return $this->signer;
	}

	/**
	 * @param string $signer
	 * Set
	 */
	public function setSigner(string $signer)
	{
		$this->signer = $signer;
	}
}
