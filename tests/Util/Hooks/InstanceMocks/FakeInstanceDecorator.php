<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Util\Hooks\InstanceMocks;

class FakeInstanceDecorator implements IAmADecoratedInterface
{
	public static $countInstance = 0;

	public const PREFIX = 'prefix1';

	/** @var IAmADecoratedInterface */
	protected $orig;

	public function __construct(IAmADecoratedInterface $orig)
	{
		$this->orig = $orig;

		self::$countInstance++;
	}

	public function createSomething(string $aText, bool $cBool, string $bText): string
	{
		return $this->orig->createSomething($aText, $cBool, $bText);
	}

	public function getAText(): ?string
	{
		return static::PREFIX . $this->orig->getAText();
	}

	public function getBText(): ?string
	{
		return static::PREFIX . $this->orig->getBText();
	}

	public function getCBool(): ?bool
	{
		return $this->orig->getCBool();
	}
}
