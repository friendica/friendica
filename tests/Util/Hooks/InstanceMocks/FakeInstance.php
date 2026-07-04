<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Util\Hooks\InstanceMocks;

class FakeInstance implements IAmADecoratedInterface
{
	protected $aText = null;
	protected $cBool = null;
	protected $bText = null;

	public function __construct(
		?string $aText = null,
		?bool $cBool = null,
		?string $bText = null,
	) {
		$this->aText = $aText;
		$this->cBool = $cBool;
		$this->bText = $bText;
	}

	public function createSomething(string $aText, bool $cBool, string $bText): string
	{
		$this->aText = $aText;
		$this->cBool = $cBool;
		$this->bText = $bText;

		return '';
	}

	public function getAText(): ?string
	{
		return $this->aText;
	}

	public function getBText(): ?string
	{
		return $this->bText;
	}

	public function getCBool(): ?bool
	{
		return $this->cBool;
	}
}
