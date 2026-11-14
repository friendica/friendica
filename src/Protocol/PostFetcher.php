<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Protocol;

use Friendica\Content\Text\BBCode;
use Friendica\Event\FetchItemByLinkEvent;
use Friendica\Model\Post;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;
use Friendica\Post\LinkResolver;
use Friendica\Util\HTTPSignature;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Fetches posts from remote servers by their link and finds the posts quoted in a body
 */
final readonly class PostFetcher
{
	public function __construct(
		private LinkResolver $linkResolver,
		private LoggerInterface $logger,
		private EventDispatcherInterface $eventDispatcher,
		private ICanSendHttpRequests $httpClient,
	) {}

	/**
	 * Fetches item for given URI or plink
	 *
	 * @param string $uri
	 * @param int    $uid
	 * @param int    $completion
	 * @param string $mimetype
	 *
	 * @return integer item id
	 */
	public function fetchByLink(string $uri, int $uid = 0, int $completion = ActivityPub\Receiver::COMPLETION_MANUAL, string $mimetype = ''): int
	{
		$this->logger->info('Trying to fetch link', ['uid' => $uid, 'uri' => $uri]);
		$item_id = $this->linkResolver->searchByLink($uri, $uid);
		if (!empty($item_id)) {
			$this->logger->info('Link found', ['uid' => $uid, 'uri' => $uri, 'id' => $item_id]);
			return $item_id;
		}

		if (ActivityPub\Processor::alreadyKnown($uri, '')) {
			return 0;
		}

		$event = $this->eventDispatcher->dispatch(
			new FetchItemByLinkEvent($uri, $uid, null),
		);

		if ($event->getItemId() !== null) {
			$this->logger->info('Hook link fetched', ['uid' => $uid, 'uri' => $uri, 'id' => $event->getItemId()]);
			return $event->getItemId();
		}

		$head_not_found = false;
		if (!$mimetype) {
			try {
				$curlResult     = $this->httpClient->head($uri, [HttpClientOptions::ACCEPT_CONTENT => HttpClientAccept::JSON_AS, HttpClientOptions::REQUEST => HttpClientRequest::ACTIVITYPUB]);
				$mimetype       = $curlResult->getContentType();
				$head_not_found = ($curlResult->getReturnCode() == 404);
			} catch (\Throwable $th) {
				$this->logger->info('Error while fetching HTTP link via HEAD', ['uid' => $uid, 'uri' => $uri, 'code' => $th->getCode(), 'message' => $th->getMessage()]);
				return 0;
			}
		}

		if (!HTTPSignature::isValidContentType($mimetype, $uri) && ($head_not_found || (current(explode(';', $mimetype)) == 'application/json'))) {
			try {
				// Issue 14126: Workaround for Mastodon servers that return "application/json" on a "head" request.
				// Issue 14536: Lemmy answers a "head" request with an error (404), so we retry with a signed "get" request.
				$curlResult = HTTPSignature::fetchRaw($uri, $uid);
				$mimetype   = $curlResult->getContentType();
			} catch (\Throwable $th) {
				$this->logger->info('Error while fetching HTTP link via signed GET', ['uid' => $uid, 'uri' => $uri, 'code' => $th->getCode(), 'message' => $th->getMessage()]);
				return 0;
			}
		}

		if (HTTPSignature::isValidContentType($mimetype, $uri)) {
			$fetched_uri = ActivityPub\Processor::fetchMissingActivity($uri, [], '', $completion, $uid);
			if (!empty($fetched_uri)) {
				$item_id = $this->linkResolver->searchByLink($fetched_uri, $uid);
				if ($item_id) {
					$this->logger->info('ActivityPub link fetched', ['uid' => $uid, 'uri' => $uri, 'id' => $item_id]);
					return $item_id;
				}
			}
		}

		$item_id = Diaspora::fetchByURL($uri);
		if ($item_id) {
			$this->logger->info('Diaspora link fetched', ['uid' => $uid, 'uri' => $uri, 'id' => $item_id]);
			return $item_id;
		}

		$this->logger->info('This is not an item link', ['uid' => $uid, 'uri' => $uri]);
		return 0;
	}

	/**
	 * Fetch the uri-id of a quoted post by searching for data in the body or attached media
	 *
	 * @param string $body   The body of the
	 * @param int    $uid    The id of the user
	 * @return integer
	 */
	public function getQuoteUriId(string $body, int $uid = 0): int
	{
		$shared = BBCode::fetchShareAttributes($body);
		if (empty($shared['guid']) && empty($shared['message_id'])) {
			return 0;
		}

		if (empty($shared['link']) && empty($shared['message_id'])) {
			$this->logger->notice('Invalid share block.', ['share' => $shared]);
			return 0;
		}

		if (!empty($shared['guid'])) {
			$shared_item = Post::selectFirst(['uri-id'], ['guid' => $shared['guid'], 'uid' => [0, $uid]]);
			if (!empty($shared_item['uri-id'])) {
				$this->logger->debug('Found post by guid', ['guid' => $shared['guid'], 'uid' => $uid]);
				return $shared_item['uri-id'];
			}
		}

		if (!empty($shared['message_id'])) {
			$shared_item = Post::selectFirst(['uri-id'], ['uri' => $shared['message_id'], 'uid' => [0, $uid]]);
			if (!empty($shared_item['uri-id'])) {
				$this->logger->debug('Found post by message_id', ['message_id' => $shared['message_id'], 'uid' => $uid]);
				return $shared_item['uri-id'];
			}
		}

		if (!empty($shared['link'])) {
			$shared_item = Post::selectFirst(['uri-id'], ['plink' => $shared['link'], 'uid' => [0, $uid]]);
			if (!empty($shared_item['uri-id'])) {
				$this->logger->debug('Found post by link', ['link' => $shared['link'], 'uid' => $uid]);
				return $shared_item['uri-id'];
			}
		}

		$url = $shared['message_id'] ?: $shared['link'];
		$id  = $this->fetchByLink($url, 0, ActivityPub\Receiver::COMPLETION_ASYNC);
		if (!$id) {
			$this->logger->notice('Post could not be fetched.', ['url' => $url, 'uid' => $uid]);
			return 0;
		}

		$shared_item = Post::selectFirst(['uri-id'], ['id' => $id]);
		if (!empty($shared_item['uri-id'])) {
			$this->logger->debug('Fetched shared post', ['id' => $id, 'url' => $url, 'uid' => $uid]);
			return $shared_item['uri-id'];
		}

		$this->logger->warning('Post does not exist although it was supposed to had been fetched.', ['id' => $id, 'url' => $url, 'uid' => $uid]);
		return 0;
	}
}
