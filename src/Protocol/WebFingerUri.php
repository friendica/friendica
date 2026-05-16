<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Protocol;

use GuzzleHttp\Psr7\Uri;

class WebFingerUri implements \Stringable
{
	/**
	 * @var string
	 */
	private $user;
	/**
	 * @var string
	 */
	private $host;
	/**
	 * @var int|null
	 */
	private $port;
	/**
	 * @var string|null
	 */
	private $path;

	private function __construct(string $user, string $host, int $port = null, string $path = null)
	{
		$this->user = $user;
		$this->host = $host;
		$this->port = $port;
		$this->path = $path;

		$this->validate();
	}

	/**
	 * @param string $addr
	 * @return WebFingerUri
	 */
	public static function fromString(string $addr): WebFingerUri
	{
		$uri = new Uri('acct://' . preg_replace('/^acct:/', '', $addr));

		return new self($uri->getUserInfo(), $uri->getHost(), $uri->getPort(), $uri->getPath());
	}

	private function validate()
	{
		if (!$this->user) {
			throw new \InvalidArgumentException('WebFinger URI User part is required');
		}

		if (!$this->host) {
			throw new \InvalidArgumentException('WebFinger URI Host part is required');
		}
	}

	public function getUser(): string
	{
		return $this->user;
	}

	public function getHost(): string
	{
		return $this->host;
	}

	public function getFullHost(): string
	{
		return $this->host
			. ($this->port ? ':' . $this->port : '')
			. ($this->path ?: '');
	}

	public function getLongForm(): string
	{
		return 'acct:' . $this->getShortForm();
	}

	public function getShortForm(): string
	{
		return $this->user . '@' . $this->getFullHost();
	}

	public function getAddr(): string
	{
		return $this->getShortForm();
	}

	public function __toString(): string
	{
		return $this->getShortForm();
	}
}
