<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Module\Settings;

use Friendica\App;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\Theme;
use Friendica\Model\User;
use Friendica\Module\BaseSettings;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Module to update user settings
 */
class Display extends BaseSettings
{
	/** @var IManageConfigValues */
	private $config;
	/** @var IManagePersonalConfigValues */
	private $pConfig;
	/** @var App */
	private $app;
	/** @var SystemMessages */
	private $systemMessages;

	public function __construct(SystemMessages $systemMessages, App $app, IManagePersonalConfigValues $pConfig, IManageConfigValues $config, IHandleUserSessions $session, App\Page $page, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($session, $page, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config         = $config;
		$this->pConfig        = $pConfig;
		$this->app            = $app;
		$this->systemMessages = $systemMessages;
	}

	protected function post(array $request = [])
	{
		$uid = $this->session->getLocalUserId();
		if (!$uid) {
			throw new HTTPException\ForbiddenException($this->t('Permission denied.'));
		}

		self::checkFormSecurityTokenRedirectOnError('/settings/display', 'settings_display');

		$user = User::getById($uid);

		$theme                  = !empty($request['theme'])                  ? trim($request['theme'])                    : $user['theme'];
		$mobile_theme           = !empty($request['mobile_theme'])           ? trim($request['mobile_theme'])             : '';
		$enable_smile           = !empty($request['enable_smile'])           ? intval($request['enable_smile'])           : 0;
		$first_day_of_week      = !empty($request['first_day_of_week'])      ? intval($request['first_day_of_week'])      : 0;
		$calendar_default_view  = !empty($request['calendar_default_view'])  ? trim($request['calendar_default_view'])    : 'month';
		$infinite_scroll        = !empty($request['infinite_scroll'])        ? intval($request['infinite_scroll'])        : 0;
		$enable_smart_threading = !empty($request['enable_smart_threading']) ? intval($request['enable_smart_threading']) : 0;
		$enable_dislike         = !empty($request['enable_dislike'])         ? intval($request['enable_dislike'])         : 0;
		$display_resharer       = !empty($request['display_resharer'])       ? intval($request['display_resharer'])       : 0;
		$stay_local             = !empty($request['stay_local'])             ? intval($request['stay_local'])             : 0;
		$preview_mode           = !empty($request['preview_mode'])           ? intval($request['preview_mode'])           : 0;
		$browser_update         = !empty($request['browser_update'])         ? intval($request['browser_update'])         : 0;
		if ($browser_update != -1) {
			$browser_update = $browser_update * 1000;
			if ($browser_update < 10000) {
				$browser_update = 10000;
			}
		}

		$itemspage_network = !empty($request['itemspage_network']) ?
			intval($request['itemspage_network']) :
			$this->config->get('system', 'itemspage_network');
		if ($itemspage_network > 100) {
			$itemspage_network = 100;
		}
		$itemspage_mobile_network = !empty($request['itemspage_mobile_network']) ?
			intval($request['itemspage_mobile_network']) :
			$this->config->get('system', 'itemspage_network_mobile');
		if ($itemspage_mobile_network > 100) {
			$itemspage_mobile_network = 100;
		}

		if ($mobile_theme !== '') {
			$this->pConfig->set($uid, 'system', 'mobile_theme', $mobile_theme);
		}

		$this->pConfig->set($uid, 'system', 'itemspage_network'       , $itemspage_network);
		$this->pConfig->set($uid, 'system', 'itemspage_mobile_network', $itemspage_mobile_network);
		$this->pConfig->set($uid, 'system', 'update_interval'         , $browser_update);
		$this->pConfig->set($uid, 'system', 'no_smilies'              , !$enable_smile);
		$this->pConfig->set($uid, 'system', 'infinite_scroll'         , $infinite_scroll);
		$this->pConfig->set($uid, 'system', 'no_smart_threading'      , !$enable_smart_threading);
		$this->pConfig->set($uid, 'system', 'hide_dislike'            , !$enable_dislike);
		$this->pConfig->set($uid, 'system', 'display_resharer'        , $display_resharer);
		$this->pConfig->set($uid, 'system', 'stay_local'              , $stay_local);
		$this->pConfig->set($uid, 'system', 'preview_mode'            , $preview_mode);

		$this->pConfig->set($uid, 'calendar', 'first_day_of_week'     , $first_day_of_week);
		$this->pConfig->set($uid, 'calendar', 'default_view'           , $calendar_default_view);

		if (in_array($theme, Theme::getAllowedList())) {
			if ($theme == $user['theme']) {
				// call theme_post only if theme has not been changed
				if ($themeconfigfile = Theme::getConfigFile($theme)) {
					require_once $themeconfigfile;
					theme_post($this->app);
				}
			} else {
				User::update(['theme' => $theme], $uid);
			}
		} else {
			$this->systemMessages->addNotice($this->t('The theme you chose isn\'t available.'));
		}

		Hook::callAll('display_settings_post', $request);

		$this->baseUrl->redirect('settings/display');
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$uid = $this->session->getLocalUserId();
		if (!$uid) {
			throw new HTTPException\ForbiddenException($this->t('Permission denied.'));
		}

		$default_theme = $this->config->get('system', 'theme');
		if (!$default_theme) {
			$default_theme = 'default';
		}

		$default_mobile_theme = $this->config->get('system', 'mobile-theme');
		if (!$default_mobile_theme) {
			$default_mobile_theme = 'none';
		}

		$user = User::getById($uid);

		$allowed_themes = Theme::getAllowedList();

		$themes = [];
		$mobile_themes = ['---' => $this->t('No special theme for mobile devices')];
		foreach ($allowed_themes as $theme) {
			$is_experimental = file_exists('view/theme/' . $theme . '/experimental');
			$is_unsupported  = file_exists('view/theme/' . $theme . '/unsupported');
			$is_mobile       = file_exists('view/theme/' . $theme . '/mobile');
			if (!$is_experimental || $this->config->get('experimental', 'exp_themes')) {
				$theme_name = ucfirst($theme);
				if ($is_unsupported) {
					$theme_name = $this->t('%s - (Unsupported)', $theme_name);
				} elseif ($is_experimental) {
					$theme_name = $this->t('%s - (Experimental)', $theme_name);
				}

				if ($is_mobile) {
					$mobile_themes[$theme] = $theme_name;
				} else {
					$themes[$theme] = $theme_name;
				}
			}
		}

		$theme_selected        = $user['theme'] ?: $default_theme;
		$mobile_theme_selected = $this->session->get('mobile-theme', $default_mobile_theme);

		$itemspage_network = intval($this->pConfig->get($uid, 'system', 'itemspage_network'));
		$itemspage_network = (($itemspage_network > 0 && $itemspage_network < 101) ? $itemspage_network : $this->config->get('system', 'itemspage_network'));
		$itemspage_mobile_network = intval($this->pConfig->get($uid, 'system', 'itemspage_mobile_network'));
		$itemspage_mobile_network = (($itemspage_mobile_network > 0 && $itemspage_mobile_network < 101) ? $itemspage_mobile_network : $this->config->get('system', 'itemspage_network_mobile'));

		$browser_update = intval($this->pConfig->get($uid, 'system', 'update_interval'));
		if ($browser_update != -1) {
			$browser_update = (($browser_update == 0) ? 40 : $browser_update / 1000); // default if not set: 40 seconds
		}

		$enable_smile           = !$this->pConfig->get($uid, 'system', 'no_smilies', 0);
		$infinite_scroll        =  $this->pConfig->get($uid, 'system', 'infinite_scroll', 0);
		$enable_smart_threading = !$this->pConfig->get($uid, 'system', 'no_smart_threading', 0);
		$enable_dislike         = !$this->pConfig->get($uid, 'system', 'hide_dislike', 0);
		$display_resharer       =  $this->pConfig->get($uid, 'system', 'display_resharer', 0);
		$stay_local             =  $this->pConfig->get($uid, 'system', 'stay_local', 0);

		$preview_mode  =  $this->pConfig->get($uid, 'system', 'preview_mode', BBCode::PREVIEW_LARGE);
		$preview_modes = [
			BBCode::PREVIEW_NONE     => $this->t('No preview'),
			BBCode::PREVIEW_NO_IMAGE => $this->t('No image'),
			BBCode::PREVIEW_SMALL    => $this->t('Small Image'),
			BBCode::PREVIEW_LARGE    => $this->t('Large Image'),
		];


		$first_day_of_week = $this->pConfig->get($uid, 'calendar', 'first_day_of_week', 0);
		$weekdays          = [
			0 => $this->t('Sunday'),
			1 => $this->t('Monday'),
			2 => $this->t('Tuesday'),
			3 => $this->t('Wednesday'),
			4 => $this->t('Thursday'),
			5 => $this->t('Friday'),
			6 => $this->t('Saturday')
		];

		$calendar_default_view = $this->pConfig->get($uid, 'calendar', 'default_view', 'month');
		$calendarViews         = [
			'month'      => $this->t('month'),
			'agendaWeek' => $this->t('week'),
			'agendaDay'  => $this->t('day'),
			'listMonth'  => $this->t('list')
		];

		$theme_config = '';
		if ($themeconfigfile = Theme::getConfigFile($theme_selected)) {
			require_once $themeconfigfile;
			$theme_config = theme_content($this->app);
		}

		$tpl = Renderer::getMarkupTemplate('settings/display.tpl');
		return Renderer::replaceMacros($tpl, [
			'$ptitle'         => $this->t('Display Settings'),
			'$submit'         => $this->t('Save Settings'),
			'$d_tset'         => $this->t('General Theme Settings'),
			'$d_ctset'        => $this->t('Custom Theme Settings'),
			'$d_cset'         => $this->t('Content Settings'),
			'$stitle'         => $this->t('Theme settings'),
			'$calendar_title' => $this->t('Calendar'),

			'$form_security_token' => self::getFormSecurityToken('settings_display'),
			'$uid'                 => $uid,

			'$theme'	    => ['theme', $this->t('Display Theme:'), $theme_selected, '', $themes, true],
			'$mobile_theme'	=> ['mobile_theme', $this->t('Mobile Theme:'), $mobile_theme_selected, '', $mobile_themes, false],
			'$theme_config' => $theme_config,

			'$itemspage_network'        => ['itemspage_network'       , $this->t('Number of items to display per page:'), $itemspage_network, $this->t('Maximum of 100 items')],
			'$itemspage_mobile_network' => ['itemspage_mobile_network', $this->t('Number of items to display per page when viewed from mobile device:'), $itemspage_mobile_network, $this->t('Maximum of 100 items')],
			'$ajaxint'                  => ['browser_update'          , $this->t('Update browser every xx seconds'), $browser_update, $this->t('Minimum of 10 seconds. Enter -1 to disable it.')],
			'$enable_smile'	            => ['enable_smile'            , $this->t('Display emoticons'), $enable_smile, $this->t('When enabled, emoticons are replaced with matching symbols.')],
			'$infinite_scroll'          => ['infinite_scroll'         , $this->t('Infinite scroll'), $infinite_scroll, $this->t('Automatic fetch new items when reaching the page end.')],
			'$enable_smart_threading'   => ['enable_smart_threading'  , $this->t('Enable Smart Threading'), $enable_smart_threading, $this->t('Enable the automatic suppression of extraneous thread indentation.')],
			'$enable_dislike'           => ['enable_dislike'          , $this->t('Display the Dislike feature'), $enable_dislike, $this->t('Display the Dislike button and dislike reactions on posts and comments.')],
			'$display_resharer'         => ['display_resharer'        , $this->t('Display the resharer'), $display_resharer, $this->t('Display the first resharer as icon and text on a reshared item.')],
			'$stay_local'               => ['stay_local'              , $this->t('Stay local'), $stay_local, $this->t("Don't go to a remote system when following a contact link.")],
			'$preview_mode'             => ['preview_mode'            , $this->t('Link preview mode'), $preview_mode, $this->t('Appearance of the link preview that is added to each post with a link.'), $preview_modes, false],

			'$first_day_of_week'     => ['first_day_of_week'    , $this->t('Beginning of week:')    , $first_day_of_week    , '', $weekdays     , false],
			'$calendar_default_view' => ['calendar_default_view', $this->t('Default calendar view:'), $calendar_default_view, '', $calendarViews, false],
		]);
	}
}
