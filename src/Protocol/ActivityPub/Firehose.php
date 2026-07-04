<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Protocol\ActivityPub;

use Friendica\Content\Text\HTML;
use Friendica\Content\Item as ContentItem;
use Friendica\Content\Conversation\Repository\UserDefinedChannel;
use Friendica\Core\Protocol;
use Friendica\Model\Contact;
use Friendica\Model\Post\Engagement;
use Friendica\Network\HTTPClient\Client\HttpClient;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;
use Friendica\Protocol\Relay;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

/**
 * Handler for processing the FediBuzz firehose stream
 */
class Firehose
{
	private const FIREHOSE_URL    = 'https://fedi.buzz/api/v1/streaming/public';
	private const MAX_RETRY_DELAY = 60;
	private const CAUSER_URL      = 'https://relay.fedi.buzz/instance/relay.fedi.buzz';

	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly HttpClient $httpClient,
		private readonly ContentItem $contentItem,
		private readonly UserDefinedChannel $userDefinedChannel,
	) {}

	/**
	 * Connects to the firehose and reconnects with exponential back-off on failure.
	 */
	public function streamLoop(): void
	{
		$retryDelay = 1;

		/** @phpstan-ignore while.alwaysTrue */
		while (true) {
			try {
				$body = $this->httpClient->get(
					self::FIREHOSE_URL,
					HttpClientAccept::STREAMING,
					[
						HttpClientOptions::REQUEST => HttpClientRequest::STREAMING,
						HttpClientOptions::STREAM  => true,
					],
				)->getBodyStream();

				// Reset retry delay on successful connection
				$retryDelay = 1;

				$this->processStream($body);

			} catch (\Throwable $e) {
				$this->logger->info('Connection lost', ['code' => $e->getCode(), 'message' => $e->getMessage(), 'delay' => $retryDelay]);
				sleep($retryDelay);
				$retryDelay = min($retryDelay * 2, self::MAX_RETRY_DELAY);
			}
		}
	}

	/**
	 * Process the incoming stream from FediBuzz
	 *
	 * @param StreamInterface $body
	 * @return void
	 */
	public function processStream(StreamInterface $body): void
	{
		$buffer       = '';
		$currentEvent = null;
		$currentData  = '';

		while (!$body->eof()) {
			$buffer .= $body->read(8192);

			$lines  = explode("\n", $buffer);
			$buffer = array_pop($lines);

			foreach ($lines as $line) {
				[$currentEvent, $currentData] = $this->parseLine(trim($line), $currentEvent, $currentData);
			}
			flush();
		}
	}

	/**
	 * Parse a single stream line and dispatch a complete event when the blank separator is reached.
	 *
	 * @return array{0: string|null, 1: string} Updated [$currentEvent, $currentData]
	 */
	private function parseLine(string $line, ?string $currentEvent, string $currentData): array
	{
		if (str_starts_with($line, 'event: ')) {
			return [substr($line, 7), ''];
		}

		if (str_starts_with($line, 'data: ')) {
			return [$currentEvent, $currentData . substr($line, 6)];
		}

		if ($line === '') {
			$data = json_decode($currentData, true);
			// @todo Handle other event types.
			// @see https://docs.joinmastodon.org/methods/streaming/#events-3
			if ($data !== null && $currentEvent === 'update') {
				$this->processUpdate($data);
			}
			return [null, ''];
		}

		return [$currentEvent, $currentData];
	}

	/**
	 * Process an update event from the FediBuzz stream
	 *
	 * @param array $data
	 * @return void
	 */
	public function processUpdate(array $data): void
	{
		if (isset($data['reblog']) && is_array($data['reblog'])) {
			$data = $data['reblog'];
		}

		$tags = [];
		if (isset($data['tags']) && is_array($data['tags'])) {
			foreach ($data['tags'] as $tag) {
				if (isset($tag['name']) && is_string($tag['name'])) {
					$tags[] = $tag['name'];
				}
			}
		}

		$content    = trim(($data['spoiler_text'] ?? '') . "\n" . ($data['plain_content'] ?? HTML::toBBCode($data['content'] ?? '')));
		$author_url = $data['account']['uri'] ?? $data['account']['url'] ?? $data['account']['acct'] ?? '';
		$author     = $this->getContactByUrl($author_url, ['id', 'unsearchable']);
		$url        = $data['uri'] ?? $data['url'] ?? '';
		if (isset($data['language']) && is_string($data['language'])) {
			$declaredLanguages = [$data['language']];
		} else {
			$declaredLanguages = [];
		}

		if (!isset($author['id']) || $author['unsearchable']) {
			$this->logger->info('Skipping unsearchable or unknown author', ['url' => $url, 'author-url' => $author_url, 'author' => $author]);
			return;
		}

		if ($this->isSolicitedPost($tags, $content, $author['id'], $url, $declaredLanguages)) {
			$this->logger->info('Matched post', ['url' => $url]);
			$this->handlePost($url, $data);
			return;
		}

		$searchtext        = $this->getSearchTextForActivity($content, $author['id'], $tags);
		$detectedLanguages = $this->contentItem->getLanguageArray($content, 1, 0, $author['id']);
		$detectedLanguage  = !empty($detectedLanguages) ? array_key_first($detectedLanguages) : '';
		if ($this->userDefinedChannel->match($searchtext, $detectedLanguage)) {
			$this->logger->info('Matched channel', ['url' => $url]);
			$this->handlePost($url, $data);
			return;
		}
	}

	/**
	 * Get contact by URL for testing purposes
	 *
	 * @param string $url
	 * @param array $fields
	 * @return array
	 */
	protected function getContactByUrl(string $url, array $fields = []): array
	{
		return Contact::getByURL($url, null, $fields);
	}

	/**
	 * Wrapper for Relay::isSolicitedPost for testing purposes
	 *
	 * @param array $tags
	 * @param string $content
	 * @param int $authorid
	 * @param string $url
	 * @param array $languages
	 * @return bool
	 */
	protected function isSolicitedPost(array $tags, string $content, int $authorid, string $url, array $languages): bool
	{
		return Relay::isSolicitedPost($tags, $content, $authorid, $url, Protocol::ACTIVITYPUB, 0, $languages);
	}

	/**
	 * Wrapper for Engagement::getSearchTextForActivity for testing purposes
	 *
	 * @param string $content
	 * @param int $authorid
	 * @param array $tags
	 * @return string
	 */
	protected function getSearchTextForActivity(string $content, int $authorid, array $tags): string
	{
		return Engagement::getSearchTextForActivity($content, $authorid, $tags, [Receiver::PUBLIC_COLLECTION]);
	}

	/**
	 * Wrapper for Receiver::handlePost for testing purposes
	 *
	 * @param string $url
	 * @param array $data
	 * @return void
	 */
	protected function handlePost(string $url, array $data): void
	{
		Receiver::handlePost($url, self::CAUSER_URL, $data);
	}
}
