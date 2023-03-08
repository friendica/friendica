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

namespace Friendica\Module\Admin;

use Friendica\App;
use Friendica\Core\Renderer;
use Friendica\Core\Search;
use Friendica\Core\System;
use Friendica\Core\Theme;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Module\BaseAdmin;
use Friendica\Module\Conversation\Community;
use Friendica\Module\Register;
use Friendica\Navigation\SystemMessages;
use Friendica\Protocol\Relay;
use Friendica\Util\BasePath;
use Friendica\Util\EMailer\MailBuilder;
use Friendica\Util\Strings;

class Site extends BaseAdmin
{
	protected function post(array $request = [])
	{
		self::checkAdminAccess();

		self::checkFormSecurityTokenRedirectOnError('/admin/site', 'admin_site');

		if (!empty($_POST['republish_directory'])) {
			Worker::add(Worker::PRIORITY_LOW, 'Directory');
			return;
		}

		if (empty($_POST['page_site'])) {
			return;
		}

		$sitename         = (!empty($_POST['sitename'])         ? trim($_POST['sitename'])      : '');
		$sender_email     = (!empty($_POST['sender_email'])     ? trim($_POST['sender_email'])  : '');
		$banner           = (!empty($_POST['banner'])           ? trim($_POST['banner'])                             : false);
		$email_banner     = (!empty($_POST['email_banner'])     ? trim($_POST['email_banner'])                       : false);
		$shortcut_icon    = (!empty($_POST['shortcut_icon'])    ? trim($_POST['shortcut_icon']) : '');
		$touch_icon       = (!empty($_POST['touch_icon'])       ? trim($_POST['touch_icon'])    : '');
		$additional_info  = (!empty($_POST['additional_info'])  ? trim($_POST['additional_info'])                    : '');
		$language         = (!empty($_POST['language'])         ? trim($_POST['language'])      : '');
		$theme            = (!empty($_POST['theme'])            ? trim($_POST['theme'])         : '');
		$theme_mobile     = (!empty($_POST['theme_mobile'])     ? trim($_POST['theme_mobile'])  : '');
		$maximagesize     = (!empty($_POST['maximagesize'])     ? trim($_POST['maximagesize'])              : 0);
		$maximagelength   = (!empty($_POST['maximagelength'])   ? intval(trim($_POST['maximagelength']))             : -1);
		$jpegimagequality = (!empty($_POST['jpegimagequality']) ? intval(trim($_POST['jpegimagequality']))           : 100);

		$register_policy        = (!empty($_POST['register_policy'])         ? intval(trim($_POST['register_policy']))             : 0);
		$daily_registrations    = (!empty($_POST['max_daily_registrations']) ? intval(trim($_POST['max_daily_registrations']))     : 0);
		$abandon_days           = (!empty($_POST['abandon_days'])            ? intval(trim($_POST['abandon_days']))                : 0);

		$register_text          = (!empty($_POST['register_text'])           ? strip_tags(trim($_POST['register_text']))           : '');

		$allowed_sites          = (!empty($_POST['allowed_sites'])           ? trim($_POST['allowed_sites'])  : '');
		$allowed_email          = (!empty($_POST['allowed_email'])           ? trim($_POST['allowed_email'])  : '');
		$forbidden_nicknames    = (!empty($_POST['forbidden_nicknames'])     ? strtolower(trim($_POST['forbidden_nicknames'])) : '');
		$system_actor_name      = (!empty($_POST['system_actor_name'])       ? trim($_POST['system_actor_name']) : '');
		$no_oembed_rich_content = !empty($_POST['no_oembed_rich_content']);
		$allowed_oembed         = (!empty($_POST['allowed_oembed'])          ? trim($_POST['allowed_oembed']) : '');
		$block_public           = !empty($_POST['block_public']);
		$force_publish          = !empty($_POST['publish_all']);
		$global_directory       = (!empty($_POST['directory'])               ? trim($_POST['directory'])      : '');
		$newuser_private        = !empty($_POST['newuser_private']);
		$enotify_no_content     = !empty($_POST['enotify_no_content']);
		$private_addons         = !empty($_POST['private_addons']);
		$disable_embedded       = !empty($_POST['disable_embedded']);
		$allow_users_remote_self = !empty($_POST['allow_users_remote_self']);
		$explicit_content       = !empty($_POST['explicit_content']);
		$proxify_content        = !empty($_POST['proxify_content']);
		$cache_contact_avatar   = !empty($_POST['cache_contact_avatar']);

		$enable_multi_reg       = !empty($_POST['enable_multi_reg']);
		$enable_openid          = !empty($_POST['enable_openid']);
		$enable_regfullname     = !empty($_POST['enable_regfullname']);
		$register_notification  = !empty($_POST['register_notification']);
		$community_page_style   = (!empty($_POST['community_page_style']) ? intval(trim($_POST['community_page_style'])) : 0);
		$max_author_posts_community_page = (!empty($_POST['max_author_posts_community_page']) ? intval(trim($_POST['max_author_posts_community_page'])) : 0);

		$verifyssl              = !empty($_POST['verifyssl']);
		$proxyuser              = (!empty($_POST['proxyuser'])              ? trim($_POST['proxyuser']) : '');
		$proxy                  = (!empty($_POST['proxy'])                  ? trim($_POST['proxy'])     : '');
		$timeout                = (!empty($_POST['timeout'])                ? intval(trim($_POST['timeout']))                : 60);
		$maxloadavg             = (!empty($_POST['maxloadavg'])             ? intval(trim($_POST['maxloadavg']))             : 20);
		$min_memory             = (!empty($_POST['min_memory'])             ? intval(trim($_POST['min_memory']))             : 0);
		$optimize_tables        = (!empty($_POST['optimize_tables'])        ? intval(trim($_POST['optimize_tables']))        : false);
		$contact_discovery      = (!empty($_POST['contact_discovery'])      ? intval(trim($_POST['contact_discovery']))      : Contact\Relation::DISCOVERY_NONE);
		$synchronize_directory  = (!empty($_POST['synchronize_directory'])  ? intval(trim($_POST['synchronize_directory']))  : false);
		$poco_requery_days      = (!empty($_POST['poco_requery_days'])      ? intval(trim($_POST['poco_requery_days']))      : 7);
		$poco_discovery         = (!empty($_POST['poco_discovery'])         ? intval(trim($_POST['poco_discovery']))         : false);
		$poco_local_search      = !empty($_POST['poco_local_search']);
		$nodeinfo               = !empty($_POST['nodeinfo']);
		$mail_enabled           = !empty($_POST['mail_enabled']);
		$ostatus_enabled        = !empty($_POST['ostatus_enabled']);
		$diaspora_enabled       = !empty($_POST['diaspora_enabled']);
		$force_ssl              = !empty($_POST['force_ssl']);
		$show_help              = !empty($_POST['show_help']);
		$dbclean                = !empty($_POST['dbclean']);
		$dbclean_expire_days    = (!empty($_POST['dbclean_expire_days'])    ? intval($_POST['dbclean_expire_days'])           : 0);
		$dbclean_unclaimed      = (!empty($_POST['dbclean_unclaimed'])      ? intval($_POST['dbclean_unclaimed'])             : 0);
		$dbclean_expire_conv    = (!empty($_POST['dbclean_expire_conv'])    ? intval($_POST['dbclean_expire_conv'])           : 0);
		$suppress_tags          = !empty($_POST['suppress_tags']);
		$max_comments           = (!empty($_POST['max_comments'])           ? intval($_POST['max_comments'])                  : 0);
		$max_display_comments   = (!empty($_POST['max_display_comments'])   ? intval($_POST['max_display_comments'])          : 0);
		$temppath               = (!empty($_POST['temppath'])               ? trim($_POST['temppath'])   : '');
		$singleuser             = (!empty($_POST['singleuser'])             ? trim($_POST['singleuser']) : '');
		$only_tag_search        = !empty($_POST['only_tag_search']);
		$compute_group_counts   = !empty($_POST['compute_group_counts']);
		$check_new_version_url  = (!empty($_POST['check_new_version_url'])  ? trim($_POST['check_new_version_url']) : 'none');

		$worker_queues    = (!empty($_POST['worker_queues'])                ? intval($_POST['worker_queues'])                 : 10);
		$worker_fastlane  = !empty($_POST['worker_fastlane']);

		$relay_directly    = !empty($_POST['relay_directly']);
		$relay_scope       = (!empty($_POST['relay_scope'])       ? trim($_POST['relay_scope'])        : '');
		$relay_server_tags = (!empty($_POST['relay_server_tags']) ? trim($_POST['relay_server_tags'])  : '');
		$relay_deny_tags   = (!empty($_POST['relay_deny_tags'])   ? trim($_POST['relay_deny_tags'])    : '');
		$relay_user_tags   = !empty($_POST['relay_user_tags']);
		$active_panel      = (!empty($_POST['active_panel'])      ? "#" . trim($_POST['active_panel']) : '');

		$transactionConfig = DI::config()->beginTransaction();

		// Has the directory url changed? If yes, then resubmit the existing profiles there
		if ($global_directory != DI::config()->get('system', 'directory') && ($global_directory != '')) {
			$transactionConfig->set('system', 'directory', $global_directory);
			Worker::add(Worker::PRIORITY_LOW, 'Directory');
		}

		if (DI::baseUrl()->getPath() != "") {
			$diaspora_enabled = false;
		}

		$transactionConfig->set('system', 'maxloadavg'            , $maxloadavg);
		$transactionConfig->set('system', 'min_memory'            , $min_memory);
		$transactionConfig->set('system', 'optimize_tables'       , $optimize_tables);
		$transactionConfig->set('system', 'contact_discovery'     , $contact_discovery);
		$transactionConfig->set('system', 'synchronize_directory' , $synchronize_directory);
		$transactionConfig->set('system', 'poco_requery_days'     , $poco_requery_days);
		$transactionConfig->set('system', 'poco_discovery'        , $poco_discovery);
		$transactionConfig->set('system', 'poco_local_search'     , $poco_local_search);
		$transactionConfig->set('system', 'nodeinfo'              , $nodeinfo);
		$transactionConfig->set('config', 'sitename'              , $sitename);
		$transactionConfig->set('config', 'sender_email'          , $sender_email);
		$transactionConfig->set('system', 'suppress_tags'         , $suppress_tags);
		$transactionConfig->set('system', 'shortcut_icon'         , $shortcut_icon);
		$transactionConfig->set('system', 'touch_icon'            , $touch_icon);

		if ($banner == "") {
			$transactionConfig->delete('system', 'banner');
		} else {
			$transactionConfig->set('system', 'banner', $banner);
		}

		if (empty($email_banner)) {
			$transactionConfig->delete('system', 'email_banner');
		} else {
			$transactionConfig->set('system', 'email_banner', $email_banner);
		}

		if (empty($additional_info)) {
			$transactionConfig->delete('config', 'info');
		} else {
			$transactionConfig->set('config', 'info', $additional_info);
		}
		$transactionConfig->set('system', 'language', $language);
		$transactionConfig->set('system', 'theme', $theme);
		Theme::install($theme);

		if ($theme_mobile == '---') {
			$transactionConfig->delete('system', 'mobile-theme');
		} else {
			$transactionConfig->set('system', 'mobile-theme', $theme_mobile);
		}
		if ($singleuser == '---') {
			$transactionConfig->delete('system', 'singleuser');
		} else {
			$transactionConfig->set('system', 'singleuser', $singleuser);
		}
		if (preg_match('/\d+(?:\s*[kmg])?/i', $maximagesize)) {
			$transactionConfig->set('system', 'maximagesize', $maximagesize);
		} else {
			DI::sysmsg()->addNotice(DI::l10n()->t('%s is no valid input for maximum image size', $maximagesize));
		}
		$transactionConfig->set('system', 'max_image_length'       , $maximagelength);
		$transactionConfig->set('system', 'jpeg_quality'           , $jpegimagequality);

		$transactionConfig->set('config', 'register_policy'        , $register_policy);
		$transactionConfig->set('system', 'max_daily_registrations', $daily_registrations);
		$transactionConfig->set('system', 'account_abandon_days'   , $abandon_days);
		$transactionConfig->set('config', 'register_text'          , $register_text);
		$transactionConfig->set('system', 'allowed_sites'          , $allowed_sites);
		$transactionConfig->set('system', 'allowed_email'          , $allowed_email);
		$transactionConfig->set('system', 'forbidden_nicknames'    , $forbidden_nicknames);
		$transactionConfig->set('system', 'system_actor_name'      , $system_actor_name);
		$transactionConfig->set('system', 'no_oembed_rich_content' , $no_oembed_rich_content);
		$transactionConfig->set('system', 'allowed_oembed'         , $allowed_oembed);
		$transactionConfig->set('system', 'block_public'           , $block_public);
		$transactionConfig->set('system', 'publish_all'            , $force_publish);
		$transactionConfig->set('system', 'newuser_private'        , $newuser_private);
		$transactionConfig->set('system', 'enotify_no_content'     , $enotify_no_content);
		$transactionConfig->set('system', 'disable_embedded'       , $disable_embedded);
		$transactionConfig->set('system', 'allow_users_remote_self', $allow_users_remote_self);
		$transactionConfig->set('system', 'explicit_content'       , $explicit_content);
		$transactionConfig->set('system', 'proxify_content'        , $proxify_content);
		$transactionConfig->set('system', 'cache_contact_avatar'   , $cache_contact_avatar);
		$transactionConfig->set('system', 'check_new_version_url'  , $check_new_version_url);

		$transactionConfig->set('system', 'block_extended_register', !$enable_multi_reg);
		$transactionConfig->set('system', 'no_openid'              , !$enable_openid);
		$transactionConfig->set('system', 'no_regfullname'         , !$enable_regfullname);
		$transactionConfig->set('system', 'register_notification'  , $register_notification);
		$transactionConfig->set('system', 'community_page_style'   , $community_page_style);
		$transactionConfig->set('system', 'max_author_posts_community_page', $max_author_posts_community_page);
		$transactionConfig->set('system', 'verifyssl'              , $verifyssl);
		$transactionConfig->set('system', 'proxyuser'              , $proxyuser);
		$transactionConfig->set('system', 'proxy'                  , $proxy);
		$transactionConfig->set('system', 'curl_timeout'           , $timeout);
		$transactionConfig->set('system', 'imap_disabled'          , !$mail_enabled && function_exists('imap_open'));
		$transactionConfig->set('system', 'ostatus_disabled'       , !$ostatus_enabled);
		$transactionConfig->set('system', 'diaspora_enabled'       , $diaspora_enabled);

		$transactionConfig->set('config', 'private_addons'         , $private_addons);

		$transactionConfig->set('system', 'force_ssl'              , $force_ssl);
		$transactionConfig->set('system', 'hide_help'              , !$show_help);

		$transactionConfig->set('system', 'dbclean'                , $dbclean);
		$transactionConfig->set('system', 'dbclean-expire-days'    , $dbclean_expire_days);
		$transactionConfig->set('system', 'dbclean_expire_conversation', $dbclean_expire_conv);

		if ($dbclean_unclaimed == 0) {
			$dbclean_unclaimed = $dbclean_expire_days;
		}

		$transactionConfig->set('system', 'dbclean-expire-unclaimed', $dbclean_unclaimed);

		$transactionConfig->set('system', 'max_comments', $max_comments);
		$transactionConfig->set('system', 'max_display_comments', $max_display_comments);

		if ($temppath != '') {
			$temppath = BasePath::getRealPath($temppath);
		}

		$transactionConfig->set('system', 'temppath', $temppath);

		$transactionConfig->set('system', 'only_tag_search'  , $only_tag_search);
		$transactionConfig->set('system', 'compute_group_counts', $compute_group_counts);

		$transactionConfig->set('system', 'worker_queues'    , $worker_queues);
		$transactionConfig->set('system', 'worker_fastlane'  , $worker_fastlane);

		$transactionConfig->set('system', 'relay_directly'   , $relay_directly);
		$transactionConfig->set('system', 'relay_scope'      , $relay_scope);
		$transactionConfig->set('system', 'relay_server_tags', $relay_server_tags);
		$transactionConfig->set('system', 'relay_deny_tags'  , $relay_deny_tags);
		$transactionConfig->set('system', 'relay_user_tags'  , $relay_user_tags);

		$transactionConfig->commit();

		DI::baseUrl()->redirect('admin/site' . $active_panel);
	}

	protected function content(array $request = []): string
	{
		parent::content();

		/* Installed langs */
		$lang_choices = DI::l10n()->getAvailableLanguages();

		if (DI::config()->get('system', 'directory_submit_url') &&
			!DI::config()->get('system', 'directory')) {
			DI::config()->set('system', 'directory', dirname(DI::config()->get('system', 'directory_submit_url')));
			DI::config()->delete('system', 'directory_submit_url');
		}

		/* Installed themes */
		$theme_choices = [];
		$theme_choices_mobile = [];
		$theme_choices_mobile['---'] = DI::l10n()->t('No special theme for mobile devices');
		$files = glob('view/theme/*');
		if (is_array($files)) {
			$allowed_theme_list = DI::config()->get('system', 'allowed_themes');

			foreach ($files as $file) {
				if (intval(file_exists($file . '/unsupported'))) {
					continue;
				}

				$f = basename($file);

				// Only show allowed themes here
				if (($allowed_theme_list != '') && !strstr($allowed_theme_list, $f)) {
					continue;
				}

				$theme_name = ((file_exists($file . '/experimental')) ? DI::l10n()->t('%s - (Experimental)', $f) : $f);

				if (file_exists($file . '/mobile')) {
					$theme_choices_mobile[$f] = $theme_name;
				} else {
					$theme_choices[$f] = $theme_name;
				}
			}
		}

		/* Community page style */
		$community_page_style_choices = [
			Community::DISABLED         => DI::l10n()->t('No community page'),
			Community::DISABLED_VISITOR => DI::l10n()->t('No community page for visitors'),
			Community::LOCAL            => DI::l10n()->t('Public postings from users of this site'),
			Community::GLOBAL           => DI::l10n()->t('Public postings from the federated network'),
			Community::LOCAL_AND_GLOBAL => DI::l10n()->t('Public postings from local users and the federated network')
		];

		/* get user names to make the install a personal install of X */
		// @TODO Move to Model\User::getNames()
		$user_names = [];
		$user_names['---'] = DI::l10n()->t('Multi user instance');

		$usersStmt = DBA::select('user', ['username', 'nickname'], ['account_removed' => 0, 'account_expired' => 0]);
		foreach (DBA::toArray($usersStmt) as $user) {
			$user_names[$user['nickname']] = $user['username'];
		}

		/* Banner */
		$banner = DI::config()->get('system', 'banner');

		$email_banner = DI::config()->get('system', 'email_banner');

		if ($email_banner == false) {
			$email_banner = MailBuilder::DEFAULT_EMAIL_BANNER;
		}

		$additional_info = DI::config()->get('config', 'info');

		// Automatically create temporary paths
		System::getTempPath();

		/* Register policy */
		$register_choices = [
			Register::CLOSED => DI::l10n()->t('Closed'),
			Register::APPROVE => DI::l10n()->t('Requires approval'),
			Register::OPEN => DI::l10n()->t('Open')
		];

		$check_git_version_choices = [
			'none' => DI::l10n()->t('Don\'t check'),
			'stable' => DI::l10n()->t('check the stable version'),
			'develop' => DI::l10n()->t('check the development version')
		];

		$discovery_choices = [
			Contact\Relation::DISCOVERY_NONE => DI::l10n()->t('none'),
			Contact\Relation::DISCOVERY_LOCAL => DI::l10n()->t('Local contacts'),
			Contact\Relation::DISCOVERY_INTERACTOR => DI::l10n()->t('Interactors'),
			// "All" is deactivated until we are sure not to put too much stress on the fediverse with this
			// ContactRelation::DISCOVERY_ALL => DI::l10n()->t('All'),
		];

		$diaspora_able = (DI::baseUrl()->getPath() == '');

		$t = Renderer::getMarkupTemplate('admin/site.tpl');
		return Renderer::replaceMacros($t, [
			'$title'             => DI::l10n()->t('Administration'),
			'$page'              => DI::l10n()->t('Site'),
			'$general_info'      => DI::l10n()->t('General Information'),
			'$submit'            => DI::l10n()->t('Save Settings'),
			'$republish'         => DI::l10n()->t('Republish users to directory'),
			'$registration'      => DI::l10n()->t('Registration'),
			'$upload'            => DI::l10n()->t('File upload'),
			'$corporate'         => DI::l10n()->t('Policies'),
			'$advanced'          => DI::l10n()->t('Advanced'),
			'$portable_contacts' => DI::l10n()->t('Auto Discovered Contact Directory'),
			'$performance'       => DI::l10n()->t('Performance'),
			'$worker_title'      => DI::l10n()->t('Worker'),
			'$relay_title'       => DI::l10n()->t('Message Relay'),
			'$relay_description' => DI::l10n()->t('Use the command "console relay" in the command line to add or remove relays.'),
			'$no_relay_list'     => DI::l10n()->t('The system is not subscribed to any relays at the moment.'),
			'$relay_list_title'  => DI::l10n()->t('The system is currently subscribed to the following relays:'),
			'$relay_list'        => Relay::getList(['url']),
			'$relocate'          => DI::l10n()->t('Relocate Node'),
			'$relocate_msg'      => DI::l10n()->t('Relocating your node enables you to change the DNS domain of this node and keep all the existing users and posts. This process takes a while and can only be started from the relocate console command like this:'),
			'$relocate_cmd'      => DI::l10n()->t('(Friendica directory)# bin/console relocate https://newdomain.com'),

			// name, label, value, help string, extra data...
			'$sitename'         => ['sitename', DI::l10n()->t('Site name'), DI::config()->get('config', 'sitename'), ''],
			'$sender_email'     => ['sender_email', DI::l10n()->t('Sender Email'), DI::config()->get('config', 'sender_email'), DI::l10n()->t('The email address your server shall use to send notification emails from.'), '', '', 'email'],
			'$system_actor_name' => ['system_actor_name', DI::l10n()->t('Name of the system actor'), User::getActorName(), DI::l10n()->t("Name of the internal system account that is used to perform ActivityPub requests. This must be an unused username. If set, this can't be changed again.")],
			'$banner'           => ['banner', DI::l10n()->t('Banner/Logo'), $banner, ''],
			'$email_banner'     => ['email_banner', DI::l10n()->t('Email Banner/Logo'), $email_banner, ''],
			'$shortcut_icon'    => ['shortcut_icon', DI::l10n()->t('Shortcut icon'), DI::config()->get('system', 'shortcut_icon'), DI::l10n()->t('Link to an icon that will be used for browsers.')],
			'$touch_icon'       => ['touch_icon', DI::l10n()->t('Touch icon'), DI::config()->get('system', 'touch_icon'), DI::l10n()->t('Link to an icon that will be used for tablets and mobiles.')],
			'$additional_info'  => ['additional_info', DI::l10n()->t('Additional Info'), $additional_info, DI::l10n()->t('For public servers: you can add additional information here that will be listed at %s/servers.', Search::getGlobalDirectory())],
			'$language'         => ['language', DI::l10n()->t('System language'), DI::config()->get('system', 'language'), '', $lang_choices],
			'$theme'            => ['theme', DI::l10n()->t('System theme'), DI::config()->get('system', 'theme'), DI::l10n()->t('Default system theme - may be over-ridden by user profiles - <a href="%s" id="cnftheme">Change default theme settings</a>', DI::baseUrl() . '/admin/themes'), $theme_choices],
			'$theme_mobile'     => ['theme_mobile', DI::l10n()->t('Mobile system theme'), DI::config()->get('system', 'mobile-theme', '---'), DI::l10n()->t('Theme for mobile devices'), $theme_choices_mobile],
			'$force_ssl'        => ['force_ssl', DI::l10n()->t('Force SSL'), DI::config()->get('system', 'force_ssl'), DI::l10n()->t('Force all Non-SSL requests to SSL - Attention: on some systems it could lead to endless loops.')],
			'$show_help'        => ['show_help', DI::l10n()->t('Show help entry from navigation menu'), !DI::config()->get('system', 'hide_help'), DI::l10n()->t('Displays the menu entry for the Help pages from the navigation menu. It is always accessible by calling /help directly.')],
			'$singleuser'       => ['singleuser', DI::l10n()->t('Single user instance'), DI::config()->get('system', 'singleuser', '---'), DI::l10n()->t('Make this instance multi-user or single-user for the named user'), $user_names],

			'$maximagesize'     => ['maximagesize', DI::l10n()->t('Maximum image size'), DI::config()->get('system', 'maximagesize'), DI::l10n()->t('Maximum size in bytes of uploaded images. Default is 0, which means no limits. You can put k, m, or g behind the desired value for KiB, MiB, GiB, respectively.
													The value of <code>upload_max_filesize</code> in your <code>PHP.ini</code> needs be set to at least the desired limit.
													Currently <code>upload_max_filesize</code> is set to %s (%s byte)', Strings::formatBytes(Strings::getBytesFromShorthand(ini_get('upload_max_filesize'))), Strings::getBytesFromShorthand(ini_get('upload_max_filesize'))),
													'', 'pattern="\d+(?:\s*[kmg])?"'],
			'$maximagelength'   => ['maximagelength', DI::l10n()->t('Maximum image length'), DI::config()->get('system', 'max_image_length'), DI::l10n()->t('Maximum length in pixels of the longest side of uploaded images. Default is -1, which means no limits.')],
			'$jpegimagequality' => ['jpegimagequality', DI::l10n()->t('JPEG image quality'), DI::config()->get('system', 'jpeg_quality'), DI::l10n()->t('Uploaded JPEGS will be saved at this quality setting [0-100]. Default is 100, which is full quality.')],

			'$register_policy'        => ['register_policy', DI::l10n()->t('Register policy'), DI::config()->get('config', 'register_policy'), '', $register_choices],
			'$daily_registrations'    => ['max_daily_registrations', DI::l10n()->t('Maximum Daily Registrations'), DI::config()->get('system', 'max_daily_registrations'), DI::l10n()->t('If registration is permitted above, this sets the maximum number of new user registrations to accept per day.  If register is set to closed, this setting has no effect.')],
			'$register_text'          => ['register_text', DI::l10n()->t('Register text'), DI::config()->get('config', 'register_text'), DI::l10n()->t('Will be displayed prominently on the registration page. You can use BBCode here.')],
			'$forbidden_nicknames'    => ['forbidden_nicknames', DI::l10n()->t('Forbidden Nicknames'), DI::config()->get('system', 'forbidden_nicknames'), DI::l10n()->t('Comma separated list of nicknames that are forbidden from registration. Preset is a list of role names according RFC 2142.')],
			'$abandon_days'           => ['abandon_days', DI::l10n()->t('Accounts abandoned after x days'), DI::config()->get('system', 'account_abandon_days'), DI::l10n()->t('Will not waste system resources polling external sites for abandonded accounts. Enter 0 for no time limit.')],
			'$allowed_sites'          => ['allowed_sites', DI::l10n()->t('Allowed friend domains'), DI::config()->get('system', 'allowed_sites'), DI::l10n()->t('Comma separated list of domains which are allowed to establish friendships with this site. Wildcards are accepted. Empty to allow any domains')],
			'$allowed_email'          => ['allowed_email', DI::l10n()->t('Allowed email domains'), DI::config()->get('system', 'allowed_email'), DI::l10n()->t('Comma separated list of domains which are allowed in email addresses for registrations to this site. Wildcards are accepted. Empty to allow any domains')],
			'$no_oembed_rich_content' => ['no_oembed_rich_content', DI::l10n()->t('No OEmbed rich content'), DI::config()->get('system', 'no_oembed_rich_content'), DI::l10n()->t('Don\'t show the rich content (e.g. embedded PDF), except from the domains listed below.')],
			'$allowed_oembed'         => ['allowed_oembed', DI::l10n()->t('Trusted third-party domains'), DI::config()->get('system', 'allowed_oembed'), DI::l10n()->t('Comma separated list of domains from which content is allowed to be embedded in posts like with OEmbed. All sub-domains of the listed domains are allowed as well.')],
			'$block_public'           => ['block_public', DI::l10n()->t('Block public'), DI::config()->get('system', 'block_public'), DI::l10n()->t('Check to block public access to all otherwise public personal pages on this site unless you are currently logged in.')],
			'$force_publish'          => ['publish_all', DI::l10n()->t('Force publish'), DI::config()->get('system', 'publish_all'), DI::l10n()->t('Check to force all profiles on this site to be listed in the site directory.') . '<strong>' . DI::l10n()->t('Enabling this may violate privacy laws like the GDPR') . '</strong>'],
			'$global_directory'       => ['directory', DI::l10n()->t('Global directory URL'), DI::config()->get('system', 'directory'), DI::l10n()->t('URL to the global directory. If this is not set, the global directory is completely unavailable to the application.')],
			'$newuser_private'        => ['newuser_private', DI::l10n()->t('Private posts by default for new users'), DI::config()->get('system', 'newuser_private'), DI::l10n()->t('Set default post permissions for all new members to the default privacy group rather than public.')],
			'$enotify_no_content'     => ['enotify_no_content', DI::l10n()->t('Don\'t include post content in email notifications'), DI::config()->get('system', 'enotify_no_content'), DI::l10n()->t('Don\'t include the content of a post/comment/private message/etc. in the email notifications that are sent out from this site, as a privacy measure.')],
			'$private_addons'         => ['private_addons', DI::l10n()->t('Disallow public access to addons listed in the apps menu.'), DI::config()->get('config', 'private_addons'), DI::l10n()->t('Checking this box will restrict addons listed in the apps menu to members only.')],
			'$disable_embedded'       => ['disable_embedded', DI::l10n()->t('Don\'t embed private images in posts'), DI::config()->get('system', 'disable_embedded'), DI::l10n()->t('Don\'t replace locally-hosted private photos in posts with an embedded copy of the image. This means that contacts who receive posts containing private photos will have to authenticate and load each image, which may take a while.')],
			'$explicit_content'       => ['explicit_content', DI::l10n()->t('Explicit Content'), DI::config()->get('system', 'explicit_content'), DI::l10n()->t('Set this to announce that your node is used mostly for explicit content that might not be suited for minors. This information will be published in the node information and might be used, e.g. by the global directory, to filter your node from listings of nodes to join. Additionally a note about this will be shown at the user registration page.')],
			'$proxify_content'        => ['proxify_content', DI::l10n()->t('Proxify external content'), DI::config()->get('system', 'proxify_content'), DI::l10n()->t('Route external content via the proxy functionality. This is used for example for some OEmbed accesses and in some other rare cases.')],
			'$cache_contact_avatar'   => ['cache_contact_avatar', DI::l10n()->t('Cache contact avatars'), DI::config()->get('system', 'cache_contact_avatar'), DI::l10n()->t('Locally store the avatar pictures of the contacts. This uses a lot of storage space but it increases the performance.')],
			'$allow_users_remote_self'=> ['allow_users_remote_self', DI::l10n()->t('Allow Users to set remote_self'), DI::config()->get('system', 'allow_users_remote_self'), DI::l10n()->t('With checking this, every user is allowed to mark every contact as a remote_self in the repair contact dialog. Setting this flag on a contact causes mirroring every posting of that contact in the users stream.')],
			'$enable_multi_reg'       => ['enable_multi_reg', DI::l10n()->t('Enable multiple registrations'), !DI::config()->get('system', 'block_extended_register'), DI::l10n()->t('Enable users to register additional accounts for use as pages.')],
			'$enable_openid'          => ['enable_openid', DI::l10n()->t('Enable OpenID'), !DI::config()->get('system', 'no_openid'), DI::l10n()->t('Enable OpenID support for registration and logins.')],
			'$enable_regfullname'     => ['enable_regfullname', DI::l10n()->t('Enable Fullname check'), !DI::config()->get('system', 'no_regfullname'), DI::l10n()->t('Enable check to only allow users to register with a space between the first name and the last name in their full name.')],
			'$register_notification'  => ['register_notification', DI::l10n()->t('Email administrators on new registration'), DI::config()->get('system', 'register_notification'), DI::l10n()->t('If enabled and the system is set to an open registration, an email for each new registration is sent to the administrators.')],
			'$community_page_style'   => ['community_page_style', DI::l10n()->t('Community pages for visitors'), DI::config()->get('system', 'community_page_style'), DI::l10n()->t('Which community pages should be available for visitors. Local users always see both pages.'), $community_page_style_choices],
			'$max_author_posts_community_page' => ['max_author_posts_community_page', DI::l10n()->t('Posts per user on community page'), DI::config()->get('system', 'max_author_posts_community_page'), DI::l10n()->t('The maximum number of posts per user on the community page. (Not valid for "Global Community")')],
			'$mail_able'              => function_exists('imap_open'),
			'$mail_enabled'           => ['mail_enabled', DI::l10n()->t('Enable Mail support'), !DI::config()->get('system', 'imap_disabled', !function_exists('imap_open')), DI::l10n()->t('Enable built-in mail support to poll IMAP folders and to reply via mail.')],
			'$mail_not_able'          => DI::l10n()->t('Mail support can\'t be enabled because the PHP IMAP module is not installed.'),
			'$ostatus_enabled'        => ['ostatus_enabled', DI::l10n()->t('Enable OStatus support'), !DI::config()->get('system', 'ostatus_disabled'), DI::l10n()->t('Enable built-in OStatus (StatusNet, GNU Social etc.) compatibility. All communications in OStatus are public.')],
			'$diaspora_able'          => $diaspora_able,
			'$diaspora_not_able'      => DI::l10n()->t('Diaspora support can\'t be enabled because Friendica was installed into a sub directory.'),
			'$diaspora_enabled'       => ['diaspora_enabled', DI::l10n()->t('Enable Diaspora support'), DI::config()->get('system', 'diaspora_enabled', $diaspora_able), DI::l10n()->t('Enable built-in Diaspora network compatibility for communicating with diaspora servers.')],
			'$verifyssl'              => ['verifyssl', DI::l10n()->t('Verify SSL'), DI::config()->get('system', 'verifyssl'), DI::l10n()->t('If you wish, you can turn on strict certificate checking. This will mean you cannot connect (at all) to self-signed SSL sites.')],
			'$proxyuser'              => ['proxyuser', DI::l10n()->t('Proxy user'), DI::config()->get('system', 'proxyuser'), DI::l10n()->t('User name for the proxy server.')],
			'$proxy'                  => ['proxy', DI::l10n()->t('Proxy URL'), DI::config()->get('system', 'proxy'), DI::l10n()->t('If you want to use a proxy server that Friendica should use to connect to the network, put the URL of the proxy here.')],
			'$timeout'                => ['timeout', DI::l10n()->t('Network timeout'), DI::config()->get('system', 'curl_timeout'), DI::l10n()->t('Value is in seconds. Set to 0 for unlimited (not recommended).')],
			'$maxloadavg'             => ['maxloadavg', DI::l10n()->t('Maximum Load Average'), DI::config()->get('system', 'maxloadavg'), DI::l10n()->t('Maximum system load before delivery and poll processes are deferred - default %d.', 20)],
			'$min_memory'             => ['min_memory', DI::l10n()->t('Minimal Memory'), DI::config()->get('system', 'min_memory'), DI::l10n()->t('Minimal free memory in MB for the worker. Needs access to /proc/meminfo - default 0 (deactivated).')],
			'$optimize_tables'        => ['optimize_tables', DI::l10n()->t('Periodically optimize tables'), DI::config()->get('system', 'optimize_tables'), DI::l10n()->t('Periodically optimize tables like the cache and the workerqueue')],

			'$contact_discovery'      => ['contact_discovery', DI::l10n()->t('Discover followers/followings from contacts'), DI::config()->get('system', 'contact_discovery'), DI::l10n()->t('If enabled, contacts are checked for their followers and following contacts.') . '<ul>' .
				'<li>' . DI::l10n()->t('None - deactivated') . '</li>' .
				'<li>' . DI::l10n()->t('Local contacts - contacts of our local contacts are discovered for their followers/followings.') . '</li>' .
				'<li>' . DI::l10n()->t('Interactors - contacts of our local contacts and contacts who interacted on locally visible postings are discovered for their followers/followings.') . '</li></ul>',
				$discovery_choices],
			'$synchronize_directory'  => ['synchronize_directory', DI::l10n()->t('Synchronize the contacts with the directory server'), DI::config()->get('system', 'synchronize_directory'), DI::l10n()->t('if enabled, the system will check periodically for new contacts on the defined directory server.')],

			'$poco_requery_days'      => ['poco_requery_days', DI::l10n()->t('Days between requery'), DI::config()->get('system', 'poco_requery_days'), DI::l10n()->t('Number of days after which a server is requeried for his contacts.')],
			'$poco_discovery'         => ['poco_discovery', DI::l10n()->t('Discover contacts from other servers'), DI::config()->get('system', 'poco_discovery'), DI::l10n()->t('Periodically query other servers for contacts. The system queries Friendica, Mastodon and Hubzilla servers.')],
			'$poco_local_search'      => ['poco_local_search', DI::l10n()->t('Search the local directory'), DI::config()->get('system', 'poco_local_search'), DI::l10n()->t('Search the local directory instead of the global directory. When searching locally, every search will be executed on the global directory in the background. This improves the search results when the search is repeated.')],

			'$nodeinfo'               => ['nodeinfo', DI::l10n()->t('Publish server information'), DI::config()->get('system', 'nodeinfo'), DI::l10n()->t('If enabled, general server and usage data will be published. The data contains the name and version of the server, number of users with public profiles, number of posts and the activated protocols and connectors. See <a href="http://the-federation.info/">the-federation.info</a> for details.')],

			'$check_new_version_url'  => ['check_new_version_url', DI::l10n()->t('Check upstream version'), DI::config()->get('system', 'check_new_version_url'), DI::l10n()->t('Enables checking for new Friendica versions at github. If there is a new version, you will be informed in the admin panel overview.'), $check_git_version_choices],
			'$suppress_tags'          => ['suppress_tags', DI::l10n()->t('Suppress Tags'), DI::config()->get('system', 'suppress_tags'), DI::l10n()->t('Suppress showing a list of hashtags at the end of the posting.')],
			'$dbclean'                => ['dbclean', DI::l10n()->t('Clean database'), DI::config()->get('system', 'dbclean'), DI::l10n()->t('Remove old remote items, orphaned database records and old content from some other helper tables.')],
			'$dbclean_expire_days'    => ['dbclean_expire_days', DI::l10n()->t('Lifespan of remote items'), DI::config()->get('system', 'dbclean-expire-days'), DI::l10n()->t('When the database cleanup is enabled, this defines the days after which remote items will be deleted. Own items, and marked or filed items are always kept. 0 disables this behaviour.')],
			'$dbclean_unclaimed'      => ['dbclean_unclaimed', DI::l10n()->t('Lifespan of unclaimed items'), DI::config()->get('system', 'dbclean-expire-unclaimed'), DI::l10n()->t('When the database cleanup is enabled, this defines the days after which unclaimed remote items (mostly content from the relay) will be deleted. Default value is 90 days. Defaults to the general lifespan value of remote items if set to 0.')],
			'$dbclean_expire_conv'    => ['dbclean_expire_conv', DI::l10n()->t('Lifespan of raw conversation data'), DI::config()->get('system', 'dbclean_expire_conversation'), DI::l10n()->t('The conversation data is used for ActivityPub and OStatus, as well as for debug purposes. It should be safe to remove it after 14 days, default is 90 days.')],
			'$max_comments'           => ['max_comments', DI::l10n()->t('Maximum numbers of comments per post'), DI::config()->get('system', 'max_comments'), DI::l10n()->t('How much comments should be shown for each post? Default value is 100.')],
			'$max_display_comments'   => ['max_display_comments', DI::l10n()->t('Maximum numbers of comments per post on the display page'), DI::config()->get('system', 'max_display_comments'), DI::l10n()->t('How many comments should be shown on the single view for each post? Default value is 1000.')],
			'$temppath'               => ['temppath', DI::l10n()->t('Temp path'), DI::config()->get('system', 'temppath'), DI::l10n()->t('If you have a restricted system where the webserver can\'t access the system temp path, enter another path here.')],
			'$only_tag_search'        => ['only_tag_search', DI::l10n()->t('Only search in tags'), DI::config()->get('system', 'only_tag_search'), DI::l10n()->t('On large systems the text search can slow down the system extremely.')],
			'$compute_group_counts'   => ['compute_group_counts', DI::l10n()->t('Generate counts per contact group when calculating network count'), DI::config()->get('system', 'compute_group_counts'), DI::l10n()->t('On systems with users that heavily use contact groups the query can be very expensive.')],

			'$worker_queues'          => ['worker_queues', DI::l10n()->t('Maximum number of parallel workers'), DI::config()->get('system', 'worker_queues'), DI::l10n()->t('On shared hosters set this to %d. On larger systems, values of %d are great. Default value is %d.', 5, 20, 10)],
			'$worker_fastlane'        => ['worker_fastlane', DI::l10n()->t('Enable fastlane'), DI::config()->get('system', 'worker_fastlane'), DI::l10n()->t('When enabed, the fastlane mechanism starts an additional worker if processes with higher priority are blocked by processes of lower priority.')],

			'$relay_directly'         => ['relay_directly', DI::l10n()->t('Direct relay transfer'), DI::config()->get('system', 'relay_directly'), DI::l10n()->t('Enables the direct transfer to other servers without using the relay servers')],
			'$relay_scope'            => ['relay_scope', DI::l10n()->t('Relay scope'), DI::config()->get('system', 'relay_scope'), DI::l10n()->t('Can be "all" or "tags". "all" means that every public post should be received. "tags" means that only posts with selected tags should be received.'), [Relay::SCOPE_NONE => DI::l10n()->t('Disabled'), Relay::SCOPE_ALL => DI::l10n()->t('all'), Relay::SCOPE_TAGS => DI::l10n()->t('tags')]],
			'$relay_server_tags'      => ['relay_server_tags', DI::l10n()->t('Server tags'), DI::config()->get('system', 'relay_server_tags'), DI::l10n()->t('Comma separated list of tags for the "tags" subscription.')],
			'$relay_deny_tags'        => ['relay_deny_tags', DI::l10n()->t('Deny Server tags'), DI::config()->get('system', 'relay_deny_tags'), DI::l10n()->t('Comma separated list of tags that are rejected.')],
			'$relay_user_tags'        => ['relay_user_tags', DI::l10n()->t('Allow user tags'), DI::config()->get('system', 'relay_user_tags'), DI::l10n()->t('If enabled, the tags from the saved searches will used for the "tags" subscription in addition to the "relay_server_tags".')],

			'$form_security_token'    => self::getFormSecurityToken('admin_site'),
			'$relocate_button'        => DI::l10n()->t('Start Relocation'),
		]);
	}
}
