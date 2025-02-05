<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Settings;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Page;
use Friendica\AppHelper;
use Friendica\Content\ContactSelector;
use Friendica\Content\Conversation\Collection\Timelines;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Conversation\Factory\Channel as ChannelFactory;
use Friendica\Content\Conversation\Factory\Community as CommunityFactory;
use Friendica\Content\Conversation\Factory\Network as NetworkFactory;
use Friendica\Content\Conversation\Factory\Timeline as TimelineFactory;
use Friendica\Content\Conversation\Repository;
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
	/** @var AppHelper */
	private $appHelper;
	/** @var SystemMessages */
	private $systemMessages;
	/** @var ChannelFactory */
	protected $channel;
	/** @var Repository\UserDefinedChannel */
	protected $userDefinedChannel;
	/** @var CommunityFactory */
	protected $community;
	/** @var NetworkFactory */
	protected $network;
	/** @var TimelineFactory */
	protected $timeline;

	public function __construct(Repository\UserDefinedChannel $userDefinedChannel, NetworkFactory $network, CommunityFactory $community, ChannelFactory $channel, TimelineFactory $timeline, SystemMessages $systemMessages, AppHelper $appHelper, IManagePersonalConfigValues $pConfig, IManageConfigValues $config, IHandleUserSessions $session, Page $page, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($session, $page, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config             = $config;
		$this->pConfig            = $pConfig;
		$this->appHelper          = $appHelper;
		$this->systemMessages     = $systemMessages;
		$this->timeline           = $timeline;
		$this->channel            = $channel;
		$this->community          = $community;
		$this->network            = $network;
		$this->userDefinedChannel = $userDefinedChannel;
	}

	protected function post(array $request = [])
	{
		$uid = $this->session->getLocalUserId();
		if (!$uid) {
			throw new HTTPException\ForbiddenException($this->t('Permission denied.'));
		}

		self::checkFormSecurityTokenRedirectOnError('/settings/display', 'settings_display');

		$user = User::getById($uid);

		$theme                   = trim($request['theme']);
		$mobile_theme            = trim($request['mobile_theme'] ?? '');
		$enable_smile            = (bool)$request['enable_smile'];
		$enable                  = (array)$request['enable'];
		$bookmark                = (array)$request['bookmark'];
		$channel_languages       = (array)$request['channel_languages'];
		$first_day_of_week       = (int)$request['first_day_of_week'];
		$calendar_default_view   = trim($request['calendar_default_view']);
		$infinite_scroll         = (bool)$request['infinite_scroll'];
		$enable_smart_threading  = (bool)$request['enable_smart_threading'];
		$enable_dislike          = (bool)$request['enable_dislike'];
		$display_resharer        = (bool)$request['display_resharer'];
		$stay_local              = (bool)$request['stay_local'];
		$hide_empty_descriptions = (bool)$request['hide_empty_descriptions'];
		$hide_custom_emojis      = (bool)$request['hide_custom_emojis'];
		$platform_icon_style     = (int)$request['platform_icon_style'];
		$show_page_drop          = (bool)$request['show_page_drop'];
		$display_eventlist       = (bool)$request['display_eventlist'];
		$preview_mode            = (int)$request['preview_mode'];
		$browser_update          = (int)$request['browser_update'];
		if ($browser_update != -1) {
			$browser_update = $browser_update * 1000;
			if ($browser_update < 10000) {
				$browser_update = 10000;
			}
		}

		$enabled_timelines = [];
		foreach ($enable as $code => $enabled) {
			if ($enabled) {
				$enabled_timelines[] = $code;
			}
		}

		$network_timelines = [];
		foreach ($bookmark as $code => $bookmarked) {
			if ($bookmarked) {
				$network_timelines[] = $code;
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

		$this->pConfig->set($uid, 'system', 'itemspage_network', $itemspage_network);
		$this->pConfig->set($uid, 'system', 'itemspage_mobile_network', $itemspage_mobile_network);
		$this->pConfig->set($uid, 'system', 'update_interval', $browser_update);
		$this->pConfig->set($uid, 'system', 'no_smilies', !$enable_smile);
		$this->pConfig->set($uid, 'system', 'infinite_scroll', $infinite_scroll);
		$this->pConfig->set($uid, 'system', 'no_smart_threading', !$enable_smart_threading);
		$this->pConfig->set($uid, 'system', 'hide_dislike', !$enable_dislike);
		$this->pConfig->set($uid, 'system', 'display_resharer', $display_resharer);
		$this->pConfig->set($uid, 'system', 'stay_local', $stay_local);
		$this->pConfig->set($uid, 'system', 'show_page_drop', $show_page_drop);
		$this->pConfig->set($uid, 'system', 'display_eventlist', $display_eventlist);
		$this->pConfig->set($uid, 'system', 'preview_mode', $preview_mode);

		$this->pConfig->set($uid, 'system', 'network_timelines', $network_timelines);
		$this->pConfig->set($uid, 'system', 'enabled_timelines', $enabled_timelines);
		$this->pConfig->set($uid, 'channel', 'languages', $channel_languages);

		$this->pConfig->set($uid, 'accessibility', 'hide_empty_descriptions', $hide_empty_descriptions);
		$this->pConfig->set($uid, 'accessibility', 'hide_custom_emojis', $hide_custom_emojis);
		$this->pConfig->set($uid, 'accessibility', 'platform_icon_style', $platform_icon_style);

		$this->pConfig->set($uid, 'calendar', 'first_day_of_week', $first_day_of_week);
		$this->pConfig->set($uid, 'calendar', 'default_view', $calendar_default_view);

		if (in_array($theme, Theme::getAllowedList())) {
			if ($theme == $user['theme']) {
				// call theme_post only if theme has not been changed
				if ($themeconfigfile = Theme::getConfigFile($theme)) {
					require_once $themeconfigfile;
					theme_post($this->appHelper);
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

		$themes        = [];
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

		$itemspage_network        = intval($this->pConfig->get($uid, 'system', 'itemspage_network'));
		$itemspage_network        = (($itemspage_network > 0 && $itemspage_network < 101) ? $itemspage_network : $this->config->get('system', 'itemspage_network'));
		$itemspage_mobile_network = intval($this->pConfig->get($uid, 'system', 'itemspage_mobile_network'));
		$itemspage_mobile_network = (($itemspage_mobile_network > 0 && $itemspage_mobile_network < 101) ? $itemspage_mobile_network : $this->config->get('system', 'itemspage_network_mobile'));

		$browser_update = intval($this->pConfig->get($uid, 'system', 'update_interval'));
		if ($browser_update != -1) {
			$browser_update = (($browser_update == 0) ? 40 : $browser_update / 1000); // default if not set: 40 seconds
		}

		$enable_smile           = !$this->pConfig->get($uid, 'system', 'no_smilies', false);
		$infinite_scroll        = $this->pConfig->get($uid, 'system', 'infinite_scroll', false);
		$enable_smart_threading = !$this->pConfig->get($uid, 'system', 'no_smart_threading', false);
		$enable_dislike         = !$this->pConfig->get($uid, 'system', 'hide_dislike', false);
		$display_resharer       = $this->pConfig->get($uid, 'system', 'display_resharer', false);
		$stay_local             = $this->pConfig->get($uid, 'system', 'stay_local', false);
		$show_page_drop         = $this->pConfig->get($uid, 'system', 'show_page_drop', true);
		$display_eventlist      = $this->pConfig->get($uid, 'system', 'display_eventlist', true);

		$hide_empty_descriptions = $this->pConfig->get($uid, 'accessibility', 'hide_empty_descriptions', false);
		$hide_custom_emojis      = $this->pConfig->get($uid, 'accessibility', 'hide_custom_emojis', false);
		$platform_icon_style     = $this->pConfig->get($uid, 'accessibility', 'platform_icon_style', ContactSelector::SVG_COLOR_BLACK);
		$platform_icon_styles    = [
			ContactSelector::SVG_DISABLED    => $this->t('Disabled'),
			ContactSelector::SVG_COLOR_BLACK => $this->t('Color/Black'),
			ContactSelector::SVG_BLACK       => $this->t('Black'),
			ContactSelector::SVG_COLOR_WHITE => $this->t('Color/White'),
			ContactSelector::SVG_WHITE       => $this->t('White'),
		];

		$preview_mode  = $this->pConfig->get($uid, 'system', 'preview_mode', BBCode::PREVIEW_LARGE);
		$preview_modes = [
			BBCode::PREVIEW_NONE     => $this->t('No preview'),
			BBCode::PREVIEW_NO_IMAGE => $this->t('No image'),
			BBCode::PREVIEW_SMALL    => $this->t('Small Image'),
			BBCode::PREVIEW_LARGE    => $this->t('Large Image'),
		];

		$bookmarked_timelines = $this->pConfig->get($uid, 'system', 'network_timelines', $this->getAvailableTimelines($uid, true)->column('code'));
		$enabled_timelines    = $this->pConfig->get($uid, 'system', 'enabled_timelines', $this->getAvailableTimelines($uid, false)->column('code'));
		$channel_languages    = User::getWantedLanguages($uid);
		$languages            = $this->l10n->getLanguageCodes(true);

		$timelines = [];
		foreach ($this->getAvailableTimelines($uid) as $timeline) {
			$timelines[] = [
				'label'       => $timeline->label,
				'description' => $timeline->description,
				'enable'      => ["enable[{$timeline->code}]", '', in_array($timeline->code, $enabled_timelines)],
				'bookmark'    => ["bookmark[{$timeline->code}]", '', in_array($timeline->code, $bookmarked_timelines)],
			];
		}

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
			$theme_config = theme_content($this->appHelper);
		}

		$tpl = Renderer::getMarkupTemplate('settings/display.tpl');
		return Renderer::replaceMacros($tpl, [
			'$ptitle'         => $this->t('Display Settings'),
			'$submit'         => $this->t('Save Settings'),
			'$d_tset'         => $this->t('General Theme Settings'),
			'$d_ctset'        => $this->t('Custom Theme Settings'),
			'$d_cset'         => $this->t('Content Settings'),
			'$stitle'         => $this->t('Theme settings'),
			'$timeline_title' => $this->t('Timelines'),
			'$channel_title'  => $this->t('Channels'),
			'$calendar_title' => $this->t('Calendar'),

			'$form_security_token' => self::getFormSecurityToken('settings_display'),
			'$uid'                 => $uid,

			'$theme'        => ['theme', $this->t('Display Theme:'), $theme_selected, '', $themes, true],
			'$mobile_theme' => ['mobile_theme', $this->t('Mobile Theme:'), $mobile_theme_selected, '', $mobile_themes, false],
			'$theme_config' => $theme_config,

			'$itemspage_network'        => ['itemspage_network', $this->t('Number of items to display per page:'), $itemspage_network, $this->t('Maximum of 100 items')],
			'$itemspage_mobile_network' => ['itemspage_mobile_network', $this->t('Number of items to display per page when viewed from mobile device:'), $itemspage_mobile_network, $this->t('Maximum of 100 items')],
			'$ajaxint'                  => ['browser_update', $this->t('Update browser every xx seconds'), $browser_update, $this->t('Minimum of 10 seconds. Enter -1 to disable it.')],
			'$enable_smile'             => ['enable_smile', $this->t('Display emoticons'), $enable_smile, $this->t('When enabled, emoticons are replaced with matching symbols.')],
			'$infinite_scroll'          => ['infinite_scroll', $this->t('Infinite scroll'), $infinite_scroll, $this->t('Automatic fetch new items when reaching the page end.')],
			'$enable_smart_threading'   => ['enable_smart_threading', $this->t('Enable Smart Threading'), $enable_smart_threading, $this->t('Enable the automatic suppression of extraneous thread indentation.')],
			'$enable_dislike'           => ['enable_dislike', $this->t('Display the Dislike feature'), $enable_dislike, $this->t('Display the Dislike button and dislike reactions on posts and comments.')],
			'$display_resharer'         => ['display_resharer', $this->t('Display the resharer'), $display_resharer, $this->t('Display the first resharer as icon and text on a reshared item.')],
			'$stay_local'               => ['stay_local', $this->t('Stay local'), $stay_local, $this->t("Don't go to a remote system when following a contact link.")],
			'$show_page_drop'           => ['show_page_drop', $this->t('Show the post deletion checkbox'), $show_page_drop, $this->t("Display the checkbox for the post deletion on the network page.")],
			'$display_eventlist'        => ['display_eventlist', $this->t('DIsplay the event list'), $display_eventlist, $this->t("Display the birthday reminder and event list on the network page.")],
			'$preview_mode'             => ['preview_mode', $this->t('Link preview mode'), $preview_mode, $this->t('Appearance of the link preview that is added to each post with a link.'), $preview_modes, false],
			'$hide_empty_descriptions'  => ['hide_empty_descriptions', $this->t('Hide pictures with empty alternative text'), $hide_empty_descriptions, $this->t("Don't display pictures that are missing the alternative text.")],
			'$hide_custom_emojis'       => ['hide_custom_emojis', $this->t('Hide custom emojis'), $hide_custom_emojis, $this->t("Don't display custom emojis.")],
			'$platform_icon_style'      => ['platform_icon_style', $this->t('Platform icons style'), $platform_icon_style, $this->t('Style of the platform icons'), $platform_icon_styles, false],

			'$timeline_label'       => $this->t('Label'),
			'$timeline_descriptiom' => $this->t('Description'),
			'$timeline_enable'      => $this->t('Enable'),
			'$timeline_bookmark'    => $this->t('Bookmark'),
			'$timelines'            => $timelines,
			'$timeline_explanation' => $this->t('Enable timelines that you want to see in the channels widget. Bookmark timelines that you want to see in the top menu.'),

			'$channel_languages' => ['channel_languages[]', $this->t('Channel languages:'), $channel_languages, $this->t('Select all the languages you want to see in your channels. "Unspecified" describes all posts for which no language information was detected (e.g. posts with just an image or too little text to be sure of the language). If you want to see all languages, you will need to select all items in the list.'), $languages, 'multiple'],

			'$first_day_of_week'     => ['first_day_of_week', $this->t('Beginning of week:'), $first_day_of_week, '', $weekdays, false],
			'$calendar_default_view' => ['calendar_default_view', $this->t('Default calendar view:'), $calendar_default_view, '', $calendarViews, false],
		]);
	}

	private function getAvailableTimelines(int $uid, bool $only_network = false): Timelines
	{
		$timelines = [];

		foreach ($this->network->getTimelines('') as $channel) {
			$timelines[] = $channel;
		}

		if ($only_network) {
			return new Timelines($timelines);
		}

		foreach ($this->channel->getTimelines($uid) as $channel) {
			$timelines[] = $channel;
		}

		foreach ($this->userDefinedChannel->selectByUid($uid) as $channel) {
			$timelines[] = $channel;
		}

		foreach ($this->community->getTimelines(true) as $community) {
			$timelines[] = $community;
		}

		return new Timelines($timelines);
	}
}
