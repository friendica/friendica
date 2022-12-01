<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica;

use Exception;
use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Core\Config\Factory\Config;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\User;
use Friendica\Module\Maintenance;
use Friendica\Security\Authentication;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Core\Theme;
use Friendica\Database\Database;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Module\Special\HTTPException as ModuleHTTPException;
use Friendica\Network\HTTPException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\HTTPInputData;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * Our main application structure for the life of this page.
 *
 * Primarily deals with the URL that got us here
 * and tries to make some sense of it, and
 * stores our page contents and config storage
 * and anything else that might need to be passed around
 * before we spit the page out.
 *
 */
class App
{
	const PLATFORM = 'Friendica';
	const CODENAME = 'Giant Rhubarb';
	const VERSION  = '2022.12-dev';

	// Allow themes to control internal parameters
	// by changing App values in theme.php
	private $theme_info = [
		'videowidth'        => 425,
		'videoheight'       => 350,
	];

	private $user_id       = 0;
	private $nickname      = '';
	private $timezone      = '';
	private $profile_owner = 0;
	private $contact_id    = 0;
	private $queue         = [];

	/**
	 * @var App\Mode The Mode of the Application
	 */
	private $mode;

	/**
	 * @var BaseURL
	 */
	private $baseURL;

	/** @var string The name of the current theme */
	private $currentTheme;
	/** @var string The name of the current mobile theme */
	private $currentMobileTheme;

	/**
	 * @var IManageConfigValues The config
	 */
	private $config;

	/**
	 * @var LoggerInterface The logger
	 */
	private $logger;

	/**
	 * @var Profiler The profiler of this app
	 */
	private $profiler;

	/**
	 * @var Database The Friendica database connection
	 */
	private $database;

	/**
	 * @var L10n The translator
	 */
	private $l10n;

	/**
	 * @var App\Arguments
	 */
	private $args;

	/**
	 * @var IManagePersonalConfigValues
	 */
	private $pConfig;

	/**
	 * @var IHandleUserSessions
	 */
	private $session;

	/**
	 * Set the user ID
	 *
	 * @param int $user_id
	 * @return void
	 */
	public function setLoggedInUserId(int $user_id)
	{
		$this->user_id = $user_id;
	}

	/**
	 * Set the nickname
	 *
	 * @param int $user_id
	 * @return void
	 */
	public function setLoggedInUserNickname(string $nickname)
	{
		$this->nickname = $nickname;
	}

	public function isLoggedIn(): bool
	{
		return $this->session->getLocalUserId() && $this->user_id && ($this->user_id == $this->session->getLocalUserId());
	}

	/**
	 * Check if current user has admin role.
	 *
	 * @return bool true if user is an admin
	 * @throws Exception
	 */
	public function isSiteAdmin(): bool
	{
		return
			$this->session->getLocalUserId()
			&& $this->database->exists('user', [
				'uid'   => $this->getLoggedInUserId(),
				'email' => User::getAdminEmailList()
			]);
	}

	/**
	 * Fetch the user id
	 * @return int User id
	 */
	public function getLoggedInUserId(): int
	{
		return $this->user_id;
	}

	/**
	 * Fetch the user nick name
	 * @return string User's nickname
	 */
	public function getLoggedInUserNickname(): string
	{
		return $this->nickname;
	}

	/**
	 * Set the profile owner ID
	 *
	 * @param int $owner_id
	 * @return void
	 */
	public function setProfileOwner(int $owner_id)
	{
		$this->profile_owner = $owner_id;
	}

	/**
	 * Get the profile owner ID
	 *
	 * @return int
	 */
	public function getProfileOwner(): int
	{
		return $this->profile_owner;
	}

	/**
	 * Set the contact ID
	 *
	 * @param int $contact_id
	 * @return void
	 */
	public function setContactId(int $contact_id)
	{
		$this->contact_id = $contact_id;
	}

	/**
	 * Get the contact ID
	 *
	 * @return int
	 */
	public function getContactId(): int
	{
		return $this->contact_id;
	}

	/**
	 * Set the timezone
	 *
	 * @param string $timezone A valid time zone identifier, see https://www.php.net/manual/en/timezones.php
	 * @return void
	 */
	public function setTimeZone(string $timezone)
	{
		$this->timezone = (new \DateTimeZone($timezone))->getName();
		DateTimeFormat::setLocalTimeZone($this->timezone);
	}

	/**
	 * Get the timezone
	 *
	 * @return int
	 */
	public function getTimeZone(): string
	{
		return $this->timezone;
	}

	/**
	 * Set workerqueue information
	 *
	 * @param array $queue
	 * @return void
	 */
	public function setQueue(array $queue)
	{
		$this->queue = $queue;
	}

	/**
	 * Fetch workerqueue information
	 *
	 * @return array Worker queue
	 */
	public function getQueue(): array
	{
		return $this->queue ?? [];
	}

	/**
	 * Fetch a specific workerqueue field
	 *
	 * @param string $index Work queue record to fetch
	 * @return mixed Work queue item or NULL if not found
	 */
	public function getQueueValue(string $index)
	{
		return $this->queue[$index] ?? null;
	}

	public function setThemeInfoValue(string $index, $value)
	{
		$this->theme_info[$index] = $value;
	}

	public function getThemeInfo()
	{
		return $this->theme_info;
	}

	public function getThemeInfoValue(string $index, $default = null)
	{
		return $this->theme_info[$index] ?? $default;
	}

	/**
	 * Returns the current config cache of this node
	 *
	 * @return Cache
	 */
	public function getConfigCache()
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
		// Don't use the basepath of the config table for basepath (it should always be the config-file one)
		return $this->config->getCache()->get('system', 'basepath');
	}

	/**
	 * @param Database                    $database The Friendica Database
	 * @param IManageConfigValues         $config   The Configuration
	 * @param App\Mode                    $mode     The mode of this Friendica app
	 * @param BaseURL                     $baseURL  The full base URL of this Friendica app
	 * @param LoggerInterface             $logger   The current app logger
	 * @param Profiler                    $profiler The profiler of this application
	 * @param L10n                        $l10n     The translator instance
	 * @param App\Arguments               $args     The Friendica Arguments of the call
	 * @param IManagePersonalConfigValues $pConfig  Personal configuration
	 * @param IHandleUserSessions         $session  The (User)Session handler
	 */
	public function __construct(Database $database, IManageConfigValues $config, App\Mode $mode, BaseURL $baseURL, LoggerInterface $logger, Profiler $profiler, L10n $l10n, Arguments $args, IManagePersonalConfigValues $pConfig, IHandleUserSessions $session)
	{
		$this->database = $database;
		$this->config   = $config;
		$this->mode     = $mode;
		$this->baseURL  = $baseURL;
		$this->profiler = $profiler;
		$this->logger   = $logger;
		$this->l10n     = $l10n;
		$this->args     = $args;
		$this->pConfig  = $pConfig;
		$this->session  = $session;

		$this->load();
	}

	/**
	 * Load the whole app instance
	 */
	public function load()
	{
		set_time_limit(0);

		// Normally this constant is defined - but not if "pcntl" isn't installed
		if (!defined('SIGTERM')) {
			define('SIGTERM', 15);
		}

		// Ensure that all "strtotime" operations do run timezone independent
		date_default_timezone_set('UTC');

		// This has to be quite large to deal with embedded private photos
		ini_set('pcre.backtrack_limit', 500000);

		set_include_path(
			get_include_path() . PATH_SEPARATOR
			. $this->getBasePath() . DIRECTORY_SEPARATOR . 'include' . PATH_SEPARATOR
			. $this->getBasePath() . DIRECTORY_SEPARATOR . 'library' . PATH_SEPARATOR
			. $this->getBasePath());

		$this->profiler->reset();

		if ($this->mode->has(App\Mode::DBAVAILABLE)) {
			$this->profiler->update($this->config);

			Core\Hook::loadHooks();
			$loader = (new Config())->createConfigFileLoader($this->getBasePath(), $_SERVER);
			Core\Hook::callAll('load_config', $loader);
		}

		$this->loadDefaultTimezone();
		// Register template engines
		Core\Renderer::registerTemplateEngine('Friendica\Render\FriendicaSmartyEngine');
	}

	/**
	 * Loads the default timezone
	 *
	 * Include support for legacy $default_timezone
	 *
	 * @global string $default_timezone
	 */
	private function loadDefaultTimezone()
	{
		if ($this->config->get('system', 'default_timezone')) {
			$timezone = $this->config->get('system', 'default_timezone', 'UTC');
		} else {
			global $default_timezone;
			$timezone = $default_timezone ?? '' ?: 'UTC';
		}

		$this->setTimeZone($timezone);
	}

	/**
	 * Returns the current theme name. May be overriden by the mobile theme name.
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
	public function setCurrentTheme(string $theme)
	{
		$this->currentTheme = $theme;
	}

	/**
	 * Setter for current mobile theme name
	 *
	 * @param string $theme Name of current mobile theme
	 */
	public function setCurrentMobileTheme(string $theme)
	{
		$this->currentMobileTheme = $theme;
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

		$page_theme = null;
		// Find the theme that belongs to the user whose stuff we are looking at
		if (!empty($this->profile_owner) && ($this->profile_owner != $this->session->getLocalUserId())) {
			// Allow folks to override user themes and always use their own on their own site.
			// This works only if the user is on the same server
			$user = $this->database->selectFirst('user', ['theme'], ['uid' => $this->profile_owner]);
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
		// Find the theme that belongs to the user whose stuff we are looking at
		if (!empty($this->profile_owner) && ($this->profile_owner != $this->session->getLocalUserId())) {
			// Allow folks to override user themes and always use their own on their own site.
			// This works only if the user is on the same server
			if (!$this->session->getLocalUserId()) {
				$page_mobile_theme = $this->pConfig->get($this->profile_owner, 'system', 'mobile-theme');
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
	 * Provide a sane default if nothing is chosen or the specified theme does not exist.
	 *
	 * @return string Current theme's stylsheet path
	 * @throws Exception
	 */
	public function getCurrentThemeStylesheetPath(): string
	{
		return Core\Theme::getStylesheetPath($this->getCurrentTheme());
	}

	/**
	 * Sets the base url for use in cmdline programs which don't have
	 * $_SERVER variables
	 */
	public function checkURL()
	{
		$url = $this->config->get('system', 'url');

		// if the url isn't set or the stored url is radically different
		// than the currently visited url, store the current value accordingly.
		// "Radically different" ignores common variations such as http vs https
		// and www.example.com vs example.com.
		// We will only change the url to an ip address if there is no existing setting

		if (empty($url) || (!Util\Strings::compareLink($url, $this->baseURL->get())) && (!preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/", $this->baseURL->getHostname()))) {
			$this->config->set('system', 'url', $this->baseURL->get());
		}
	}

	/**
	 * Frontend App script
	 *
	 * The App object behaves like a container and a dispatcher at the same time, including a representation of the
	 * request and a representation of the response.
	 *
	 * This probably should change to limit the size of this monster method.
	 *
	 * @param App\Router                  $router
	 * @param IManagePersonalConfigValues $pconfig
	 * @param Authentication              $auth       The Authentication backend of the node
	 * @param App\Page                    $page       The Friendica page printing container
	 * @param HTTPInputData               $httpInput  A library for processing PHP input streams
	 * @param float                       $start_time The start time of the overall script execution
	 *
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function runFrontend(App\Router $router, IManagePersonalConfigValues $pconfig, Authentication $auth, App\Page $page, HTTPInputData $httpInput, float $start_time)
	{
		$this->profiler->set($start_time, 'start');
		$this->profiler->set(microtime(true), 'classinit');

		$moduleName = $this->args->getModuleName();
		$page->setLogging($this->args->getMethod(), $this->args->getModuleName(), $this->args->getCommand());

		try {
			// Missing DB connection: ERROR
			if ($this->mode->has(App\Mode::LOCALCONFIGPRESENT) && !$this->mode->has(App\Mode::DBAVAILABLE)) {
				throw new HTTPException\InternalServerErrorException($this->l10n->t('Apologies but the website is unavailable at the moment.'));
			}

			if (!$this->mode->isInstall()) {
				// Force SSL redirection
				if ($this->baseURL->checkRedirectHttps()) {
					System::externalRedirect($this->baseURL->get() . '/' . $this->args->getQueryString());
				}

				Core\Hook::callAll('init_1');
			}

			if ($this->mode->isNormal() && !$this->mode->isBackend()) {
				$requester = HTTPSignature::getSigner('', $_SERVER);
				if (!empty($requester)) {
					Profile::addVisitorCookieForHandle($requester);
				}
			}

			// ZRL
			if (!empty($_GET['zrl']) && $this->mode->isNormal() && !$this->mode->isBackend() && !$this->session->getLocalUserId()) {
				// Only continue when the given profile link seems valid
				// Valid profile links contain a path with "/profile/" and no query parameters
				if ((parse_url($_GET['zrl'], PHP_URL_QUERY) == '') &&
					strstr(parse_url($_GET['zrl'], PHP_URL_PATH), '/profile/')) {
					if ($this->session->get('visitor_home') != $_GET['zrl']) {
						$this->session->set('my_url', $_GET['zrl']);
						$this->session->set('authenticated', 0);

						$remote_contact = Contact::getByURL($_GET['zrl'], false, ['subscribe']);
						if (!empty($remote_contact['subscribe'])) {
							$_SESSION['remote_comment'] = $remote_contact['subscribe'];
						}
					}

					Model\Profile::zrlInit($this);
				} else {
					// Someone came with an invalid parameter, maybe as a DDoS attempt
					// We simply stop processing here
					$this->logger->debug('Invalid ZRL parameter.', ['zrl' => $_GET['zrl']]);
					throw new HTTPException\ForbiddenException();
				}
			}

			if (!empty($_GET['owt']) && $this->mode->isNormal()) {
				$token = $_GET['owt'];
				Model\Profile::openWebAuthInit($token);
			}

			if (!$this->mode->isBackend()) {
				$auth->withSession($this);
			}

			if (empty($_SESSION['authenticated'])) {
				header('X-Account-Management-Status: none');
			}

			/*
			 * check_config() is responsible for running update scripts. These automatically
			 * update the DB schema whenever we push a new one out. It also checks to see if
			 * any addons have been added or removed and reacts accordingly.
			 */

			// in install mode, any url loads install module
			// but we need "view" module for stylesheet
			if ($this->mode->isInstall() && $moduleName !== 'install') {
				$this->baseURL->redirect('install');
			} else {
				$this->checkURL();
				Core\Update::check($this->getBasePath(), false, $this->mode);
				Core\Addon::loadAddons();
				Core\Hook::loadHooks();
			}

			// Compatibility with the Android Diaspora client
			if ($moduleName == 'stream') {
				$this->baseURL->redirect('network?order=post');
			}

			if ($moduleName == 'conversations') {
				$this->baseURL->redirect('message');
			}

			if ($moduleName == 'commented') {
				$this->baseURL->redirect('network?order=comment');
			}

			if ($moduleName == 'liked') {
				$this->baseURL->redirect('network?order=comment');
			}

			if ($moduleName == 'activity') {
				$this->baseURL->redirect('network?conv=1');
			}

			if (($moduleName == 'status_messages') && ($this->args->getCommand() == 'status_messages/new')) {
				$this->baseURL->redirect('bookmarklet');
			}

			if (($moduleName == 'user') && ($this->args->getCommand() == 'user/edit')) {
				$this->baseURL->redirect('settings');
			}

			if (($moduleName == 'tag_followings') && ($this->args->getCommand() == 'tag_followings/manage')) {
				$this->baseURL->redirect('search');
			}

			// Initialize module that can set the current theme in the init() method, either directly or via App->setProfileOwner
			$page['page_title'] = $moduleName;

			// The "view" module is required to show the theme CSS
			if (!$this->mode->isInstall() && !$this->mode->has(App\Mode::MAINTENANCEDISABLED) && $moduleName !== 'view') {
				$module = $router->getModule(Maintenance::class);
			} else {
				// determine the module class and save it to the module instance
				// @todo there's an implicit dependency due SESSION::start(), so it has to be called here (yet)
				$module = $router->getModule();
			}

			// Processes data from GET requests
			$httpinput = $httpInput->process();
			$input     = array_merge($httpinput['variables'], $httpinput['files'], $request ?? $_REQUEST);

			// Let the module run it's internal process (init, get, post, ...)
			$timestamp = microtime(true);
			$response = $module->run($input);
			$this->profiler->set(microtime(true) - $timestamp, 'content');
			if ($response->getHeaderLine(ICanCreateResponses::X_HEADER) === ICanCreateResponses::TYPE_HTML) {
				$page->run($this, $this->baseURL, $this->args, $this->mode, $response, $this->l10n, $this->profiler, $this->config, $pconfig, $this->session->getLocalUserId());
			} else {
				$page->exit($response);
			}
		} catch (HTTPException $e) {
			(new ModuleHTTPException())->rawContent($e);
		}
		$page->logRuntime($this->config, 'runFrontend');
	}

	/**
	 * Automatically redirects to relative or absolute URL
	 * Should only be used if it isn't clear if the URL is either internal or external
	 *
	 * @param string $toUrl The target URL
	 *
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function redirect(string $toUrl)
	{
		if (!empty(parse_url($toUrl, PHP_URL_SCHEME))) {
			Core\System::externalRedirect($toUrl);
		} else {
			$this->baseURL->redirect($toUrl);
		}
	}
}
