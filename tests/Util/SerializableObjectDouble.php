<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Util;

class SerializableObjectDouble implements \Serializable
{
	public function serialize()
	{
		return '\'serialized\'';
	}

	public function unserialize($data) {}

	public function __serialize(): array
	{
		return ['data' => 'serialized'];
	}

	public function __unserialize(array $data): void
	{
		return;
	}
}
