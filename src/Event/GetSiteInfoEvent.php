<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired after the site information of a URL has been scraped, before it is returned.
 *
 * Can be used by addons to add, change or remove the site information.
 */
final class GetSiteInfoEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.get_site_info';

	/**
	 * @internal
	 *
	 * @param array $siteinfo The scrapped site information
	 */
	public function __construct(
		private array $siteinfo,
	) {
		parent::__construct(self::NAME);
	}

	/**
	 * @return array The scrapped site information
	 */
	public function getSiteInfoArray(): array
	{
		return $this->siteinfo;
	}

	/**
	 * @param array $siteinfo The scrapped site information
	 */
	public function setSiteInfoArray(array $siteinfo): void
	{
		$this->siteinfo = $siteinfo;
	}
}
