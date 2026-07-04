<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

use Friendica\Core\L10n;
use Friendica\Test\Util\SampleStorageBackend;
use Mockery\MockInterface;

function create_instance(&$data): void
{
	/** @var L10n&MockInterface $l10n */
	$l10n = \Mockery::mock(L10n::class);

	if ($data['name'] == SampleStorageBackend::getName()) {
		$data['storage'] = new SampleStorageBackend($l10n);
	}
}
