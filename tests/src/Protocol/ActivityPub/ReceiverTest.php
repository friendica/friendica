<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\src\Protocol\ActivityPub;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Protocol\ActivityPub\Processor;
use Friendica\Protocol\ActivityPub\Receiver;
use Friendica\Test\FixtureTestCase;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\JsonLD;
use PHPUnit\Framework\Attributes\DataProvider;

class ReceiverTest extends FixtureTestCase
{
	private const OBJECT_ID = 'https://video.example/videos/1';

	/**
	 * Builds a compacted object with a single "as:url" entry.
	 *
	 * @param array|string $url
	 */
	private static function compactedObject(string $type, $url): array
	{
		return ['@id' => self::OBJECT_ID, '@type' => $type, 'as:url' => $url];
	}

	private static function htmlLink(string $href): array
	{
		return [['@type' => 'as:Link', 'as:href' => ['@id' => $href], 'as:mediaType' => 'text/html']];
	}

	/**
	 * Seeds the parse_url cache, so the audio/video branch doesn't fetch the URL.
	 */
	private function seedSiteinfoCache(string $url): void
	{
		DBA::insert('parsed_url', [
			'url_hash' => hash('sha256', $url),
			'oembed'   => false,
			'url'      => $url,
			'content'  => serialize(['url' => $url, 'type' => 'link']),
			'created'  => DateTimeFormat::utcNow(),
			'expires'  => DateTimeFormat::utc('now + 1 day'),
		]);
	}

	public static function dataInvalidAlternateUrl(): array
	{
		return [
			'video-link-javascript' => [self::compactedObject('as:Video', self::htmlLink('javascript:alert(document.domain)'))],
			'audio-link-javascript' => [self::compactedObject('as:Audio', self::htmlLink('javascript:alert(document.domain)'))],
			'video-link-data'       => [self::compactedObject('as:Video', self::htmlLink('data:text/html,<script>alert(1)</script>'))],
			'video-plain-url'       => [self::compactedObject('as:Video', ['@id' => 'javascript:alert(document.domain)'])],
			'note-plain-url'        => [self::compactedObject('as:Note', ['@id' => 'javascript:alert(document.domain)'])],
		];
	}

	#[DataProvider('dataInvalidAlternateUrl')]
	public function testInvalidAlternateUrlIsDropped(array $object): void
	{
		$objectData = Receiver::getObjectDataFromActivity($object);

		self::assertNull($objectData['alternate-url']);
	}

	public static function dataValidAlternateUrl(): array
	{
		return [
			'video-link' => ['as:Video', 'https://video.example/w/abc'],
			'audio-link' => ['as:Audio', 'https://audio.example/track/abc'],
		];
	}

	#[DataProvider('dataValidAlternateUrl')]
	public function testValidAlternateUrlIsKept(string $type, string $href): void
	{
		$this->seedSiteinfoCache($href);

		$objectData = Receiver::getObjectDataFromActivity(self::compactedObject($type, self::htmlLink($href)));

		self::assertSame($href, $objectData['alternate-url']);
	}

	public static function dataCreator(): array
	{
		$mallory = 'https://attacker.example/users/mallory';
		$alice   = 'https://friendica.local/profile/selfcontact';

		return [
			'actor-from-other-system'      => [['as:actor' => ['@id' => $alice], 'as:attributedTo' => ['@id' => $mallory]], $mallory, $mallory],
			'additional-creator-elsewhere' => [['as:attributedTo' => [['@id' => $mallory], ['@id' => $alice]]], $mallory, $mallory],
		];
	}

	#[DataProvider('dataCreator')]
	public function testCreatorFromOtherSystemIsIgnored(array $fields, string $author, string $actor): void
	{
		$objectData = Receiver::getObjectDataFromActivity(['@id' => 'https://attacker.example/notes/1', '@type' => 'as:Note'] + $fields);

		self::assertSame($author, $objectData['author']);
		self::assertSame($actor, $objectData['actor']);
	}

	public static function dataInlineObject(): array
	{
		$signer = 'https://attacker.example/users/mallory';
		$other  = 'https://friendica.local/profile/selfcontact';

		return [
			'own-object'     => [['@id' => 'https://attacker.example/notes/1', '@type' => 'as:Note', 'as:attributedTo' => ['@id' => $signer]], true],
			'reference-only' => [['@id' => 'https://friendica.local/objects/1'], true],
			'foreign-object' => [['@id' => 'https://friendica.local/objects/1', '@type' => 'as:Note', 'as:attributedTo' => ['@id' => $signer]], false],
			'foreign-author' => [['@id' => 'https://attacker.example/notes/1', '@type' => 'as:Note', 'as:attributedTo' => ['@id' => $other]], false],
		];
	}

	#[DataProvider('dataInlineObject')]
	public function testInlineObjectHasToBelongToSigner(array $object, bool $expected): void
	{
		$method = new \ReflectionMethod(Receiver::class, 'isInlineObjectOfSigner');

		self::assertSame($expected, $method->invoke(null, $object, ['https://attacker.example/users/mallory']));
	}

	public function testFetchedObjectDoesNotConfirmADeletion(): void
	{
		$objectId = 'https://friendica.local/objects/1';
		$actor    = 'https://friendica.local/profile/selfcontact';

		// The origin still serves the object that somebody else claims to delete
		DI::cache()->set(Processor::CACHEKEY_FETCH_ACTIVITY . '0:' . hash('sha256', $objectId), [
			'@context'     => 'https://www.w3.org/ns/activitystreams',
			'id'           => $objectId,
			'type'         => 'Note',
			'attributedTo' => $actor,
			'content'      => 'still here',
		]);

		$activity = JsonLD::compact([
			'@context' => 'https://www.w3.org/ns/activitystreams',
			'id'       => $objectId,
			'type'     => 'Delete',
			'actor'    => $actor,
			'object'   => ['id' => $objectId, 'type' => 'Note'],
		]);

		$trustSource = false;
		Receiver::prepareObjectData($activity, 0, true, $trustSource);

		self::assertFalse($trustSource);
	}
}
