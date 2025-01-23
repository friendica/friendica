<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica;

use DateTimeZone;
use Exception;
use Friendica\App\BaseURL;
use Friendica\App\Mode;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Core\Theme;
use Friendica\Database\Database;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

/**
 * Helper for our main application structure for the life of this page.
 *
 * Primarily deals with the URL that got us here
 * and tries to make some sense of it, and
 * stores our page contents and config storage
 * and anything else that might need to be passed around
 * before we spit the page out.
 *
 * @internal
 */
final class AppLegacy implements AppHelper
{
	private $profile_owner = 0;

	private $timezone = '';

	private $contact_id = 0;

	private $queue = [];

	/** @var string The name of the current theme */
	private $currentTheme;

	/** @var string The name of the current mobile theme */
	private $currentMobileTheme;

	// Allow themes to control internal parameters
	// by changing App values in theme.php
	private $theme_info = [
		'videowidth'  => 425,
		'videoheight' => 350,
	];

	/**
	 * @var Database The Friendica database connection
	 */
	private $database;

	/**
	 * @var IManageConfigValues The config
	 */
	private $config;

	/**
	 * @var Mode The Mode of the Application
	 */
	private $mode;

	/**
	 * @var BaseURL
	 */
	private $baseURL;

	/**
	 * @var L10n The translator
	 */
	private $l10n;

	/**
	 * @var IManagePersonalConfigValues
	 */
	private $pConfig;

	/**
	 * @var IHandleUserSessions
	 */
	private $session;

	public function __construct(
		Database $database,
		IManageConfigValues $config,
		Mode $mode,
		BaseURL $baseURL,
		L10n $l10n,
		IManagePersonalConfigValues $pConfig,
		IHandleUserSessions $session
	) {
		$this->database = $database;
		$this->config   = $config;
		$this->mode     = $mode;
		$this->l10n     = $l10n;
		$this->baseURL  = $baseURL;
		$this->pConfig  = $pConfig;
		$this->session  = $session;
	}

	/**
	 * Set the profile owner ID
	 */
	public function setProfileOwner(int $owner_id): void
	{
		$this->profile_owner = $owner_id;
	}

	/**
	 * Get the profile owner ID
	 */
	public function getProfileOwner(): int
	{
		return $this->profile_owner;
	}

	/**
	 * Set the timezone
	 *
	 * @param string $timezone A valid time zone identifier, see https://www.php.net/manual/en/timezones.php
	 */
	public function setTimeZone(string $timezone): void
	{
		$this->timezone = (new DateTimeZone($timezone))->getName();

		DateTimeFormat::setLocalTimeZone($this->timezone);
	}

	/**
	 * Get the timezone name
	 */
	public function getTimeZone(): string
	{
		return $this->timezone;
	}

	/**
	 * Set the contact ID
	 */
	public function setContactId(int $contact_id): void
	{
		$this->contact_id = $contact_id;
	}

	/**
	 * Get the contact ID
	 */
	public function getContactId(): int
	{
		return $this->contact_id;
	}

	/**
	 * Set workerqueue information
	 *
	 * @param array<string,mixed> $queue
	 */
	public function setQueue(array $queue): void
	{
		$this->queue = $queue;
	}

	/**
	 * Fetch workerqueue information
	 *
	 * @return array<string,mixed> Worker queue
	 */
	public function getQueue(): array
	{
		return $this->queue;
	}

	/**
	 * Fetch a specific workerqueue field
	 *
	 * @param string $index Work queue record to fetch
	 *
	 * @return mixed|null Work queue item or NULL if not found
	 */
	public function getQueueValue(string $index)
	{
		return $this->queue[$index] ?? null;
	}

	/**
	 * Returns the current theme name. May be overridden by the mobile theme name.
	 *
	 * @return string Current theme name or empty string in installation phase
	 * @throws Exception
	 */
	public function getCurrentTheme(): string
	{
		if ($this->mode->isInstall()) {
			return '';
		}

		// Specific mobile theme override
		if (($this->mode->isMobile() || $this->mode->isTablet()) && $this->session->get('show-mobile', true)) {
			$user_mobile_theme = $this->getCurrentMobileTheme();

			// --- means same mobile theme as desktop
			if (!empty($user_mobile_theme) && $user_mobile_theme !== '---') {
				return $user_mobile_theme;
			}
		}

		if (!$this->currentTheme) {
			$this->computeCurrentTheme();
		}

		return $this->currentTheme;
	}

	/**
	 * Returns the current mobile theme name.
	 *
	 * @return string Mobile theme name or empty string if installer
	 * @throws Exception
	 */
	public function getCurrentMobileTheme(): string
	{
		if ($this->mode->isInstall()) {
			return '';
		}

		if (is_null($this->currentMobileTheme)) {
			$this->computeCurrentMobileTheme();
		}

		return $this->currentMobileTheme;
	}

	/**
	 * Setter for current theme name
	 *
	 * @param string $theme Name of current theme
	 */
	public function setCurrentTheme(string $theme): void
	{
		$this->currentTheme = $theme;
	}

	/**
	 * Setter for current mobile theme name
	 *
	 * @param string $theme Name of current mobile theme
	 */
	public function setCurrentMobileTheme(string $theme): void
	{
		$this->currentMobileTheme = $theme;
	}

	public function setThemeInfoValue(string $index, $value): void
	{
		$this->theme_info[$index] = $value;
	}

	public function getThemeInfo(): array
	{
		return $this->theme_info;
	}

	public function getThemeInfoValue(string $index, $default = null)
	{
		return $this->theme_info[$index] ?? $default;
	}

	/**
	 * Provide a sane default if nothing is chosen or the specified theme does not exist.
	 *
	 * @return string Current theme's stylesheet path
	 * @throws Exception
	 */
	public function getCurrentThemeStylesheetPath(): string
	{
		return Theme::getStylesheetPath($this->getCurrentTheme());
	}

	/**
	 * Returns the current config cache of this node
	 */
	public function getConfigCache(): Cache
	{
		return $this->config->getCache();
	}

	/**
	 * The basepath of this app
	 *
	 * @return string Base path from configuration
	 */
	public function getBasePath(): string
	{
		return $this->config->get('system', 'basepath');
	}

	/**
	 * Computes the current theme name based on the node settings, the page owner settings and the user settings
	 *
	 * @throws Exception
	 */
	private function computeCurrentTheme()
	{
		$system_theme = $this->config->get('system', 'theme');
		if (!$system_theme) {
			throw new Exception($this->l10n->t('No system theme config value set.'));
		}

		// Sane default
		$this->setCurrentTheme($system_theme);

		$page_theme    = null;
		$profile_owner = $this->getProfileOwner();

		// Find the theme that belongs to the user whose stuff we are looking at
		if (!empty($profile_owner) && ($profile_owner != $this->session->getLocalUserId())) {
			// Allow folks to override user themes and always use their own on their own site.
			// This works only if the user is on the same server
			$user = $this->database->selectFirst('user', ['theme'], ['uid' => $profile_owner]);
			if ($this->database->isResult($user) && !$this->session->getLocalUserId()) {
				$page_theme = $user['theme'];
			}
		}

		$theme_name = $page_theme ?: $this->session->get('theme', $system_theme);

		$theme_name = Strings::sanitizeFilePathItem($theme_name);
		if ($theme_name
			&& in_array($theme_name, Theme::getAllowedList())
			&& (file_exists('view/theme/' . $theme_name . '/style.css')
				|| file_exists('view/theme/' . $theme_name . '/style.php'))
		) {
			$this->setCurrentTheme($theme_name);
		}
	}

	/**
	 * Computes the current mobile theme name based on the node settings, the page owner settings and the user settings
	 */
	private function computeCurrentMobileTheme()
	{
		$system_mobile_theme = $this->config->get('system', 'mobile-theme', '');

		// Sane default
		$this->setCurrentMobileTheme($system_mobile_theme);

		$page_mobile_theme = null;
		$profile_owner     = $this->getProfileOwner();

		// Find the theme that belongs to the user whose stuff we are looking at
		if (!empty($profile_owner) && ($profile_owner != $this->session->getLocalUserId())) {
			// Allow folks to override user themes and always use their own on their own site.
			// This works only if the user is on the same server
			if (!$this->session->getLocalUserId()) {
				$page_mobile_theme = $this->pConfig->get($profile_owner, 'system', 'mobile-theme');
			}
		}

		$mobile_theme_name = $page_mobile_theme ?: $this->session->get('mobile-theme', $system_mobile_theme);

		$mobile_theme_name = Strings::sanitizeFilePathItem($mobile_theme_name);
		if ($mobile_theme_name == '---'
			||
			in_array($mobile_theme_name, Theme::getAllowedList())
			&& (file_exists('view/theme/' . $mobile_theme_name . '/style.css')
				|| file_exists('view/theme/' . $mobile_theme_name . '/style.php'))
		) {
			$this->setCurrentMobileTheme($mobile_theme_name);
		}
	}

	/**
	 * Automatically redirects to relative or absolute URL
	 * Should only be used if it isn't clear if the URL is either internal or external
	 *
	 * @param string $toUrl The target URL
	 *
	 * @throws InternalServerErrorException
	 */
	public function redirect(string $toUrl)
	{
		if (!empty(parse_url($toUrl, PHP_URL_SCHEME))) {
			System::externalRedirect($toUrl);
		} else {
			$this->baseURL->redirect($toUrl);
		}
	}
}
