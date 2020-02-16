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

namespace Friendica\Domain\Entity\Item;

use Friendica\BaseEntity;
use Friendica\Domain\Entity\Item;
use Friendica\Network\HTTPException\NotImplementedException;

/**
 * Entity class for table item-content
 *
 * Content for all posts
 */
class Content extends BaseEntity
{
	/** @var int */
	private $id;

	/**
	 * @var string
	 */
	private $uri;

	/**
	 * @var int
	 * Id of the item-uri table entry that contains the item uri
	 */
	private $uriId;

	/**
	 * @var string
	 * RIPEMD-128 hash from uri
	 */
	private $uriPlinkHash = '';

	/**
	 * @var string
	 * item title
	 */
	private $title = '';

	/**
	 * @var string
	 */
	private $contentWarning = '';

	/**
	 * @var string
	 * item body content
	 */
	private $body;

	/**
	 * @var string
	 * text location where this item originated
	 */
	private $location = '';

	/**
	 * @var string
	 * longitude/latitude pair representing location where this item originated
	 */
	private $coord = '';

	/**
	 * @var string
	 * Language information about this post
	 */
	private $language;

	/**
	 * @var string
	 * application which generated this item
	 */
	private $app = '';

	/**
	 * @var string
	 */
	private $renderedHash = '';

	/**
	 * @var string
	 * item.body converted to html
	 */
	private $renderedHtml;

	/**
	 * @var string
	 * ActivityStreams object type
	 */
	private $objectType = '';

	/**
	 * @var string
	 * JSON encoded object structure unless it is an implied object (normal post)
	 */
	private $object;

	/**
	 * @var string
	 * ActivityStreams target type if applicable (URI)
	 */
	private $targetType = '';

	/**
	 * @var string
	 * JSON encoded target structure if used
	 */
	private $target;

	/**
	 * @var string
	 * permalink or URL to a displayable copy of the message at its source
	 */
	private $plink = '';

	/**
	 * @var string
	 * ActivityStreams verb
	 */
	private $verb = '';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'uri' => $this->uri,
			'uri-id' => $this->uriId,
			'uri-plink-hash' => $this->uriPlinkHash,
			'title' => $this->title,
			'content-warning' => $this->contentWarning,
			'body' => $this->body,
			'location' => $this->location,
			'coord' => $this->coord,
			'language' => $this->language,
			'app' => $this->app,
			'rendered-hash' => $this->renderedHash,
			'rendered-html' => $this->renderedHtml,
			'object-type' => $this->objectType,
			'object' => $this->object,
			'target-type' => $this->targetType,
			'target' => $this->target,
			'plink' => $this->plink,
			'verb' => $this->verb,
		];
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Get Thread
	 *
	 * @return Thread
	 */
	public function getThread()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for iid is not implemented yet');
	}

	/**
	 * @return string
	 * Get
	 */
	public function getUri()
	{
		return $this->uri;
	}

	/**
	 * @param string $uri
	 * Set
	 */
	public function setUri(string $uri)
	{
		$this->uri = $uri;
	}

	/**
	 * @return int
	 * Get Id of the item-uri table entry that contains the item uri
	 */
	public function getUriId()
	{
		return $this->uriId;
	}

	/**
	 * @param int $uriId
	 * Set Id of the item-uri table entry that contains the item uri
	 */
	public function setUriId(int $uriId)
	{
		$this->uriId = $uriId;
	}

	/**
	 * Get \ItemUri
	 *
	 * @return \ItemUri
	 */
	public function getItemUri()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
	}

	/**
	 * @return string
	 * Get RIPEMD-128 hash from uri
	 */
	public function getUriPlinkHash()
	{
		return $this->uriPlinkHash;
	}

	/**
	 * @param string $uriPlinkHash
	 * Set RIPEMD-128 hash from uri
	 */
	public function setUriPlinkHash(string $uriPlinkHash)
	{
		$this->uriPlinkHash = $uriPlinkHash;
	}

	/**
	 * @return string
	 * Get item title
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $title
	 * Set item title
	 */
	public function setTitle(string $title)
	{
		$this->title = $title;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getContentWarning()
	{
		return $this->contentWarning;
	}

	/**
	 * @param string $contentWarning
	 * Set
	 */
	public function setContentWarning(string $contentWarning)
	{
		$this->contentWarning = $contentWarning;
	}

	/**
	 * @return string
	 * Get item body content
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * @param string $body
	 * Set item body content
	 */
	public function setBody(string $body)
	{
		$this->body = $body;
	}

	/**
	 * @return string
	 * Get text location where this item originated
	 */
	public function getLocation()
	{
		return $this->location;
	}

	/**
	 * @param string $location
	 * Set text location where this item originated
	 */
	public function setLocation(string $location)
	{
		$this->location = $location;
	}

	/**
	 * @return string
	 * Get longitude/latitude pair representing location where this item originated
	 */
	public function getCoord()
	{
		return $this->coord;
	}

	/**
	 * @param string $coord
	 * Set longitude/latitude pair representing location where this item originated
	 */
	public function setCoord(string $coord)
	{
		$this->coord = $coord;
	}

	/**
	 * @return string
	 * Get Language information about this post
	 */
	public function getLanguage()
	{
		return $this->language;
	}

	/**
	 * @param string $language
	 * Set Language information about this post
	 */
	public function setLanguage(string $language)
	{
		$this->language = $language;
	}

	/**
	 * @return string
	 * Get application which generated this item
	 */
	public function getApp()
	{
		return $this->app;
	}

	/**
	 * @param string $app
	 * Set application which generated this item
	 */
	public function setApp(string $app)
	{
		$this->app = $app;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getRenderedHash()
	{
		return $this->renderedHash;
	}

	/**
	 * @param string $renderedHash
	 * Set
	 */
	public function setRenderedHash(string $renderedHash)
	{
		$this->renderedHash = $renderedHash;
	}

	/**
	 * @return string
	 * Get item.body converted to html
	 */
	public function getRenderedHtml()
	{
		return $this->renderedHtml;
	}

	/**
	 * @param string $renderedHtml
	 * Set item.body converted to html
	 */
	public function setRenderedHtml(string $renderedHtml)
	{
		$this->renderedHtml = $renderedHtml;
	}

	/**
	 * @return string
	 * Get ActivityStreams object type
	 */
	public function getObjectType()
	{
		return $this->objectType;
	}

	/**
	 * @param string $objectType
	 * Set ActivityStreams object type
	 */
	public function setObjectType(string $objectType)
	{
		$this->objectType = $objectType;
	}

	/**
	 * @return string
	 * Get JSON encoded object structure unless it is an implied object (normal post)
	 */
	public function getObject()
	{
		return $this->object;
	}

	/**
	 * @param string $object
	 * Set JSON encoded object structure unless it is an implied object (normal post)
	 */
	public function setObject(string $object)
	{
		$this->object = $object;
	}

	/**
	 * @return string
	 * Get ActivityStreams target type if applicable (URI)
	 */
	public function getTargetType()
	{
		return $this->targetType;
	}

	/**
	 * @param string $targetType
	 * Set ActivityStreams target type if applicable (URI)
	 */
	public function setTargetType(string $targetType)
	{
		$this->targetType = $targetType;
	}

	/**
	 * @return string
	 * Get JSON encoded target structure if used
	 */
	public function getTarget()
	{
		return $this->target;
	}

	/**
	 * @param string $target
	 * Set JSON encoded target structure if used
	 */
	public function setTarget(string $target)
	{
		$this->target = $target;
	}

	/**
	 * @return string
	 * Get permalink or URL to a displayable copy of the message at its source
	 */
	public function getPlink()
	{
		return $this->plink;
	}

	/**
	 * @param string $plink
	 * Set permalink or URL to a displayable copy of the message at its source
	 */
	public function setPlink(string $plink)
	{
		$this->plink = $plink;
	}

	/**
	 * @return string
	 * Get ActivityStreams verb
	 */
	public function getVerb()
	{
		return $this->verb;
	}

	/**
	 * @param string $verb
	 * Set ActivityStreams verb
	 */
	public function setVerb(string $verb)
	{
		$this->verb = $verb;
	}
}
