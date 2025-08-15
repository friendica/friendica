<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Navigation\Notifications\Collection;

use Friendica\BaseCollection;
use Friendica\Navigation\Notifications\Entity\Notification as NotificationEntity;

class Notifications extends BaseCollection
{
	public function current(): NotificationEntity
	{
		return parent::current();
	}

	public function setSeen(): Notifications
	{
		$notifications = $this->map(function (NotificationEntity $notification) {
			$notification->setSeen();
		});

		if (!$notifications instanceof Notifications) {
			// Show the possible error explicitly
			throw new \Exception(sprintf(
				'BaseCollection::map() should return instance of %s, but returns %s instead.',
				Notifications::class,
				get_class($notifications),
			));
		}

		return $notifications;
	}

	public function setDismissed(): Notifications
	{
		$notifications = $this->map(function (NotificationEntity $notification) {
			$notification->setDismissed();
		});

		if (!$notifications instanceof Notifications) {
			// Show the possible error explicitly
			throw new \Exception(sprintf(
				'BaseCollection::map() should return instance of %s, but returns %s instead.',
				Notifications::class,
				get_class($notifications),
			));
		}

		return $notifications;
	}

	public function countUnseen(): int
	{
		return array_reduce($this->getArrayCopy(), function (int $carry, NotificationEntity $notification) {
			return $carry + ($notification->seen ? 0 : 1);
		}, 0);
	}
}
