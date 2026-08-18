<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired before the permission tooltip HTML content is rendered, to allow addons to modify the model.
 */
final class PermissionTooltipContentEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.permission_tooltip_content';

	/**
	 * @internal
	 *
	 * @param array $model The item, photo or event model
	 */
	public function __construct(
		private array $model,
	) {
		parent::__construct(self::NAME);
	}

	/**
	 * @return array The item, photo or event model
	 */
	public function getModelArray(): array
	{
		return $this->model;
	}

	/**
	 * @param array $model The item, photo or event model
	 */
	public function setModelArray(array $model): void
	{
		$this->model = $model;
	}
}
