<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Event;

use Dice\Dice;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Dice never autowires a nullable constructor parameter - it passes null instead.
 *
 * A class declaring `?EventDispatcherInterface $eventDispatcher = null` therefore silently
 * loses its dispatcher when the container builds it, and every event it should dispatch is
 * skipped. That's how Emailer stopped triggering SMTP addons.
 *
 * @see https://github.com/friendica/friendica/issues/15998
 */
class EventDispatcherInjectionTest extends TestCase
{
	/**
	 * BaseModule is the one legitimate exception: its parameter stays nullable so the
	 * subclasses that don't pass it along keep working, and it falls back to DI::eventDispatcher().
	 */
	private const FILES_WITH_FALLBACK = [
		'BaseModule.php',
	];

	public function testDiceDoesNotAutowireNullableParameters(): void
	{
		$dice = (new Dice())->addRules([
			EventDispatcherInterface::class => ['instanceOf' => \Friendica\Event\EventDispatcher::class],
		]);

		$nullable = $dice->create(ClassWithNullableDispatcher::class);
		$required = $dice->create(ClassWithRequiredDispatcher::class);

		self::assertNull($nullable->eventDispatcher, 'Dice still skips nullable parameters');
		self::assertInstanceOf(EventDispatcherInterface::class, $required->eventDispatcher);
	}

	public function testNoConstructorDeclaresANullableEventDispatcher(): void
	{
		$violations = [];

		foreach ($this->constructorSignaturesInSrc() as $file => $signature) {
			if (in_array(basename($file), self::FILES_WITH_FALLBACK, true)) {
				continue;
			}

			if (preg_match('/\?\s*EventDispatcherInterface\s+\$\w+/', $signature)) {
				$violations[] = $file;
			}
		}

		self::assertSame(
			[],
			$violations,
			"Dice passes null for these, so their events are never dispatched.\n"
			. 'Declare the parameter as required and non-nullable.',
		);
	}

	/**
	 * Parses the source instead of reflecting, because loading every class in src/ isn't possible.
	 *
	 * @return iterable<string, string> relative file path => constructor signature
	 */
	private function constructorSignaturesInSrc(): iterable
	{
		$src   = dirname(__DIR__, 3) . '/src';
		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src));

		foreach ($files as $file) {
			if ($file->getExtension() !== 'php') {
				continue;
			}

			$source = file_get_contents($file->getPathname());

			if (!preg_match('/function\s+__construct\s*\((.*?)\)\s*[:{]/s', $source, $matches)) {
				continue;
			}

			yield substr((string) $file->getPathname(), strlen($src) + 1) => $matches[1];
		}
	}
}

class ClassWithNullableDispatcher
{
	public function __construct(public readonly ?EventDispatcherInterface $eventDispatcher = null) {}
}

class ClassWithRequiredDispatcher
{
	public function __construct(public readonly EventDispatcherInterface $eventDispatcher) {}
}
