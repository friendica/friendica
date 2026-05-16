<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Navigation\Notifications\Collection;

use Friendica\BaseCollection;
use Friendica\Navigation\Notifications\ValueObject;

/**
 * @deprecated 2022.05 Use \Friendica\Navigation\Notifications\Collection\FormattedNotifications instead
 */
class FormattedNotifies extends BaseCollection
{
	/**
	 * @return ValueObject\FormattedNotify
	 */
	public function current(): ValueObject\FormattedNotify
	{
		return parent::current();
	}
}
