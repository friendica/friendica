<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPClient\Capability;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Temporary class to map Friendica used variables based on PSR-7 HTTPResponse
 */
interface ICanHandleHttpResponses
{
	/**
	 * Gets the Return Code
	 *
	 * @return string The Return Code
	 */
	public function getReturnCode(): string;

	/**
	 * Returns the Content Type
	 *
	 * @return string the Content Type
	 */
	public function getContentType(): string;

	/**
	 * Returns the headers
	 *
	 * @param string $header optional header field. Return all fields if empty
	 *
	 * @return string[] the headers or the specified content of the header variable
	 *@see MessageInterface::getHeader()
	 *
	 */
	public function getHeader(string $header);

	/**
	 * Returns all headers
	 *
	 * @see MessageInterface::getHeaders()
	 * @return string[][]
	 */
	public function getHeaders();

	/**
	 * Check if a specified header exists
	 *
	 * @see MessageInterface::hasHeader()
	 * @param string $field header field
	 * @return boolean "true" if header exists
	 */
	public function inHeader(string $field): bool;

	/**
	 * Returns the headers as an associated array
	 * @see MessageInterface::getHeaders()
	 * @deprecated
	 *
	 * @return string[][] associated header array
	 */
	public function getHeaderArray();

	/**
	 * @return bool
	 */
	public function isSuccess(): bool;

	/**
	 * Returns if the URL is permanently gone (return code 410)
	 *
	 * @return bool
	 */
	public function isGone(): bool;

	/**
	 * @return string
	 */
	public function getUrl(): string;

	/**
	 * If the request was redirected to another URL, gets the final URL requested
	 * @return string
	 */
	public function getRedirectUrl(): string;

	/**
	 * If the request was redirected to another URL, indicates if the redirect is permanent.
	 * If the request was not redirected, returns false.
	 * If the request was redirected multiple times, returns true only if all of the redirects were permanent.
	 *
	 * @return bool True if the redirect is permanent
	 */
	public function redirectIsPermanent(): bool;

	/**
	 * Getter for body
	 *
	 * @return string
	 */
	public function getBodyString();

	/**
	 * @return boolean
	 */
	public function isRedirectUrl(): bool;

	/**
	 * @return integer
	 */
	public function getErrorNumber(): int;

	/**
	 * @return string
	 */
	public function getError(): string;

	/**
	 * @return boolean
	 */
	public function isTimeout(): bool;

	/**
	 * Get the response body as a stream
	 *
	 * @return StreamInterface
	 */
	public function getBodyStream(): StreamInterface;
}
