<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Profile\ProfileField\Collection;

use Friendica\BaseCollection;
use Friendica\Profile\ProfileField\Entity\ProfileField as ProfileFieldEntity;

class ProfileFields extends BaseCollection
{
	public function current(): ProfileFieldEntity
	{
		return parent::current();
	}

	public function map(callable $callback): ProfileFields
	{
		$profileFields = parent::map($callback);

		if (!$profileFields instanceof ProfileFields) {
			// Show the possible error explicitly
			throw new \Exception(sprintf(
				'BaseCollection::map() should return instance of %s, but returns %s instead.',
				ProfileFields::class,
				get_class($profileFields),
			));
		}

		return $profileFields;
	}

	public function filter(?callable $callback = null, int $flag = 0): ProfileFields
	{
		return new self(array_filter($this->getArrayCopy(), $callback, $flag));
	}
}
