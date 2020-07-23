<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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
use Friendica\Core\Theme;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\GContact;
use Friendica\Module\BaseAdmin;
use Friendica\Module\Register;
use Friendica\Protocol\PortableContact;
use Friendica\Util\BasePath;
use Friendica\Util\EMailer\MailBuilder;
use Friendica\Util\Strings;
use Friendica\Worker\Delivery;

require_once __DIR__ . '/../../../boot.php';

class Site extends BaseAdmin
{
	public static function post(array $parameters = [])
	{
		parent::post($parameters);

		self::checkFormSecurityTokenRedirectOnError('/admin/site', 'admin_site');

		$a = DI::app();

		if (!empty($_POST['republish_directory'])) {
			Worker::add(PRIORITY_LOW, 'Directory');
			return;
		}

		if (empty($_POST['page_site'])) {
			return;
		}

		// relocate
		// @TODO This file could benefit from moving this feature away in a Module\Admin\Relocate class for example
		if (!empty($_POST['relocate']) && !empty($_POST['relocate_url']) && $_POST['relocate_url'] != "") {
			$new_url = $_POST['relocate_url'];
			$new_url = rtrim($new_url, "/");

			$parsed = @parse_url($new_url);
			if (!is_array($parsed) || empty($parsed['host']) || empty($parsed['scheme'])) {
				notice(DI::l10n()->t("Can not parse base url. Must have at least <scheme>://<domain>"));
				DI::baseUrl()->redirect('admin/site');
			}

			/* steps:
			 * replace all "baseurl" to "new_url" in config, profile, term, items and contacts
			 * send relocate for every local user
			 * */

			$old_url = DI::baseUrl()->get(true);

			// Generate host names for relocation the addresses in the format user@address.tld
			$new_host = str_replace("http://", "@", Strings::normaliseLink($new_url));
			$old_host = str_replace("http://", "@", Strings::normaliseLink($old_url));

			function update_table(App $a, $table_name, $fields, $old_url, $new_url)
			{
				$dbold = DBA::escape($old_url);
				$dbnew = DBA::escape($new_url);

				$upd = [];
				foreach ($fields as $f) {
					$upd[] = "`$f` = REPLACE(`$f`, '$dbold', '$dbnew')";
				}

				$upds = implode(", ", $upd);

				$r = DBA::e(sprintf("UPDATE %s SET %s;", $table_name, $upds));
				if (!DBA::isResult($r)) {
					notice("Failed updating '$table_name': " . DBA::errorMessage());
					DI::baseUrl()->redirect('admin/site');
				}
			}

			// update tables
			// update profile links in the format "http://server.tld"
			update_table($a, "profile", ['photo', 'thumb'], $old_url, $new_url);
			update_table($a, "contact", ['photo', 'thumb', 'micro', 'url', 'nurl', 'alias', 'request', 'notify', 'poll', 'confirm', 'poco', 'avatar'], $old_url, $new_url);
			update_table($a, "gcontact", ['url', 'nurl', 'photo', 'server_url', 'notify', 'alias'], $old_url, $new_url);
			update_table($a, "item", ['owner-link', 'author-link', 'body', 'plink', 'tag'], $old_url, $new_url);

			// update profile addresses in the format "user@server.tld"
			update_table($a, "contact", ['addr'], $old_host, $new_host);
			update_table($a, "gcontact", ['connect', 'addr'], $old_host, $new_host);

			// update config
			DI::config()->set('system', 'url', $new_url);
			DI::baseUrl()->saveByURL($new_url);

			// send relocate
			$usersStmt = DBA::select('user', ['uid'], ['account_removed' => false, 'account_expired' => false]);
			while ($user = DBA::fetch($usersStmt)) {
				Worker::add(PRIORITY_HIGH, 'Notifier', Delivery::RELOCATION, $user['uid']);
			}
			DBA::close($usersStmt);

			info("Relocation started. Could take a while to complete.");

			DI::baseUrl()->redirect('admin/site');
		}
		// end relocate

		$sitename         = (!empty($_POST['sitename'])         ? Strings::escapeTags(trim($_POST['sitename']))      : '');
		$sender_email     = (!empty($_POST['sender_email'])     ? Strings::escapeTags(trim($_POST['sender_email']))  : '');
		$banner           = (!empty($_POST['banner'])           ? trim($_POST['banner'])                             : false);
		$email_banner     = (!empty($_POST['email_banner'])     ? trim($_POST['email_banner'])                       : false);
		$shortcut_icon    = (!empty($_POST['shortcut_icon'])    ? Strings::escapeTags(trim($_POST['shortcut_icon'])) : '');
		$touch_icon       = (!empty($_POST['touch_icon'])       ? Strings::escapeTags(trim($_POST['touch_icon']))    : '');
		$additional_info  = (!empty($_POST['additional_info'])  ? trim($_POST['additional_info'])                    : '');
		$language         = (!empty($_POST['language'])         ? Strings::escapeTags(trim($_POST['language']))      : '');
		$theme            = (!empty($_POST['theme'])            ? Strings::escapeTags(trim($_POST['theme']))         : '');
		$theme_mobile     = (!empty($_POST['theme_mobile'])     ? Strings::escapeTags(trim($_POST['theme_mobile']))  : '');
		$maximagesize     = (!empty($_POST['maximagesize'])     ? intval(trim($_POST['maximagesize']))               : 0);
		$maximagelength   = (!empty($_POST['maximagelength'])   ? intval(trim($_POST['maximagelength']))             : MAX_IMAGE_LENGTH);
		$jpegimagequality = (!empty($_POST['jpegimagequality']) ? intval(trim($_POST['jpegimagequality']))           : JPEG_QUALITY);

		$register_policy        = (!empty($_POST['register_policy'])         ? intval(trim($_POST['register_policy']))             : 0);
		$daily_registrations    = (!empty($_POST['max_daily_registrations']) ? intval(trim($_POST['max_daily_registrations']))     : 0);
		$abandon_days           = (!empty($_POST['abandon_days'])            ? intval(trim($_POST['abandon_days']))                : 0);

		$register_text          = (!empty($_POST['register_text'])           ? strip_tags(trim($_POST['register_text']))           : '');

		$allowed_sites          = (!empty($_POST['allowed_sites'])           ? Strings::escapeTags(trim($_POST['allowed_sites']))  : '');
		$allowed_email          = (!empty($_POST['allowed_email'])           ? Strings::escapeTags(trim($_POST['allowed_email']))  : '');
		$forbidden_nicknames    = (!empty($_POST['forbidden_nicknames'])     ? strtolower(Strings::escapeTags(trim($_POST['forbidden_nicknames']))) : '');
		$no_oembed_rich_content = !empty($_POST['no_oembed_rich_content']);
		$allowed_oembed         = (!empty($_POST['allowed_oembed'])          ? Strings::escapeTags(trim($_POST['allowed_oembed'])) : '');
		$block_public           = !empty($_POST['block_public']);
		$force_publish          = !empty($_POST['publish_all']);
		$global_directory       = (!empty($_POST['directory'])               ? Strings::escapeTags(trim($_POST['directory']))      : '');
		$newuser_private        = !empty($_POST['newuser_private']);
		$enotify_no_content     = !empty($_POST['enotify_no_content']);
		$private_addons         = !empty($_POST['private_addons']);
		$disable_embedded       = !empty($_POST['disable_embedded']);
		$allow_users_remote_self = !empty($_POST['allow_users_remote_self']);
		$explicit_content       = !empty($_POST['explicit_content']);

		$no_multi_reg           = !empty($_POST['no_multi_reg']);
		$no_openid              = !empty($_POST['no_openid']);
		$no_regfullname         = !empty($_POST['no_regfullname']);
		$community_page_style   = (!empty($_POST['community_page_style']) ? intval(trim($_POST['community_page_style'])) : 0);
		$max_author_posts_community_page = (!empty($_POST['max_author_posts_community_page']) ? intval(trim($_POST['max_author_posts_community_page'])) : 0);

		$verifyssl              = !empty($_POST['verifyssl']);
		$proxyuser              = (!empty($_POST['proxyuser'])              ? Strings::escapeTags(trim($_POST['proxyuser'])) : '');
		$proxy                  = (!empty($_POST['proxy'])                  ? Strings::escapeTags(trim($_POST['proxy']))     : '');
		$timeout                = (!empty($_POST['timeout'])                ? intval(trim($_POST['timeout']))                : 60);
		$maxloadavg             = (!empty($_POST['maxloadavg'])             ? intval(trim($_POST['maxloadavg']))             : 20);
		$maxloadavg_frontend    = (!empty($_POST['maxloadavg_frontend'])    ? intval(trim($_POST['maxloadavg_frontend']))    : 50);
		$min_memory             = (!empty($_POST['min_memory'])             ? intval(trim($_POST['min_memory']))             : 0);
		$optimize_max_tablesize = (!empty($_POST['optimize_max_tablesize']) ? intval(trim($_POST['optimize_max_tablesize'])) : 100);
		$optimize_fragmentation = (!empty($_POST['optimize_fragmentation']) ? intval(trim($_POST['optimize_fragmentation'])) : 30);
		$poco_completion        = (!empty($_POST['poco_completion'])        ? intval(trim($_POST['poco_completion']))        : false);
		$gcontact_discovery     = (!empty($_POST['gcontact_discovery'])     ? intval(trim($_POST['gcontact_discovery']))     : GContact::DISCOVERY_NONE);
		$poco_requery_days      = (!empty($_POST['poco_requery_days'])      ? intval(trim($_POST['poco_requery_days']))      : 7);
		$poco_discovery         = (!empty($_POST['poco_discovery'])         ? intval(trim($_POST['poco_discovery']))         : PortableContact::DISABLED);
		$poco_discovery_since   = (!empty($_POST['poco_discovery_since'])   ? intval(trim($_POST['poco_discovery_since']))   : 30);
		$poco_local_search      = !empty($_POST['poco_local_search']);
		$nodeinfo               = !empty($_POST['nodeinfo']);
		$dfrn_only              = !empty($_POST['dfrn_only']);
		$ostatus_disabled       = !empty($_POST['ostatus_disabled']);
		$diaspora_enabled       = !empty($_POST['diaspora_enabled']);
		$ssl_policy             = (!empty($_POST['ssl_policy'])             ? intval($_POST['ssl_policy'])                    : 0);
		$force_ssl              = !empty($_POST['force_ssl']);
		$hide_help              = !empty($_POST['hide_help']);
		$dbclean                = !empty($_POST['dbclean']);
		$dbclean_expire_days    = (!empty($_POST['dbclean_expire_days'])    ? intval($_POST['dbclean_expire_days'])           : 0);
		$dbclean_unclaimed      = (!empty($_POST['dbclean_unclaimed'])      ? intval($_POST['dbclean_unclaimed'])             : 0);
		$dbclean_expire_conv    = (!empty($_POST['dbclean_expire_conv'])    ? intval($_POST['dbclean_expire_conv'])           : 0);
		$suppress_tags          = !empty($_POST['suppress_tags']);
		$itemcache              = (!empty($_POST['itemcache'])              ? Strings::escapeTags(trim($_POST['itemcache']))  : '');
		$itemcache_duration     = (!empty($_POST['itemcache_duration'])     ? intval($_POST['itemcache_duration'])            : 0);
		$max_comments           = (!empty($_POST['max_comments'])           ? intval($_POST['max_comments'])                  : 0);
		$max_display_comments   = (!empty($_POST['max_display_comments'])   ? intval($_POST['max_display_comments'])          : 0);
		$temppath               = (!empty($_POST['temppath'])               ? Strings::escapeTags(trim($_POST['temppath']))   : '');
		$singleuser             = (!empty($_POST['singleuser'])             ? Strings::escapeTags(trim($_POST['singleuser'])) : '');
		$proxy_disabled         = !empty($_POST['proxy_disabled']);
		$only_tag_search        = !empty($_POST['only_tag_search']);
		$rino                   = (!empty($_POST['rino'])                   ? intval($_POST['rino'])                          : 0);
		$check_new_version_url  = (!empty($_POST['check_new_version_url'])  ? Strings::escapeTags(trim($_POST['check_new_version_url'])) : 'none');

		$worker_queues    = (!empty($_POST['worker_queues'])                ? intval($_POST['worker_queues'])                 : 10);
		$worker_dont_fork = !empty($_POST['worker_dont_fork']);
		$worker_fastlane  = !empty($_POST['worker_fastlane']);
		$worker_frontend  = !empty($_POST['worker_frontend']);

		$relay_directly    = !empty($_POST['relay_directly']);
		$relay_server      = (!empty($_POST['relay_server'])      ? Strings::escapeTags(trim($_POST['relay_server']))       : '');
		$relay_subscribe   = !empty($_POST['relay_subscribe']);
		$relay_scope       = (!empty($_POST['relay_scope'])       ? Strings::escapeTags(trim($_POST['relay_scope']))        : '');
		$relay_server_tags = (!empty($_POST['relay_server_tags']) ? Strings::escapeTags(trim($_POST['relay_server_tags']))  : '');
		$relay_user_tags   = !empty($_POST['relay_user_tags']);
		$active_panel      = (!empty($_POST['active_panel'])      ? "#" . Strings::escapeTags(trim($_POST['active_panel'])) : '');

		$storagebackend    = Strings::escapeTags(trim($_POST['storagebackend'] ?? ''));

		// save storage backend form
		if (DI::storageManager()->setBackend($storagebackend)) {
			$storage_opts     = DI::storage()->getOptions();
			$storage_form_prefix = preg_replace('|[^a-zA-Z0-9]|', '', $storagebackend);
			$storage_opts_data   = [];
			foreach ($storage_opts as $name => $info) {
				$fieldname = $storage_form_prefix . '_' . $name;
				switch ($info[0]) { // type
					case 'checkbox':
					case 'yesno':
						$value = !empty($_POST[$fieldname]);
						break;
					default:
						$value = $_POST[$fieldname] ?? '';
				}
				$storage_opts_data[$name] = $value;
			}
			unset($name);
			unset($info);

			$storage_form_errors = DI::storage()->saveOptions($storage_opts_data);
			if (count($storage_form_errors)) {
				foreach ($storage_form_errors as $name => $err) {
					notice('Storage backend, ' . $storage_opts[$name][1] . ': ' . $err);
				}
				DI::baseUrl()->redirect('admin/site' . $active_panel);
			}
		} else {
			info(DI::l10n()->t('Invalid storage backend setting value.'));
		}

		// Has the directory url changed? If yes, then resubmit the existing profiles there
		if ($global_directory != DI::config()->get('system', 'directory') && ($global_directory != '')) {
			DI::config()->set('system', 'directory', $global_directory);
			Worker::add(PRIORITY_LOW, 'Directory');
		}

		if (DI::baseUrl()->getUrlPath() != "") {
			$diaspora_enabled = false;
		}
		if ($ssl_policy != intval(DI::config()->get('system', 'ssl_policy'))) {
			if ($ssl_policy == App\BaseURL::SSL_POLICY_FULL) {
				DBA::e("UPDATE `contact` SET
				`url`     = REPLACE(`url`    , 'http:' , 'https:'),
				`photo`   = REPLACE(`photo`  , 'http:' , 'https:'),
				`thumb`   = REPLACE(`thumb`  , 'http:' , 'https:'),
				`micro`   = REPLACE(`micro`  , 'http:' , 'https:'),
				`request` = REPLACE(`request`, 'http:' , 'https:'),
				`notify`  = REPLACE(`notify` , 'http:' , 'https:'),
				`poll`    = REPLACE(`poll`   , 'http:' , 'https:'),
				`confirm` = REPLACE(`confirm`, 'http:' , 'https:'),
				`poco`    = REPLACE(`poco`   , 'http:' , 'https:')
				WHERE `self` = 1"
				);
				DBA::e("UPDATE `profile` SET
				`photo`   = REPLACE(`photo`  , 'http:' , 'https:'),
				`thumb`   = REPLACE(`thumb`  , 'http:' , 'https:')
				WHERE 1 "
				);
			} elseif ($ssl_policy == App\BaseURL::SSL_POLICY_SELFSIGN) {
				DBA::e("UPDATE `contact` SET
				`url`     = REPLACE(`url`    , 'https:' , 'http:'),
				`photo`   = REPLACE(`photo`  , 'https:' , 'http:'),
				`thumb`   = REPLACE(`thumb`  , 'https:' , 'http:'),
				`micro`   = REPLACE(`micro`  , 'https:' , 'http:'),
				`request` = REPLACE(`request`, 'https:' , 'http:'),
				`notify`  = REPLACE(`notify` , 'https:' , 'http:'),
				`poll`    = REPLACE(`poll`   , 'https:' , 'http:'),
				`confirm` = REPLACE(`confirm`, 'https:' , 'http:'),
				`poco`    = REPLACE(`poco`   , 'https:' , 'http:')
				WHERE `self` = 1"
				);
				DBA::e("UPDATE `profile` SET
				`photo`   = REPLACE(`photo`  , 'https:' , 'http:'),
				`thumb`   = REPLACE(`thumb`  , 'https:' , 'http:')
				WHERE 1 "
				);
			}
		}
		DI::config()->set('system', 'ssl_policy'            , $ssl_policy);
		DI::config()->set('system', 'maxloadavg'            , $maxloadavg);
		DI::config()->set('system', 'maxloadavg_frontend'   , $maxloadavg_frontend);
		DI::config()->set('system', 'min_memory'            , $min_memory);
		DI::config()->set('system', 'optimize_max_tablesize', $optimize_max_tablesize);
		DI::config()->set('system', 'optimize_fragmentation', $optimize_fragmentation);
		DI::config()->set('system', 'poco_completion'       , $poco_completion);
		DI::config()->set('system', 'gcontact_discovery'    , $gcontact_discovery);
		DI::config()->set('system', 'poco_requery_days'     , $poco_requery_days);
		DI::config()->set('system', 'poco_discovery'        , $poco_discovery);
		DI::config()->set('system', 'poco_discovery_since'  , $poco_discovery_since);
		DI::config()->set('system', 'poco_local_search'     , $poco_local_search);
		DI::config()->set('system', 'nodeinfo'              , $nodeinfo);
		DI::config()->set('config', 'sitename'              , $sitename);
		DI::config()->set('config', 'sender_email'          , $sender_email);
		DI::config()->set('system', 'suppress_tags'         , $suppress_tags);
		DI::config()->set('system', 'shortcut_icon'         , $shortcut_icon);
		DI::config()->set('system', 'touch_icon'            , $touch_icon);

		if ($banner == "") {
			DI::config()->delete('system', 'banner');
		} else {
			DI::config()->set('system', 'banner', $banner);
		}

		if (empty($email_banner)) {
			DI::config()->delete('system', 'email_banner');
		} else {
			DI::config()->set('system', 'email_banner', $email_banner);
		}

		if (empty($additional_info)) {
			DI::config()->delete('config', 'info');
		} else {
			DI::config()->set('config', 'info', $additional_info);
		}
		DI::config()->set('system', 'language', $language);
		DI::config()->set('system', 'theme', $theme);
		Theme::install($theme);

		if ($theme_mobile == '---') {
			DI::config()->delete('system', 'mobile-theme');
		} else {
			DI::config()->set('system', 'mobile-theme', $theme_mobile);
		}
		if ($singleuser == '---') {
			DI::config()->delete('system', 'singleuser');
		} else {
			DI::config()->set('system', 'singleuser', $singleuser);
		}
		DI::config()->set('system', 'maximagesize'           , $maximagesize);
		DI::config()->set('system', 'max_image_length'       , $maximagelength);
		DI::config()->set('system', 'jpeg_quality'           , $jpegimagequality);

		DI::config()->set('config', 'register_policy'        , $register_policy);
		DI::config()->set('system', 'max_daily_registrations', $daily_registrations);
		DI::config()->set('system', 'account_abandon_days'   , $abandon_days);
		DI::config()->set('config', 'register_text'          , $register_text);
		DI::config()->set('system', 'allowed_sites'          , $allowed_sites);
		DI::config()->set('system', 'allowed_email'          , $allowed_email);
		DI::config()->set('system', 'forbidden_nicknames'    , $forbidden_nicknames);
		DI::config()->set('system', 'no_oembed_rich_content' , $no_oembed_rich_content);
		DI::config()->set('system', 'allowed_oembed'         , $allowed_oembed);
		DI::config()->set('system', 'block_public'           , $block_public);
		DI::config()->set('system', 'publish_all'            , $force_publish);
		DI::config()->set('system', 'newuser_private'        , $newuser_private);
		DI::config()->set('system', 'enotify_no_content'     , $enotify_no_content);
		DI::config()->set('system', 'disable_embedded'       , $disable_embedded);
		DI::config()->set('system', 'allow_users_remote_self', $allow_users_remote_self);
		DI::config()->set('system', 'explicit_content'       , $explicit_content);
		DI::config()->set('system', 'check_new_version_url'  , $check_new_version_url);

		DI::config()->set('system', 'block_extended_register', $no_multi_reg);
		DI::config()->set('system', 'no_openid'              , $no_openid);
		DI::config()->set('system', 'no_regfullname'         , $no_regfullname);
		DI::config()->set('system', 'community_page_style'   , $community_page_style);
		DI::config()->set('system', 'max_author_posts_community_page', $max_author_posts_community_page);
		DI::config()->set('system', 'verifyssl'              , $verifyssl);
		DI::config()->set('system', 'proxyuser'              , $proxyuser);
		DI::config()->set('system', 'proxy'                  , $proxy);
		DI::config()->set('system', 'curl_timeout'           , $timeout);
		DI::config()->set('system', 'dfrn_only'              , $dfrn_only);
		DI::config()->set('system', 'ostatus_disabled'       , $ostatus_disabled);
		DI::config()->set('system', 'diaspora_enabled'       , $diaspora_enabled);

		DI::config()->set('config', 'private_addons'         , $private_addons);

		DI::config()->set('system', 'force_ssl'              , $force_ssl);
		DI::config()->set('system', 'hide_help'              , $hide_help);

		DI::config()->set('system', 'dbclean'                , $dbclean);
		DI::config()->set('system', 'dbclean-expire-days'    , $dbclean_expire_days);
		DI::config()->set('system', 'dbclean_expire_conversation', $dbclean_expire_conv);

		if ($dbclean_unclaimed == 0) {
			$dbclean_unclaimed = $dbclean_expire_days;
		}

		DI::config()->set('system', 'dbclean-expire-unclaimed', $dbclean_unclaimed);

		if ($itemcache != '') {
			$itemcache = BasePath::getRealPath($itemcache);
		}

		DI::config()->set('system', 'itemcache', $itemcache);
		DI::config()->set('system', 'itemcache_duration', $itemcache_duration);
		DI::config()->set('system', 'max_comments', $max_comments);
		DI::config()->set('system', 'max_display_comments', $max_display_comments);

		if ($temppath != '') {
			$temppath = BasePath::getRealPath($temppath);
		}

		DI::config()->set('system', 'temppath', $temppath);

		DI::config()->set('system', 'proxy_disabled'   , $proxy_disabled);
		DI::config()->set('system', 'only_tag_search'  , $only_tag_search);

		DI::config()->set('system', 'worker_queues'    , $worker_queues);
		DI::config()->set('system', 'worker_dont_fork' , $worker_dont_fork);
		DI::config()->set('system', 'worker_fastlane'  , $worker_fastlane);
		DI::config()->set('system', 'frontend_worker'  , $worker_frontend);

		DI::config()->set('system', 'relay_directly'   , $relay_directly);
		DI::config()->set('system', 'relay_server'     , $relay_server);
		DI::config()->set('system', 'relay_subscribe'  , $relay_subscribe);
		DI::config()->set('system', 'relay_scope'      , $relay_scope);
		DI::config()->set('system', 'relay_server_tags', $relay_server_tags);
		DI::config()->set('system', 'relay_user_tags'  , $relay_user_tags);

		DI::config()->set('system', 'rino_encrypt'     , $rino);

		info(DI::l10n()->t('Site settings updated.') . EOL);

		DI::baseUrl()->redirect('admin/site' . $active_panel);
	}

	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		/* Installed langs */
		$lang_choices = DI::l10n()->getAvailableLanguages();

		if (strlen(DI::config()->get('system', 'directory_submit_url')) &&
			!strlen(DI::config()->get('system', 'directory'))) {
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
			CP_NO_INTERNAL_COMMUNITY => DI::l10n()->t('No community page for local users'),
			CP_NO_COMMUNITY_PAGE => DI::l10n()->t('No community page'),
			CP_USERS_ON_SERVER => DI::l10n()->t('Public postings from users of this site'),
			CP_GLOBAL_COMMUNITY => DI::l10n()->t('Public postings from the federated network'),
			CP_USERS_AND_GLOBAL => DI::l10n()->t('Public postings from local users and the federated network')
		];

		$poco_discovery_choices = [
			PortableContact::DISABLED => DI::l10n()->t('Disabled'),
			PortableContact::USERS => DI::l10n()->t('Users'),
			PortableContact::USERS_GCONTACTS => DI::l10n()->t('Users, Global Contacts'),
			PortableContact::USERS_GCONTACTS_FALLBACK => DI::l10n()->t('Users, Global Contacts/fallback'),
		];

		$poco_discovery_since_choices = [
			'30' => DI::l10n()->t('One month'),
			'91' => DI::l10n()->t('Three months'),
			'182' => DI::l10n()->t('Half a year'),
			'365' => DI::l10n()->t('One year'),
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

		if ($banner == false) {
			$banner = '<a href="https://friendi.ca"><img id="logo-img" src="images/friendica-32.png" alt="logo" /></a><span id="logo-text"><a href="https://friendi.ca">Friendica</a></span>';
		}

		$email_banner = DI::config()->get('system', 'email_banner');

		if ($email_banner == false) {
			$email_banner = MailBuilder::DEFAULT_EMAIL_BANNER;
		}

		$additional_info = DI::config()->get('config', 'info');

		// Automatically create temporary paths
		get_temppath();
		get_itemcachepath();

		/* Register policy */
		$register_choices = [
			Register::CLOSED => DI::l10n()->t('Closed'),
			Register::APPROVE => DI::l10n()->t('Requires approval'),
			Register::OPEN => DI::l10n()->t('Open')
		];

		$ssl_choices = [
			App\BaseURL::SSL_POLICY_NONE => DI::l10n()->t('No SSL policy, links will track page SSL state'),
			App\BaseURL::SSL_POLICY_FULL => DI::l10n()->t('Force all links to use SSL'),
			App\BaseURL::SSL_POLICY_SELFSIGN => DI::l10n()->t('Self-signed certificate, use SSL for local links only (discouraged)')
		];

		$check_git_version_choices = [
			'none' => DI::l10n()->t('Don\'t check'),
			'stable' => DI::l10n()->t('check the stable version'),
			'develop' => DI::l10n()->t('check the development version')
		];

		$discovery_choices = [
			GContact::DISCOVERY_NONE => DI::l10n()->t('none'),
			GContact::DISCOVERY_DIRECT => DI::l10n()->t('Direct contacts'),
			GContact::DISCOVERY_RECURSIVE => DI::l10n()->t('Contacts of contacts')
		];

		$diaspora_able = (DI::baseUrl()->getUrlPath() == '');

		$optimize_max_tablesize = DI::config()->get('system', 'optimize_max_tablesize', -1);

		if ($optimize_max_tablesize <= 0) {
			$optimize_max_tablesize = -1;
		}

		$current_storage_backend = DI::storage();
		$available_storage_backends = [];

		// show legacy option only if it is the current backend:
		// once changed can't be selected anymore
		if ($current_storage_backend == null) {
			$available_storage_backends[''] = DI::l10n()->t('Database (legacy)');
		}

		foreach (DI::storageManager()->listBackends() as $name => $class) {
			$available_storage_backends[$name] = $name;
		}

		// build storage config form,
		$storage_form_prefix = preg_replace('|[^a-zA-Z0-9]|' ,'', $current_storage_backend);

		$storage_form = [];
		if (!is_null($current_storage_backend) && $current_storage_backend != '') {
			foreach ($current_storage_backend->getOptions() as $name => $info) {
				$type = $info[0];
				// Backward compatibilty with yesno field description
				if ($type == 'yesno') {
					$type = 'checkbox';
					// Remove translated labels Yes No from field info
					unset($info[4]);
				}

				$info[0] = $storage_form_prefix . '_' . $name;
				$info['type'] = $type;
				$info['field'] = 'field_' . $type . '.tpl';
				$storage_form[$name] = $info;
			}
		}

		$t = Renderer::getMarkupTemplate('admin/site.tpl');
		return Renderer::replaceMacros($t, [
			'$title'             => DI::l10n()->t('Administration'),
			'$page'              => DI::l10n()->t('Site'),
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
			'$relocate'          => DI::l10n()->t('Relocate Instance'),
			'$relocate_warning'  => DI::l10n()->t('<strong>Warning!</strong> Advanced function. Could make this server unreachable.'),
			'$baseurl'           => DI::baseUrl()->get(true),

			// name, label, value, help string, extra data...
			'$sitename'         => ['sitename', DI::l10n()->t('Site name'), DI::config()->get('config', 'sitename'), ''],
			'$sender_email'     => ['sender_email', DI::l10n()->t('Sender Email'), DI::config()->get('config', 'sender_email'), DI::l10n()->t('The email address your server shall use to send notification emails from.'), '', '', 'email'],
			'$banner'           => ['banner', DI::l10n()->t('Banner/Logo'), $banner, ''],
			'$email_banner'     => ['email_banner', DI::l10n()->t('Email Banner/Logo'), $email_banner, ''],
			'$shortcut_icon'    => ['shortcut_icon', DI::l10n()->t('Shortcut icon'), DI::config()->get('system', 'shortcut_icon'), DI::l10n()->t('Link to an icon that will be used for browsers.')],
			'$touch_icon'       => ['touch_icon', DI::l10n()->t('Touch icon'), DI::config()->get('system', 'touch_icon'), DI::l10n()->t('Link to an icon that will be used for tablets and mobiles.')],
			'$additional_info'  => ['additional_info', DI::l10n()->t('Additional Info'), $additional_info, DI::l10n()->t('For public servers: you can add additional information here that will be listed at %s/servers.', Search::getGlobalDirectory())],
			'$language'         => ['language', DI::l10n()->t('System language'), DI::config()->get('system', 'language'), '', $lang_choices],
			'$theme'            => ['theme', DI::l10n()->t('System theme'), DI::config()->get('system', 'theme'), DI::l10n()->t('Default system theme - may be over-ridden by user profiles - <a href="/admin/themes" id="cnftheme">Change default theme settings</a>'), $theme_choices],
			'$theme_mobile'     => ['theme_mobile', DI::l10n()->t('Mobile system theme'), DI::config()->get('system', 'mobile-theme', '---'), DI::l10n()->t('Theme for mobile devices'), $theme_choices_mobile],
			'$ssl_policy'       => ['ssl_policy', DI::l10n()->t('SSL link policy'), DI::config()->get('system', 'ssl_policy'), DI::l10n()->t('Determines whether generated links should be forced to use SSL'), $ssl_choices],
			'$force_ssl'        => ['force_ssl', DI::l10n()->t('Force SSL'), DI::config()->get('system', 'force_ssl'), DI::l10n()->t('Force all Non-SSL requests to SSL - Attention: on some systems it could lead to endless loops.')],
			'$hide_help'        => ['hide_help', DI::l10n()->t('Hide help entry from navigation menu'), DI::config()->get('system', 'hide_help'), DI::l10n()->t('Hides the menu entry for the Help pages from the navigation menu. You can still access it calling /help directly.')],
			'$singleuser'       => ['singleuser', DI::l10n()->t('Single user instance'), DI::config()->get('system', 'singleuser', '---'), DI::l10n()->t('Make this instance multi-user or single-user for the named user'), $user_names],

			'$storagebackend'   => ['storagebackend', DI::l10n()->t('File storage backend'), $current_storage_backend, DI::l10n()->t('The backend used to store uploaded data. If you change the storage backend, you can manually move the existing files. If you do not do so, the files uploaded before the change will still be available at the old backend. Please see <a href="/help/Settings#1_2_3_1">the settings documentation</a> for more information about the choices and the moving procedure.'), $available_storage_backends],
			'$storageform'      => $storage_form,
			'$maximagesize'     => ['maximagesize', DI::l10n()->t('Maximum image size'), DI::config()->get('system', 'maximagesize'), DI::l10n()->t('Maximum size in bytes of uploaded images. Default is 0, which means no limits.')],
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
			'$allowed_oembed'         => ['allowed_oembed', DI::l10n()->t('Allowed OEmbed domains'), DI::config()->get('system', 'allowed_oembed'), DI::l10n()->t('Comma separated list of domains which oembed content is allowed to be displayed. Wildcards are accepted.')],
			'$block_public'           => ['block_public', DI::l10n()->t('Block public'), DI::config()->get('system', 'block_public'), DI::l10n()->t('Check to block public access to all otherwise public personal pages on this site unless you are currently logged in.')],
			'$force_publish'          => ['publish_all', DI::l10n()->t('Force publish'), DI::config()->get('system', 'publish_all'), DI::l10n()->t('Check to force all profiles on this site to be listed in the site directory.') . '<strong>' . DI::l10n()->t('Enabling this may violate privacy laws like the GDPR') . '</strong>'],
			'$global_directory'       => ['directory', DI::l10n()->t('Global directory URL'), DI::config()->get('system', 'directory', 'https://dir.friendica.social'), DI::l10n()->t('URL to the global directory. If this is not set, the global directory is completely unavailable to the application.')],
			'$newuser_private'        => ['newuser_private', DI::l10n()->t('Private posts by default for new users'), DI::config()->get('system', 'newuser_private'), DI::l10n()->t('Set default post permissions for all new members to the default privacy group rather than public.')],
			'$enotify_no_content'     => ['enotify_no_content', DI::l10n()->t('Don\'t include post content in email notifications'), DI::config()->get('system', 'enotify_no_content'), DI::l10n()->t('Don\'t include the content of a post/comment/private message/etc. in the email notifications that are sent out from this site, as a privacy measure.')],
			'$private_addons'         => ['private_addons', DI::l10n()->t('Disallow public access to addons listed in the apps menu.'), DI::config()->get('config', 'private_addons'), DI::l10n()->t('Checking this box will restrict addons listed in the apps menu to members only.')],
			'$disable_embedded'       => ['disable_embedded', DI::l10n()->t('Don\'t embed private images in posts'), DI::config()->get('system', 'disable_embedded'), DI::l10n()->t('Don\'t replace locally-hosted private photos in posts with an embedded copy of the image. This means that contacts who receive posts containing private photos will have to authenticate and load each image, which may take a while.')],
			'$explicit_content'       => ['explicit_content', DI::l10n()->t('Explicit Content'), DI::config()->get('system', 'explicit_content', false), DI::l10n()->t('Set this to announce that your node is used mostly for explicit content that might not be suited for minors. This information will be published in the node information and might be used, e.g. by the global directory, to filter your node from listings of nodes to join. Additionally a note about this will be shown at the user registration page.')],
			'$allow_users_remote_self'=> ['allow_users_remote_self', DI::l10n()->t('Allow Users to set remote_self'), DI::config()->get('system', 'allow_users_remote_self'), DI::l10n()->t('With checking this, every user is allowed to mark every contact as a remote_self in the repair contact dialog. Setting this flag on a contact causes mirroring every posting of that contact in the users stream.')],
			'$no_multi_reg'           => ['no_multi_reg', DI::l10n()->t('Block multiple registrations'), DI::config()->get('system', 'block_extended_register'), DI::l10n()->t('Disallow users to register additional accounts for use as pages.')],
			'$no_openid'              => ['no_openid', DI::l10n()->t('Disable OpenID'), DI::config()->get('system', 'no_openid'), DI::l10n()->t('Disable OpenID support for registration and logins.')],
			'$no_regfullname'         => ['no_regfullname', DI::l10n()->t('No Fullname check'), DI::config()->get('system', 'no_regfullname'), DI::l10n()->t('Allow users to register without a space between the first name and the last name in their full name.')],
			'$community_page_style'   => ['community_page_style', DI::l10n()->t('Community pages for visitors'), DI::config()->get('system', 'community_page_style'), DI::l10n()->t('Which community pages should be available for visitors. Local users always see both pages.'), $community_page_style_choices],
			'$max_author_posts_community_page' => ['max_author_posts_community_page', DI::l10n()->t('Posts per user on community page'), DI::config()->get('system', 'max_author_posts_community_page'), DI::l10n()->t('The maximum number of posts per user on the community page. (Not valid for "Global Community")')],
			'$ostatus_disabled'       => ['ostatus_disabled', DI::l10n()->t('Disable OStatus support'), DI::config()->get('system', 'ostatus_disabled'), DI::l10n()->t('Disable built-in OStatus (StatusNet, GNU Social etc.) compatibility. All communications in OStatus are public, so privacy warnings will be occasionally displayed.')],
			'$ostatus_not_able'       => DI::l10n()->t('OStatus support can only be enabled if threading is enabled.'),
			'$diaspora_able'          => $diaspora_able,
			'$diaspora_not_able'      => DI::l10n()->t('Diaspora support can\'t be enabled because Friendica was installed into a sub directory.'),
			'$diaspora_enabled'       => ['diaspora_enabled', DI::l10n()->t('Enable Diaspora support'), DI::config()->get('system', 'diaspora_enabled', $diaspora_able), DI::l10n()->t('Provide built-in Diaspora network compatibility.')],
			'$dfrn_only'              => ['dfrn_only', DI::l10n()->t('Only allow Friendica contacts'), DI::config()->get('system', 'dfrn_only'), DI::l10n()->t('All contacts must use Friendica protocols. All other built-in communication protocols disabled.')],
			'$verifyssl'              => ['verifyssl', DI::l10n()->t('Verify SSL'), DI::config()->get('system', 'verifyssl'), DI::l10n()->t('If you wish, you can turn on strict certificate checking. This will mean you cannot connect (at all) to self-signed SSL sites.')],
			'$proxyuser'              => ['proxyuser', DI::l10n()->t('Proxy user'), DI::config()->get('system', 'proxyuser'), ''],
			'$proxy'                  => ['proxy', DI::l10n()->t('Proxy URL'), DI::config()->get('system', 'proxy'), ''],
			'$timeout'                => ['timeout', DI::l10n()->t('Network timeout'), DI::config()->get('system', 'curl_timeout', 60), DI::l10n()->t('Value is in seconds. Set to 0 for unlimited (not recommended).')],
			'$maxloadavg'             => ['maxloadavg', DI::l10n()->t('Maximum Load Average'), DI::config()->get('system', 'maxloadavg', 20), DI::l10n()->t('Maximum system load before delivery and poll processes are deferred - default %d.', 20)],
			'$maxloadavg_frontend'    => ['maxloadavg_frontend', DI::l10n()->t('Maximum Load Average (Frontend)'), DI::config()->get('system', 'maxloadavg_frontend', 50), DI::l10n()->t('Maximum system load before the frontend quits service - default 50.')],
			'$min_memory'             => ['min_memory', DI::l10n()->t('Minimal Memory'), DI::config()->get('system', 'min_memory', 0), DI::l10n()->t('Minimal free memory in MB for the worker. Needs access to /proc/meminfo - default 0 (deactivated).')],
			'$optimize_max_tablesize' => ['optimize_max_tablesize', DI::l10n()->t('Maximum table size for optimization'), $optimize_max_tablesize, DI::l10n()->t('Maximum table size (in MB) for the automatic optimization. Enter -1 to disable it.')],
			'$optimize_fragmentation' => ['optimize_fragmentation', DI::l10n()->t('Minimum level of fragmentation'), DI::config()->get('system', 'optimize_fragmentation', 30), DI::l10n()->t('Minimum fragmenation level to start the automatic optimization - default value is 30%.')],

			'$poco_completion'        => ['poco_completion', DI::l10n()->t('Periodical check of global contacts'), DI::config()->get('system', 'poco_completion'), DI::l10n()->t('If enabled, the global contacts are checked periodically for missing or outdated data and the vitality of the contacts and servers.')],
			'$gcontact_discovery'     => ['gcontact_discovery', DI::l10n()->t('Discover followers/followings from global contacts'), DI::config()->get('system', 'gcontact_discovery'), DI::l10n()->t('"Direct contacts" queries our contacts for their followers and following contacts.') .'<br>'. DI::l10n()->t('"Contacts of contacts" discovers the contacts of the contacts as well. This will create huge masses of jobs. This option should only be activated on powerful machines. It could even be detected as attack on foreign servers.'), $discovery_choices],
			'$poco_requery_days'      => ['poco_requery_days', DI::l10n()->t('Days between requery'), DI::config()->get('system', 'poco_requery_days'), DI::l10n()->t('Number of days after which a server is requeried for his contacts.')],
			'$poco_discovery'         => ['poco_discovery', DI::l10n()->t('Discover contacts from other servers'), DI::config()->get('system', 'poco_discovery'), DI::l10n()->t('Periodically query other servers for contacts. You can choose between "Users": the users on the remote system, "Global Contacts": active contacts that are known on the system. The fallback is meant for Redmatrix servers and older friendica servers, where global contacts weren\'t available. The fallback increases the server load, so the recommended setting is "Users, Global Contacts".'), $poco_discovery_choices],
			'$poco_discovery_since'   => ['poco_discovery_since', DI::l10n()->t('Timeframe for fetching global contacts'), DI::config()->get('system', 'poco_discovery_since'), DI::l10n()->t('When the discovery is activated, this value defines the timeframe for the activity of the global contacts that are fetched from other servers.'), $poco_discovery_since_choices],
			'$poco_local_search'      => ['poco_local_search', DI::l10n()->t('Search the local directory'), DI::config()->get('system', 'poco_local_search'), DI::l10n()->t('Search the local directory instead of the global directory. When searching locally, every search will be executed on the global directory in the background. This improves the search results when the search is repeated.')],

			'$nodeinfo'               => ['nodeinfo', DI::l10n()->t('Publish server information'), DI::config()->get('system', 'nodeinfo'), DI::l10n()->t('If enabled, general server and usage data will be published. The data contains the name and version of the server, number of users with public profiles, number of posts and the activated protocols and connectors. See <a href="http://the-federation.info/">the-federation.info</a> for details.')],

			'$check_new_version_url'  => ['check_new_version_url', DI::l10n()->t('Check upstream version'), DI::config()->get('system', 'check_new_version_url'), DI::l10n()->t('Enables checking for new Friendica versions at github. If there is a new version, you will be informed in the admin panel overview.'), $check_git_version_choices],
			'$suppress_tags'          => ['suppress_tags', DI::l10n()->t('Suppress Tags'), DI::config()->get('system', 'suppress_tags'), DI::l10n()->t('Suppress showing a list of hashtags at the end of the posting.')],
			'$dbclean'                => ['dbclean', DI::l10n()->t('Clean database'), DI::config()->get('system', 'dbclean', false), DI::l10n()->t('Remove old remote items, orphaned database records and old content from some other helper tables.')],
			'$dbclean_expire_days'    => ['dbclean_expire_days', DI::l10n()->t('Lifespan of remote items'), DI::config()->get('system', 'dbclean-expire-days', 0), DI::l10n()->t('When the database cleanup is enabled, this defines the days after which remote items will be deleted. Own items, and marked or filed items are always kept. 0 disables this behaviour.')],
			'$dbclean_unclaimed'      => ['dbclean_unclaimed', DI::l10n()->t('Lifespan of unclaimed items'), DI::config()->get('system', 'dbclean-expire-unclaimed', 90), DI::l10n()->t('When the database cleanup is enabled, this defines the days after which unclaimed remote items (mostly content from the relay) will be deleted. Default value is 90 days. Defaults to the general lifespan value of remote items if set to 0.')],
			'$dbclean_expire_conv'    => ['dbclean_expire_conv', DI::l10n()->t('Lifespan of raw conversation data'), DI::config()->get('system', 'dbclean_expire_conversation', 90), DI::l10n()->t('The conversation data is used for ActivityPub and OStatus, as well as for debug purposes. It should be safe to remove it after 14 days, default is 90 days.')],
			'$itemcache'              => ['itemcache', DI::l10n()->t('Path to item cache'), DI::config()->get('system', 'itemcache'), DI::l10n()->t('The item caches buffers generated bbcode and external images.')],
			'$itemcache_duration'     => ['itemcache_duration', DI::l10n()->t('Cache duration in seconds'), DI::config()->get('system', 'itemcache_duration'), DI::l10n()->t('How long should the cache files be hold? Default value is 86400 seconds (One day). To disable the item cache, set the value to -1.')],
			'$max_comments'           => ['max_comments', DI::l10n()->t('Maximum numbers of comments per post'), DI::config()->get('system', 'max_comments'), DI::l10n()->t('How much comments should be shown for each post? Default value is 100.')],
			'$max_display_comments'   => ['max_display_comments', DI::l10n()->t('Maximum numbers of comments per post on the display page'), DI::config()->get('system', 'max_display_comments'), DI::l10n()->t('How many comments should be shown on the single view for each post? Default value is 1000.')],
			'$temppath'               => ['temppath', DI::l10n()->t('Temp path'), DI::config()->get('system', 'temppath'), DI::l10n()->t('If you have a restricted system where the webserver can\'t access the system temp path, enter another path here.')],
			'$proxy_disabled'         => ['proxy_disabled', DI::l10n()->t('Disable picture proxy'), DI::config()->get('system', 'proxy_disabled'), DI::l10n()->t('The picture proxy increases performance and privacy. It shouldn\'t be used on systems with very low bandwidth.')],
			'$only_tag_search'        => ['only_tag_search', DI::l10n()->t('Only search in tags'), DI::config()->get('system', 'only_tag_search'), DI::l10n()->t('On large systems the text search can slow down the system extremely.')],

			'$relocate_url'           => ['relocate_url', DI::l10n()->t('New base url'), DI::baseUrl()->get(), DI::l10n()->t('Change base url for this server. Sends relocate message to all Friendica and Diaspora* contacts of all users.')],

			'$rino'                   => ['rino', DI::l10n()->t('RINO Encryption'), intval(DI::config()->get('system', 'rino_encrypt')), DI::l10n()->t('Encryption layer between nodes.'), [0 => DI::l10n()->t('Disabled'), 1 => DI::l10n()->t('Enabled')]],

			'$worker_queues'          => ['worker_queues', DI::l10n()->t('Maximum number of parallel workers'), DI::config()->get('system', 'worker_queues'), DI::l10n()->t('On shared hosters set this to %d. On larger systems, values of %d are great. Default value is %d.', 5, 20, 10)],
			'$worker_dont_fork'       => ['worker_dont_fork', DI::l10n()->t('Don\'t use "proc_open" with the worker'), DI::config()->get('system', 'worker_dont_fork'), DI::l10n()->t('Enable this if your system doesn\'t allow the use of "proc_open". This can happen on shared hosters. If this is enabled you should increase the frequency of worker calls in your crontab.')],
			'$worker_fastlane'        => ['worker_fastlane', DI::l10n()->t('Enable fastlane'), DI::config()->get('system', 'worker_fastlane'), DI::l10n()->t('When enabed, the fastlane mechanism starts an additional worker if processes with higher priority are blocked by processes of lower priority.')],
			'$worker_frontend'        => ['worker_frontend', DI::l10n()->t('Enable frontend worker'), DI::config()->get('system', 'frontend_worker'), DI::l10n()->t('When enabled the Worker process is triggered when backend access is performed (e.g. messages being delivered). On smaller sites you might want to call %s/worker on a regular basis via an external cron job. You should only enable this option if you cannot utilize cron/scheduled jobs on your server.', DI::baseUrl()->get())],

			'$relay_subscribe'        => ['relay_subscribe', DI::l10n()->t('Subscribe to relay'), DI::config()->get('system', 'relay_subscribe'), DI::l10n()->t('Enables the receiving of public posts from the relay. They will be included in the search, subscribed tags and on the global community page.')],
			'$relay_server'           => ['relay_server', DI::l10n()->t('Relay server'), DI::config()->get('system', 'relay_server', 'https://relay.diasp.org'), DI::l10n()->t('Address of the relay server where public posts should be send to. For example https://relay.diasp.org')],
			'$relay_directly'         => ['relay_directly', DI::l10n()->t('Direct relay transfer'), DI::config()->get('system', 'relay_directly'), DI::l10n()->t('Enables the direct transfer to other servers without using the relay servers')],
			'$relay_scope'            => ['relay_scope', DI::l10n()->t('Relay scope'), DI::config()->get('system', 'relay_scope'), DI::l10n()->t('Can be "all" or "tags". "all" means that every public post should be received. "tags" means that only posts with selected tags should be received.'), ['' => DI::l10n()->t('Disabled'), 'all' => DI::l10n()->t('all'), 'tags' => DI::l10n()->t('tags')]],
			'$relay_server_tags'      => ['relay_server_tags', DI::l10n()->t('Server tags'), DI::config()->get('system', 'relay_server_tags'), DI::l10n()->t('Comma separated list of tags for the "tags" subscription.')],
			'$relay_user_tags'        => ['relay_user_tags', DI::l10n()->t('Allow user tags'), DI::config()->get('system', 'relay_user_tags', true), DI::l10n()->t('If enabled, the tags from the saved searches will used for the "tags" subscription in addition to the "relay_server_tags".')],

			'$form_security_token'    => parent::getFormSecurityToken('admin_site'),
			'$relocate_button'        => DI::l10n()->t('Start Relocation'),
		]);
	}
}
