<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Protocol\ActivityPub;

use Friendica\Core\Hook;
use Friendica\Core\Hooks\HookEventBridge;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Protocol\ActivityPub\Transmitter;
use Friendica\Test\FixtureTestCase;

class TransmitterTest extends FixtureTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		DI::config()->set('system', 'no_smilies', false);

		/** @var \Friendica\Event\EventDispatcher */
		$eventDispatcher = DI::eventDispatcher();

		foreach (HookEventBridge::getStaticSubscribedEvents() as $eventName => $methodName) {
			$eventDispatcher->addListener($eventName, [HookEventBridge::class, $methodName]);
		}

		Hook::register('smilie', 'tests/Util/SmileyWhitespaceAddon.php', 'add_test_unicode_smilies');
		Hook::loadHooks();
	}

	public function testEmojiPost(): void
	{
		$post = Post::selectFirst([], ['id' => 14]);
		$this->assertNotNull($post);
		$note = Transmitter::createNote($post);
		$this->assertNotNull($note);

		$this->assertEquals(':like: :friendica: no <code>:dislike</code> :p: :embarrassed: 🤗 ❤ :smileyheart333: 🔥', $note['content']);
		$emojis = array_fill_keys(['like', 'friendica', 'p', 'embarrassed', 'smileyheart333'], true);
		$this->assertEquals(count($emojis), count($note['tag']));
		foreach ($note['tag'] as $emoji) {
			$this->assertTrue(array_key_exists($emoji['name'], $emojis));
			$this->assertEquals('Emoji', $emoji['type']);
		}
	}
}
