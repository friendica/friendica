<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Functional\Model;

use Friendica\Model\Tag;
use Friendica\Test\FixtureTestCase;

class TagTest extends FixtureTestCase
{
	private const URI_ID = 7;

	private const CONTACT_URL = 'https://friendica.local/profile/friendcontact';

	private function getNames(array $types): array
	{
		$names = array_column(Tag::getByURIId(self::URI_ID, $types), 'name');
		sort($names);
		return $names;
	}

	private function hashtag(string $name): string
	{
		return '#[url=https://friendica.local/search?tag=' . $name . ']' . $name . '[/url]';
	}

	public function testUpdateFromBodyReplacesHashtags(): void
	{
		$oldBody = 'Post ' . $this->hashtag('one') . ' ' . $this->hashtag('two') . ' ' . $this->hashtag('three');
		Tag::storeFromBody(self::URI_ID, $oldBody);

		self::assertSame(['one', 'three', 'two'], $this->getNames([Tag::HASHTAG]));

		// "one" is removed, "two" is renamed and "four" and "five" are added
		$newBody = 'Post ' . $this->hashtag('three') . ' ' . $this->hashtag('twoo') . ' ' . $this->hashtag('four') . ' ' . $this->hashtag('five');
		Tag::updateFromBody(self::URI_ID, $oldBody, $newBody);

		self::assertSame(['five', 'four', 'three', 'twoo'], $this->getNames([Tag::HASHTAG]));
	}

	public function testUpdateFromBodyKeepsHashtagsNotFromBody(): void
	{
		// Hashtags that had been added without being part of the body (see "Post\Tag\Add") have to stay
		Tag::store(self::URI_ID, Tag::HASHTAG, 'manual');

		$oldBody = 'Post ' . $this->hashtag('one');
		Tag::storeFromBody(self::URI_ID, $oldBody);

		Tag::updateFromBody(self::URI_ID, $oldBody, 'Post without tags');

		self::assertSame(['manual'], $this->getNames([Tag::HASHTAG]));
	}

	public function testUpdateFromBodyOnlyRemovesTypesFromBody(): void
	{
		Tag::store(self::URI_ID, Tag::IMPLICIT_MENTION, 'friendcontact', self::CONTACT_URL);
		Tag::store(self::URI_ID, Tag::TO, 'friendcontact', self::CONTACT_URL);

		$oldBody = '@[url=' . self::CONTACT_URL . ']friendcontact[/url] ' . $this->hashtag('one');
		Tag::storeFromBody(self::URI_ID, $oldBody);

		Tag::updateFromBody(self::URI_ID, $oldBody, 'Post without tags');

		self::assertCount(1, Tag::getByURIId(self::URI_ID, [Tag::IMPLICIT_MENTION]));
		self::assertCount(1, Tag::getByURIId(self::URI_ID, [Tag::TO]));
		self::assertSame([], Tag::getByURIId(self::URI_ID, [Tag::HASHTAG, Tag::MENTION, Tag::EXCLUSIVE_MENTION]));
	}

	public function testUpdateFromBodyRemovesMention(): void
	{
		$oldBody = '@[url=' . self::CONTACT_URL . ']friendcontact[/url] Post';
		Tag::storeFromBody(self::URI_ID, $oldBody);

		self::assertCount(1, Tag::getByURIId(self::URI_ID, [Tag::MENTION]));

		Tag::updateFromBody(self::URI_ID, $oldBody, 'Post');

		self::assertSame([], Tag::getByURIId(self::URI_ID, [Tag::MENTION]));
	}
}
