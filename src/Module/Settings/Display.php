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
use Friendica\Util\Strings;
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
		$update_content          = (int)$request['update_content'];
		$embed_remote_media      = (bool)$request['embed_remote_media'];
		$embed_media             = (bool)$request['embed_media'];
		$widget_timelineorder    = trim($request['widget_timelineorder']);
		$menu_timelineorder      = trim($request['menu_timelineorder']);
		$widget_timeline_reset   = (bool)$request['widget_timeline_reset'];
		$menu_timeline_reset     = (bool)$request['menu_timeline_reset'];

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
		$this->pConfig->set($uid, 'system', 'update_content', $update_content);
		$this->pConfig->set($uid, 'system', 'no_smilies', !$enable_smile);
		$this->pConfig->set($uid, 'system', 'infinite_scroll', $infinite_scroll);
		$this->pConfig->set($uid, 'system', 'no_smart_threading', !$enable_smart_threading);
		$this->pConfig->set($uid, 'system', 'hide_dislike', !$enable_dislike);
		$this->pConfig->set($uid, 'system', 'display_resharer', $display_resharer);
		$this->pConfig->set($uid, 'system', 'stay_local', $stay_local);
		$this->pConfig->set($uid, 'system', 'show_page_drop', $show_page_drop);
		$this->pConfig->set($uid, 'system', 'display_eventlist', $display_eventlist);
		$this->pConfig->set($uid, 'system', 'preview_mode', $preview_mode);
		$this->pConfig->set($uid, 'system', 'embed_remote_media', $embed_remote_media);
		$this->pConfig->set($uid, 'system', 'embed_media', $embed_media);
		if ($widget_timeline_reset) {
			$this->pConfig->delete($uid, 'system', 'widget_timeline_order');
		} else {
			$this->pConfig->set($uid, 'system', 'widget_timeline_order', $widget_timelineorder);
		}
		if ($menu_timeline_reset) {
			$this->pConfig->delete($uid, 'system', 'menu_timeline_order');
		} else {
			$this->pConfig->set($uid, 'system', 'menu_timeline_order', $menu_timelineorder);
		}
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

		$update_content         = $this->pConfig->get($uid, 'system', 'update_content') ?? false;
		$enable_smile           = !$this->pConfig->get($uid, 'system', 'no_smilies', false);
		$infinite_scroll        = $this->pConfig->get($uid, 'system', 'infinite_scroll', true);
		$enable_smart_threading = !$this->pConfig->get($uid, 'system', 'no_smart_threading', false);
		$enable_dislike         = !$this->pConfig->get($uid, 'system', 'hide_dislike', false);
		$display_resharer       = $this->pConfig->get($uid, 'system', 'display_resharer', false);
		$stay_local             = $this->pConfig->get($uid, 'system', 'stay_local', true);
		$show_page_drop         = $this->pConfig->get($uid, 'system', 'show_page_drop', true);
		$display_eventlist      = $this->pConfig->get($uid, 'system', 'display_eventlist', true);
		$embed_remote_media     = $this->pConfig->get($uid, 'system', 'embed_remote_media', false);
		$embed_media            = $this->pConfig->get($uid, 'system', 'embed_media', false);

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

		$preview_mode  = $this->pConfig->get($uid, 'system', 'preview_mode', BBCode::PREVIEW_AUTO);
		$preview_modes = [
			BBCode::PREVIEW_NONE     => $this->t('No preview'),
			BBCode::PREVIEW_NO_IMAGE => $this->t('No image'),
			BBCode::PREVIEW_SMALL    => $this->t('Small Image'),
			BBCode::PREVIEW_LARGE    => $this->t('Large Image'),
			BBCode::PREVIEW_AUTO     => $this->t('Automatic image size'),
		];

		$bookmarked_timelines = $this->pConfig->get($uid, 'system', 'network_timelines', $this->getAvailableTimelines($uid, true)->column('code'));
		$enabled_timelines    = $this->pConfig->get($uid, 'system', 'enabled_timelines', $this->getAvailableTimelines($uid, false)->column('code'));
		$channel_languages    = User::getWantedLanguages($uid);
		$languages            = $this->l10n->getLanguageCodes(true, true);

		$timelines = [];
		foreach ($this->getAvailableTimelines($uid) as $timeline) {
			$timelines[] = [
				'enable'   => ["enable[{$timeline->code}]", $timeline->label, in_array($timeline->code, $enabled_timelines), $timeline->description],
				'bookmark' => ["bookmark[{$timeline->code}]", $timeline->label, in_array($timeline->code, $bookmarked_timelines), $timeline->description],
			];
		}
		/*  GET CUSTOM TIMELINE ORDERS IF ANY
			=================================
			First we see if there is a custom order saved in user prefs, if there is we set working array to that.
			If we have an array create a temporary array with the items in the correct order.
			Lastly we modify the $timelines array with our new order for "enable", "bookmark", or both.
		*/
		$widget_timeline_order = json_decode($this->pConfig->get($uid, 'system', 'widget_timeline_order'));
		$menu_timeline_order   = json_decode($this->pConfig->get($uid, 'system', 'menu_timeline_order'));
		$temp_widget_order     = [];
		$temp_menu_order       = [];
		// do the sidebar widget order first...
		if (!empty($widget_timeline_order)) {
			$tmp = [];
			$xtr = [];
			foreach ($widget_timeline_order as $order) {
				foreach ($timelines as $timeline) {
					$name = str_replace(['enable[',']'], '', $timeline['enable'][0]);
					if ($name == $order) {
						$tmp[]['enable'] = $timeline['enable'];
					}
				}
			}
			// there could be custom or add-on channels not in our array, append those
			foreach ($timelines as $timeline) {
				$name = str_replace(['enable[',']'], '', $timeline['enable'][0]);
				if (!in_array($name, $widget_timeline_order)) {
					$xtr[]['enable'] = $timeline['enable'];
				}
			}
			// combine our two temp arrays into one big temp array
			$temp_widget_order = array_merge($tmp, $xtr);
		}
		// do the top menu order next...
		if (!empty($menu_timeline_order)) {
			$tmp = [];
			$xtr = [];
			foreach ($menu_timeline_order as $order) {
				foreach ($timelines as $timeline) {
					$name = str_replace(['bookmark[',']'], '', $timeline['bookmark'][0]);
					if ($name == $order) {
						$tmp[]['bookmark'] = $timeline['bookmark'];
					}
				}
			}
			// there could be custom or add-on channels unaccounted for in our array, append them
			foreach ($timelines as $timeline) {
				$name = str_replace(['bookmark[',']'], '', $timeline['bookmark'][0]);
				if (!in_array($name, $menu_timeline_order)) {
					$xtr[]['bookmark'] = $timeline['bookmark'];
				}
			}
			// combine our two temp arrays into one big temp array
			$temp_menu_order = array_merge($tmp, $xtr);
		}
		/*  now we need to alter the original timelines array directly...
			in theory populated temp arrays should be same length as timelines
		*/
		for ($t = 0; $t < count($timelines);$t++) {
			// only mod from populated widget array
			if (count($temp_widget_order) > 0) {
				$timelines[$t]['enable'] = $temp_widget_order[$t]['enable'];
			}
			// only mod from populated menu array
			if (count($temp_menu_order) > 0) {
				$timelines[$t]['bookmark'] = $temp_menu_order[$t]['bookmark'];
			}
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
			'$ptitle'              => $this->t('Display Settings'),
			'$submit'              => $this->t('Save Settings'),
			'$d_cset'              => $this->t('Content Settings'),
			'$stitle'              => $this->t('Theme settings'),
			'$themes_title'        => $this->t('Themes'),
			'$themes_settings_for' => $this->t('Settings for %s', Strings::ucFirst($theme_selected)),
			'$theme_changed_text'  => $this->t('Note: If you switch the theme, you need to save changes before you can see the settings for the new theme below.'),
			'$timeline_title'      => $this->t('Timelines'),
			'$channel_title'       => $this->t('Channels'),
			'$calendar_title'      => $this->t('Calendar'),
			'$sortable'            => $this->t('Drag to reorder or tab to item with keyboard and move up/down with arrow keys'),
			'$reset_widget'        => [
				'0' => 'widget_timeline_reset',
				'1' => $this->t('Reset order')
			],
			'$reset_menu' => [
				'0' => 'menu_timeline_reset',
				'1' => $this->t('Reset order')
			],

			'$form_security_token' => self::getFormSecurityToken('settings_display'),
			'$uid'                 => $uid,

			'$theme'        => ['theme', $this->t('Display theme'), $theme_selected, '', $themes, true],
			'$mobile_theme' => ['mobile_theme', $this->t('Mobile theme'), $mobile_theme_selected, '', $mobile_themes, false],
			'$theme_config' => $theme_config,

			'$itemspage_network'        => ['itemspage_network', $this->t('Number of items to display per page:'), $itemspage_network, $this->t('Maximum of 100 items')],
			'$itemspage_mobile_network' => ['itemspage_mobile_network', $this->t('Number of items to display per page when viewed from mobile device:'), $itemspage_mobile_network, $this->t('Maximum of 100 items')],
			'$update_content'           => ['update_content', $this->t('Regularly update the page content'), $update_content, $this->t('When enabled, new content on network, community and channels are added on top.')],
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
			'$embed_remote_media'       => ['embed_remote_media', $this->t('Embed remote media'), $embed_remote_media, $this->t('When enabled, remote media will be embedded in the post, like for example YouTube videos.')],
			'$embed_media'              => ['embed_media', $this->t('Embed supported media'), $embed_media, $this->t('When enabled, remote media will be embedded in the post instead of using the local player if this is supported by the remote system. This is useful for media where the remote player is better than the local one, like for example Peertube videos.')],

			'$timeline_label'       => $this->t('Label'),
			'$timeline_descriptiom' => $this->t('Description'),
			'$timeline_enable'      => $this->t('Channels Widget'),
			'$timeline_bookmark'    => $this->t('Top Menu'),
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
