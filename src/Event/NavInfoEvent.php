<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when the navigation information for the template is about to be returned.
 *
 * Can be used by addons to add, change or remove navigation entries.
 */
final class NavInfoEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.nav_info';

	/**
	 * @internal
	 *
	 * @param string    $banner       The banner HTML
	 * @param array     $nav          The navigation entries
	 * @param string    $sitelocation The webbie (username@site.com)
	 * @param array|null $userinfo    The user information (name, icon), null if not authenticated
	 */
	public function __construct(
		private string $banner,
		private array $nav,
		private string $sitelocation,
		private ?array $userinfo,
	) {
		parent::__construct(self::NAME);
	}

	public function getBanner(): string
	{
		return $this->banner;
	}

	public function getNavArray(): array
	{
		return $this->nav;
	}

	public function getSitelocation(): string
	{
		return $this->sitelocation;
	}

	public function getUserinfoArray(): ?array
	{
		return $this->userinfo;
	}

	public function setBanner(string $banner): void
	{
		$this->banner = $banner;
	}

	public function setNavArray(array $nav): void
	{
		$this->nav = $nav;
	}

	public function setSitelocation(string $sitelocation): void
	{
		$this->sitelocation = $sitelocation;
	}

	public function setUserinfoArray(?array $userinfo): void
	{
		$this->userinfo = $userinfo;
	}
}
