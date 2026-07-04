<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test;

abstract class ProtocolTestCase extends MockedTestCase
{
	protected const PROTOCOL_FIXTURES = __DIR__ . '/Fixtures/protocol/';

	protected static function loadProtocolJsonFixture(string $path): array
	{
		return json_decode(
			file_get_contents(self::PROTOCOL_FIXTURES . $path . '.json'),
			true,
			512,
			JSON_THROW_ON_ERROR,
		);
	}

	protected static function serverSentEvent(string $event, array|string $data): string
	{
		$payload = is_array($data) ? json_encode($data) : $data;

		return "event: $event\n" . "data: $payload\n\n";
	}
}
