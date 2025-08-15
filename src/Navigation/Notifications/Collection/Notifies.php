<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Navigation\Notifications\Collection;

use Friendica\BaseCollection;
use Friendica\Navigation\Notifications\Entity\Notify as NotifyEntity;

class Notifies extends BaseCollection
{
	public function current(): NotifyEntity
	{
		return parent::current();
	}

	public function setSeen(): Notifies
	{
		$notifies = $this->map(function (NotifyEntity $notify) {
			$notify->setSeen();
		});

		if (!$notifies instanceof Notifies) {
			// Show the possible error explicitly
			throw new \Exception(sprintf(
				'BaseCollection::map() should return instance of %s, but returns %s instead.',
				Notifies::class,
				get_class($notifies),
			));
		}

		return $notifies;
	}
}
