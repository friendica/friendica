<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Functional\Protocol;

use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\ItemURI;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Network\HTTPClient\Capability\ICanHandleHttpResponses;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Response\GuzzleResponse;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\Relay;
use Friendica\Test\FixtureTestCase;
use Friendica\Util\DateTimeFormat;
use GuzzleHttp\Psr7\Response;

class DiasporaTest extends FixtureTestCase
{
	private const AUTHOR = 'alice@alice.example';

	private const COMMENTER = 'bob@bob.example';

	private const KNOWN_MENTION = 'carol@carol.example';

	// Local user, its self contact (same id) provides the key pair
	private const LOCAL_UID = 42;

	private const IMPORTER = ['uid' => 0, 'page-flags' => User::PAGE_FLAGS_FREELOVE];

	/** @var string[] URLs of all outgoing HTTP requests */
	private array $requests = [];

	/** @var array<string, string> Response bodies by URL, everything else is answered with 404 */
	private array $responses = [];

	private string $publicKey;

	private string $privateKey;

	protected function setUp(): void
	{
		parent::setUp();

		$client = new class ($this) implements ICanSendHttpRequests {
			public function __construct(private readonly DiasporaTest $test) {}

			public function fetch(string $url, string $accept_content = HttpClientAccept::DEFAULT, int $timeout = 0, string $cookiejar = '', string $request = ''): string
			{
				return $this->request('GET', $url)->getBodyString();
			}

			public function get(string $url, string $accept_content = HttpClientAccept::DEFAULT, array $opts = []): ICanHandleHttpResponses
			{
				return $this->request('GET', $url, $opts);
			}

			public function head(string $url, array $opts = []): ICanHandleHttpResponses
			{
				return $this->request('HEAD', $url, $opts);
			}

			public function post(string $url, $params, array $headers = [], int $timeout = 0, string $request = ''): ICanHandleHttpResponses
			{
				return $this->request('POST', $url);
			}

			public function request(string $method, string $url, array $opts = []): ICanHandleHttpResponses
			{
				return new GuzzleResponse($this->test->respond($url), $url);
			}

			public function finalUrl(string $url): string
			{
				return $url;
			}
		};

		DI::init(DI::getDice()->addRule(ICanSendHttpRequests::class, ['instanceOf' => $client::class, 'constructParams' => [$this], 'shared' => true, 'call' => null]));

		DI::config()->set('system', 'diaspora_enabled', true);
		DI::config()->set('system', 'relay_scope', Relay::SCOPE_NONE);

		$keys             = DBA::selectFirst('contact', ['prvkey', 'pubkey'], ['id' => self::LOCAL_UID]);
		$this->privateKey = $keys['prvkey'];
		$this->publicKey  = $keys['pubkey'];

		$this->createDiasporaContact(self::AUTHOR);
		$this->createDiasporaContact(self::COMMENTER);
		$this->createDiasporaContact(self::KNOWN_MENTION);
	}

	public function testUnsolicitedFetchedStatusDoesNotProbeMentions(): void
	{
		$text = 'Hello @{One; one@unknown1.example} @{Two; two@unknown2.example} @{Three; three@unknown3.example}';

		self::assertFalse($this->dispatch(self::AUTHOR, $this->statusMessage('unsolicited-guid', $text), Diaspora::FETCHED));

		self::assertSame([], $this->requests);
		self::assertFalse(Post::exists(['guid' => 'unsolicited-guid']));
	}

	public function testSolicitedStatusIsStoredWithMentions(): void
	{
		$author = Contact::getByURL(self::AUTHOR, false, ['url', 'nurl']);
		DBA::insert('contact', [
			'uid'     => self::LOCAL_UID,
			'url'     => $author['url'],
			'nurl'    => $author['nurl'],
			'addr'    => self::AUTHOR,
			'network' => Protocol::DIASPORA,
			'rel'     => Contact::SHARING,
			'blocked' => false,
			'pending' => false,
			'created' => DateTimeFormat::utcNow(),
		]);

		$text = 'Hello @{Carol; ' . self::KNOWN_MENTION . '}';

		self::assertNotFalse($this->dispatch(self::AUTHOR, $this->statusMessage('solicited-guid', $text), Diaspora::FETCHED));

		$post = Post::selectFirst(['uri-id'], ['guid' => 'solicited-guid']);
		self::assertNotEmpty($post);
		self::assertTrue(DBA::exists('post-tag', ['uri-id' => $post['uri-id'], 'type' => Tag::MENTION]));
	}

	public function testRejectedParentIsFetchedOnce(): void
	{
		$envelope = Diaspora::buildMagicEnvelope(
			$this->statusMessage('parent-guid', 'Parent post'),
			['addr' => self::AUTHOR, 'uprvkey' => $this->privateKey],
		);
		$this->responses['https://bob.example/fetch/post/parent-guid'] = $envelope;

		$comment = '<comment>'
			. '<author>' . self::COMMENTER . '</author>'
			. '<guid>comment-guid</guid>'
			. '<parent_guid>parent-guid</parent_guid>'
			. '<text>A comment</text>'
			. '<created_at>2026-10-01T10:00:00Z</created_at>'
			. '</comment>';

		self::assertFalse($this->dispatch(self::AUTHOR, $comment, Diaspora::PUSHED));

		self::assertFalse(Post::exists(['guid' => 'parent-guid']));

		$fetches = array_filter($this->requests, fn (string $url): bool => str_contains($url, '/fetch/post/'));
		self::assertSame(['https://bob.example/fetch/post/parent-guid'], array_values($fetches));
	}

	/**
	 * Records an outgoing request and answers it from the prepared responses, otherwise with 404.
	 */
	public function respond(string $url): Response
	{
		$this->requests[] = $url;

		if (isset($this->responses[$url])) {
			return new Response(200, ['Content-Type' => 'application/magic-envelope+xml'], $this->responses[$url]);
		}

		return new Response(404);
	}

	private function dispatch(string $sender, string $xml, int $direction): bool|int
	{
		return Diaspora::dispatch(self::IMPORTER, ['author' => $sender, 'message' => $xml], simplexml_load_string($xml), $direction);
	}

	private function statusMessage(string $guid, string $text): string
	{
		return '<status_message>'
			. '<author>' . self::AUTHOR . '</author>'
			. '<guid>' . $guid . '</guid>'
			. '<created_at>2026-10-01T10:00:00Z</created_at>'
			. '<provider_display_name/>'
			. '<text>' . htmlspecialchars($text, ENT_XML1) . '</text>'
			. '<public>true</public>'
			. '</status_message>';
	}

	/**
	 * Creates a public Diaspora contact that is up to date, so it is never probed.
	 */
	private function createDiasporaContact(string $addr): void
	{
		[$nick, $host] = explode('@', $addr);
		$url           = 'https://' . $host . '/people/' . $nick;
		$uriId         = ItemURI::insert(['uri' => $url, 'guid' => $nick]);

		DBA::insert('contact', [
			'uid'         => 0,
			'uri-id'      => $uriId,
			'name'        => $nick,
			'nick'        => $nick,
			'url'         => $url,
			'nurl'        => 'http://' . $host . '/people/' . $nick,
			'addr'        => $addr,
			'baseurl'     => 'https://' . $host,
			'network'     => Protocol::DIASPORA,
			'rel'         => Contact::NOTHING,
			'blocked'     => false,
			'pending'     => false,
			'created'     => DateTimeFormat::utcNow(),
			'updated'     => DateTimeFormat::utcNow(),
			'next-update' => DateTimeFormat::utc('now + 1 year'),
		]);

		DBA::insert('diaspora-contact', [
			'uri-id'  => $uriId,
			'addr'    => $addr,
			'nick'    => $nick,
			'name'    => $nick,
			'pubkey'  => $this->publicKey,
			'created' => DateTimeFormat::utcNow(),
			'updated' => DateTimeFormat::utcNow(),
		]);
	}
}
