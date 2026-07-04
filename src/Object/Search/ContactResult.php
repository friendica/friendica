<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Search;

use Friendica\Model\Search;
use Psr\Http\Message\UriInterface;

/**
 * A search result for contact searching
 *
 * @see Search for details
 */
class ContactResult implements IResult
{
	/**
	 * @return int
	 */
	public function getCid()
	{
		return $this->cid;
	}

	/**
	 * @return int
	 */
	public function getPCid()
	{
		return $this->pCid;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getAddr()
	{
		return $this->addr;
	}

	/**
	 * @return string
	 */
	public function getItem()
	{
		return $this->item;
	}

	/**
	 * @return UriInterface
	 */
	public function getUrl(): UriInterface
	{
		return $this->url;
	}

	/**
	 * @return string
	 */
	public function getPhoto()
	{
		return $this->photo;
	}

	/**
	 * @return string
	 */
	public function getTags()
	{
		return $this->tags;
	}

	/**
	 * @return string
	 */
	public function getNetwork()
	{
		return $this->network;
	}

	/**
	 * @param string $name
	 * @param string $addr
	 * @param string $item
	 * @param UriInterface $url
	 * @param string $photo
	 * @param string $network
	 * @param int    $cid
	 * @param int    $pCid
	 * @param string $tags
	 */
	public function __construct(private $name, private $addr, private $item, private readonly UriInterface $url, private $photo, private $network, private $cid = 0, private $pCid = 0, private $tags = '') {}
}
