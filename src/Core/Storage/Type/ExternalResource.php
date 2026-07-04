<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Storage\Type;

use Exception;
use Friendica\Core\Storage\Exception\ReferenceStorageException;
use Friendica\Core\Storage\Capability\ICanReadFromStorage;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Util\HTTPSignature;
use Psr\Log\LoggerInterface;

/**
 * External resource storage class
 *
 * This class is used to load external resources, like images.
 * Is not intended to be selectable by admins as default storage class.
 */
class ExternalResource implements ICanReadFromStorage
{
	public const NAME = 'ExternalResource';

	/** @var LoggerInterface */
	protected $logger;

	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

	/**
	 * @inheritDoc
	 */
	public function get(string $reference): string
	{
		$data = json_decode($reference);
		if (empty($data->url)) {
			throw new ReferenceStorageException(sprintf('Invalid reference %s, cannot retrieve URL', $reference));
		}

		$parts = parse_url((string) $data->url);
		if (empty($parts['scheme']) || empty($parts['host'])) {
			throw new ReferenceStorageException(sprintf('Invalid reference %s, cannot extract scheme and host', $reference));
		}

		try {
			$fetchResult = HTTPSignature::fetchRaw($data->url, $data->uid, [HttpClientOptions::ACCEPT_CONTENT => [HttpClientAccept::IMAGE]]);
		} catch (Exception $exception) {
			$this->logger->notice('URL is invalid', ['url' => $data->url, 'error' => $exception]);
			throw new ReferenceStorageException(sprintf('External resource failed to get %s', $reference), $exception->getCode(), $exception);
		}

		if (!$fetchResult->isSuccess()) {
			throw new ReferenceStorageException(sprintf('External resource failed to get %s', $reference), $fetchResult->getReturnCode(), new Exception($fetchResult->getBodyString()));
		}

		$this->logger->debug('Got picture', ['Content-Type' => $fetchResult->getHeader('Content-Type'), 'uid' => $data->uid, 'url' => $data->url]);
		return $fetchResult->getBodyString();
	}

	/**
	 * @inheritDoc
	 */
	public function __toString(): string
	{
		return self::NAME;
	}

	/**
	 * @inheritDoc
	 */
	public static function getName(): string
	{
		return self::NAME;
	}
}
