<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPClient\Client;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;

/**
 * Response body sink that aborts the transfer once the body exceeds a maximum size.
 *
 * The size is counted from the received data, so it also applies when the server
 * announces no or a wrong Content-Length. The position of the underlying stream is
 * the count, so a sink that is truncated for the next response of a redirect chain
 * starts again at zero.
 */
final class SizeLimitedStream implements StreamInterface
{
	use StreamDecoratorTrait;

	/** @var StreamInterface */
	private $stream;

	public function __construct(StreamInterface $stream, private readonly int $maxSize)
	{
		$this->stream = $stream;
	}

	/**
	 * @throws ResponseTooLargeException when the body would exceed the maximum size
	 */
	public function write(string $string): int
	{
		if ($this->stream->tell() + strlen($string) > $this->maxSize) {
			throw new ResponseTooLargeException($this->maxSize);
		}

		return $this->stream->write($string);
	}
}
