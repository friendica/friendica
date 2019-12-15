<?php

namespace Friendica\Module\Admin;

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\StorageManager;
use Friendica\Core\Theme;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseAdminModule;
use Friendica\Module\Register;
use Friendica\Protocol\PortableContact;
use Friendica\Util\BasePath;
use Friendica\Util\Strings;
use Friendica\Worker\Delivery;

require_once __DIR__ . '/../../../boot.php';

class Site extends BaseAdminModule
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
				notice(L10n::t("Can not parse base url. Must have at least <scheme>://<domain>"));
				DI::baseUrl()->redirect('admin/site');
			}

			/* steps:
			 * replace all "baseurl" to "new_url" in config, profile, term, items and contacts
			 * send relocate for every local user
			 * */

			$old_url = $a->getBaseURL(true);

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
			update_table($a, "term", ['url'], $old_url, $new_url);
			update_table($a, "contact", ['photo', 'thumb', 'micro', 'url', 'nurl', 'alias', 'request', 'notify', 'poll', 'confirm', 'poco', 'avatar'], $old_url, $new_url);
			update_table($a, "gcontact", ['url', 'nurl', 'photo', 'server_url', 'notify', 'alias'], $old_url, $new_url);
			update_table($a, "item", ['owner-link', 'author-link', 'body', 'plink', 'tag'], $old_url, $new_url);

			// update profile addresses in the format "user@server.tld"
			update_table($a, "contact", ['addr'], $old_host, $new_host);
			update_table($a, "gcontact", ['connect', 'addr'], $old_host, $new_host);

			// update config
			Config::set('system', 'url', $new_url);
			DI::baseUrl()->saveByURL($new_url);

			// send relocate
			$usersStmt = DBA::select('user', ['uid'], ['account_removed' => false, 'account_expired' => false]);
			while ($user = DBA::fetch($usersStmt)) {
				Worker::add(PRIORITY_HIGH, 'Notifier', Delivery::RELOCATION, $user['uid']);
			}

			info("Relocation started. Could take a while to complete.");

			DI::baseUrl()->redirect('admin/site');
		}
		// end relocate

		$sitename         = (!empty($_POST['sitename'])         ? Strings::escapeTags(trim($_POST['sitename']))      : '');
		$sender_email     = (!empty($_POST['sender_email'])     ? Strings::escapeTags(trim($_POST['sender_email']))  : '');
		$banner           = (!empty($_POST['banner'])           ? trim($_POST['banner'])                             : false);
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

		/**
		 * @var $storagebackend \Friendica\Model\Storage\IStorage
		 */
		$storagebackend    = Strings::escapeTags(trim($_POST['storagebackend'] ?? ''));

		// save storage backend form
		if (!is_null($storagebackend) && $storagebackend != "") {
			if (StorageManager::setBackend($storagebackend)) {
				$storage_opts = $storagebackend::getOptions();
				$storage_form_prefix = preg_replace('|[^a-zA-Z0-9]|', '', $storagebackend);
				$storage_opts_data = [];
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

				$storage_form_errors = $storagebackend::saveOptions($storage_opts_data);
				if (count($storage_form_errors)) {
					foreach ($storage_form_errors as $name => $err) {
						notice('Storage backend, ' . $storage_opts[$name][1] . ': ' . $err);
					}
					DI::baseUrl()->redirect('admin/site' . $active_panel);
				}
			} else {
				info(L10n::t('Invalid storage backend setting value.'));
			}
		}

		// Has the directory url changed? If yes, then resubmit the existing profiles there
		if ($global_directory != Config::get('system', 'directory') && ($global_directory != '')) {
			Config::set('system', 'directory', $global_directory);
			Worker::add(PRIORITY_LOW, 'Directory');
		}

		if (DI::baseUrl()->getUrlPath() != "") {
			$diaspora_enabled = false;
		}
		if ($ssl_policy != intval(Config::get('system', 'ssl_policy'))) {
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
		Config::set('system', 'ssl_policy'            , $ssl_policy);
		Config::set('system', 'maxloadavg'            , $maxloadavg);
		Config::set('system', 'maxloadavg_frontend'   , $maxloadavg_frontend);
		Config::set('system', 'min_memory'            , $min_memory);
		Config::set('system', 'optimize_max_tablesize', $optimize_max_tablesize);
		Config::set('system', 'optimize_fragmentation', $optimize_fragmentation);
		Config::set('system', 'poco_completion'       , $poco_completion);
		Config::set('system', 'poco_requery_days'     , $poco_requery_days);
		Config::set('system', 'poco_discovery'        , $poco_discovery);
		Config::set('system', 'poco_discovery_since'  , $poco_discovery_since);
		Config::set('system', 'poco_local_search'     , $poco_local_search);
		Config::set('system', 'nodeinfo'              , $nodeinfo);
		Config::set('config', 'sitename'              , $sitename);
		Config::set('config', 'sender_email'          , $sender_email);
		Config::set('system', 'suppress_tags'         , $suppress_tags);
		Config::set('system', 'shortcut_icon'         , $shortcut_icon);
		Config::set('system', 'touch_icon'            , $touch_icon);

		if ($banner == "") {
			Config::delete('system', 'banner');
		} else {
			Config::set('system', 'banner', $banner);
		}

		if (empty($additional_info)) {
			Config::delete('config', 'info');
		} else {
			Config::set('config', 'info', $additional_info);
		}
		Config::set('system', 'language', $language);
		Config::set('system', 'theme', $theme);
		Theme::install($theme);

		if ($theme_mobile == '---') {
			Config::delete('system', 'mobile-theme');
		} else {
			Config::set('system', 'mobile-theme', $theme_mobile);
		}
		if ($singleuser == '---') {
			Config::delete('system', 'singleuser');
		} else {
			Config::set('system', 'singleuser', $singleuser);
		}
		Config::set('system', 'maximagesize'           , $maximagesize);
		Config::set('system', 'max_image_length'       , $maximagelength);
		Config::set('system', 'jpeg_quality'           , $jpegimagequality);

		Config::set('config', 'register_policy'        , $register_policy);
		Config::set('system', 'max_daily_registrations', $daily_registrations);
		Config::set('system', 'account_abandon_days'   , $abandon_days);
		Config::set('config', 'register_text'          , $register_text);
		Config::set('system', 'allowed_sites'          , $allowed_sites);
		Config::set('system', 'allowed_email'          , $allowed_email);
		Config::set('system', 'forbidden_nicknames'    , $forbidden_nicknames);
		Config::set('system', 'no_oembed_rich_content' , $no_oembed_rich_content);
		Config::set('system', 'allowed_oembed'         , $allowed_oembed);
		Config::set('system', 'block_public'           , $block_public);
		Config::set('system', 'publish_all'            , $force_publish);
		Config::set('system', 'newuser_private'        , $newuser_private);
		Config::set('system', 'enotify_no_content'     , $enotify_no_content);
		Config::set('system', 'disable_embedded'       , $disable_embedded);
		Config::set('system', 'allow_users_remote_self', $allow_users_remote_self);
		Config::set('system', 'explicit_content'       , $explicit_content);
		Config::set('system', 'check_new_version_url'  , $check_new_version_url);

		Config::set('system', 'block_extended_register', $no_multi_reg);
		Config::set('system', 'no_openid'              , $no_openid);
		Config::set('system', 'no_regfullname'         , $no_regfullname);
		Config::set('system', 'community_page_style'   , $community_page_style);
		Config::set('system', 'max_author_posts_community_page', $max_author_posts_community_page);
		Config::set('system', 'verifyssl'              , $verifyssl);
		Config::set('system', 'proxyuser'              , $proxyuser);
		Config::set('system', 'proxy'                  , $proxy);
		Config::set('system', 'curl_timeout'           , $timeout);
		Config::set('system', 'dfrn_only'              , $dfrn_only);
		Config::set('system', 'ostatus_disabled'       , $ostatus_disabled);
		Config::set('system', 'diaspora_enabled'       , $diaspora_enabled);

		Config::set('config', 'private_addons'         , $private_addons);

		Config::set('system', 'force_ssl'              , $force_ssl);
		Config::set('system', 'hide_help'              , $hide_help);

		Config::set('system', 'dbclean'                , $dbclean);
		Config::set('system', 'dbclean-expire-days'    , $dbclean_expire_days);
		Config::set('system', 'dbclean_expire_conversation', $dbclean_expire_conv);

		if ($dbclean_unclaimed == 0) {
			$dbclean_unclaimed = $dbclean_expire_days;
		}

		Config::set('system', 'dbclean-expire-unclaimed', $dbclean_unclaimed);

		if ($itemcache != '') {
			$itemcache = BasePath::getRealPath($itemcache);
		}

		Config::set('system', 'itemcache', $itemcache);
		Config::set('system', 'itemcache_duration', $itemcache_duration);
		Config::set('system', 'max_comments', $max_comments);

		if ($temppath != '') {
			$temppath = BasePath::getRealPath($temppath);
		}

		Config::set('system', 'temppath', $temppath);

		Config::set('system', 'proxy_disabled'   , $proxy_disabled);
		Config::set('system', 'only_tag_search'  , $only_tag_search);

		Config::set('system', 'worker_queues'    , $worker_queues);
		Config::set('system', 'worker_dont_fork' , $worker_dont_fork);
		Config::set('system', 'worker_fastlane'  , $worker_fastlane);
		Config::set('system', 'frontend_worker'  , $worker_frontend);

		Config::set('system', 'relay_directly'   , $relay_directly);
		Config::set('system', 'relay_server'     , $relay_server);
		Config::set('system', 'relay_subscribe'  , $relay_subscribe);
		Config::set('system', 'relay_scope'      , $relay_scope);
		Config::set('system', 'relay_server_tags', $relay_server_tags);
		Config::set('system', 'relay_user_tags'  , $relay_user_tags);

		Config::set('system', 'rino_encrypt'     , $rino);

		info(L10n::t('Site settings updated.') . EOL);

		DI::baseUrl()->redirect('admin/site' . $active_panel);
	}

	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		$a = DI::app();

		/* Installed langs */
		$lang_choices = L10n::getAvailableLanguages();

		if (strlen(Config::get('system', 'directory_submit_url')) &&
			!strlen(Config::get('system', 'directory'))) {
			Config::set('system', 'directory', dirname(Config::get('system', 'directory_submit_url')));
			Config::delete('system', 'directory_submit_url');
		}

		/* Installed themes */
		$theme_choices = [];
		$theme_choices_mobile = [];
		$theme_choices_mobile['---'] = L10n::t('No special theme for mobile devices');
		$files = glob('view/theme/*');
		if (is_array($files)) {
			$allowed_theme_list = Config::get('system', 'allowed_themes');

			foreach ($files as $file) {
				if (intval(file_exists($file . '/unsupported'))) {
					continue;
				}

				$f = basename($file);

				// Only show allowed themes here
				if (($allowed_theme_list != '') && !strstr($allowed_theme_list, $f)) {
					continue;
				}

				$theme_name = ((file_exists($file . '/experimental')) ? L10n::t('%s - (Experimental)', $f) : $f);

				if (file_exists($file . '/mobile')) {
					$theme_choices_mobile[$f] = $theme_name;
				} else {
					$theme_choices[$f] = $theme_name;
				}
			}
		}

		/* Community page style */
		$community_page_style_choices = [
			CP_NO_INTERNAL_COMMUNITY => L10n::t('No community page for local users'),
			CP_NO_COMMUNITY_PAGE => L10n::t('No community page'),
			CP_USERS_ON_SERVER => L10n::t('Public postings from users of this site'),
			CP_GLOBAL_COMMUNITY => L10n::t('Public postings from the federated network'),
			CP_USERS_AND_GLOBAL => L10n::t('Public postings from local users and the federated network')
		];

		$poco_discovery_choices = [
			PortableContact::DISABLED => L10n::t('Disabled'),
			PortableContact::USERS => L10n::t('Users'),
			PortableContact::USERS_GCONTACTS => L10n::t('Users, Global Contacts'),
			PortableContact::USERS_GCONTACTS_FALLBACK => L10n::t('Users, Global Contacts/fallback'),
		];

		$poco_discovery_since_choices = [
			'30' => L10n::t('One month'),
			'91' => L10n::t('Three months'),
			'182' => L10n::t('Half a year'),
			'365' => L10n::t('One year'),
		];

		/* get user names to make the install a personal install of X */
		// @TODO Move to Model\User::getNames()
		$user_names = [];
		$user_names['---'] = L10n::t('Multi user instance');

		$usersStmt = DBA::select('user', ['username', 'nickname'], ['account_removed' => 0, 'account_expired' => 0]);
		foreach (DBA::toArray($usersStmt) as $user) {
			$user_names[$user['nickname']] = $user['username'];
		}

		/* Banner */
		$banner = Config::get('system', 'banner');

		if ($banner == false) {
			$banner = '<a href="https://friendi.ca"><img id="logo-img" src="images/friendica-32.png" alt="logo" /></a><span id="logo-text"><a href="https://friendi.ca">Friendica</a></span>';
		}

		$additional_info = Config::get('config', 'info');

		// Automatically create temporary paths
		get_temppath();
		get_itemcachepath();

		/* Register policy */
		$register_choices = [
			Register::CLOSED => L10n::t('Closed'),
			Register::APPROVE => L10n::t('Requires approval'),
			Register::OPEN => L10n::t('Open')
		];

		$ssl_choices = [
			App\BaseURL::SSL_POLICY_NONE => L10n::t('No SSL policy, links will track page SSL state'),
			App\BaseURL::SSL_POLICY_FULL => L10n::t('Force all links to use SSL'),
			App\BaseURL::SSL_POLICY_SELFSIGN => L10n::t('Self-signed certificate, use SSL for local links only (discouraged)')
		];

		$check_git_version_choices = [
			'none' => L10n::t('Don\'t check'),
			'master' => L10n::t('check the stable version'),
			'develop' => L10n::t('check the development version')
		];

		$diaspora_able = (DI::baseUrl()->getUrlPath() == '');

		$optimize_max_tablesize = Config::get('system', 'optimize_max_tablesize', -1);

		if ($optimize_max_tablesize <= 0) {
			$optimize_max_tablesize = -1;
		}

		$storage_backends = StorageManager::listBackends();
		/** @var $current_storage_backend \Friendica\Model\Storage\IStorage */
		$current_storage_backend = StorageManager::getBackend();

		$available_storage_backends = [];

		// show legacy option only if it is the current backend:
		// once changed can't be selected anymore
		if ($current_storage_backend == '') {
			$available_storage_backends[''] = L10n::t('Database (legacy)');
		}

		foreach ($storage_backends as $name => $class) {
			$available_storage_backends[$class] = $name;
		}
		unset($storage_backends);

		// build storage config form,
		$storage_form_prefix = preg_replace('|[^a-zA-Z0-9]|' ,'', $current_storage_backend);

		$storage_form = [];
		if (!is_null($current_storage_backend) && $current_storage_backend != '') {
			foreach ($current_storage_backend::getOptions() as $name => $info) {
				$type = $info[0];
				$info[0] = $storage_form_prefix . '_' . $name;
				$info['type'] = $type;
				$info['field'] = 'field_' . $type . '.tpl';
				$storage_form[$name] = $info;
			}
		}

		$t = Renderer::getMarkupTemplate('admin/site.tpl');
		return Renderer::replaceMacros($t, [
			'$title'             => L10n::t('Administration'),
			'$page'              => L10n::t('Site'),
			'$submit'            => L10n::t('Save Settings'),
			'$republish'         => L10n::t('Republish users to directory'),
			'$registration'      => L10n::t('Registration'),
			'$upload'            => L10n::t('File upload'),
			'$corporate'         => L10n::t('Policies'),
			'$advanced'          => L10n::t('Advanced'),
			'$portable_contacts' => L10n::t('Auto Discovered Contact Directory'),
			'$performance'       => L10n::t('Performance'),
			'$worker_title'      => L10n::t('Worker'),
			'$relay_title'       => L10n::t('Message Relay'),
			'$relocate'          => L10n::t('Relocate Instance'),
			'$relocate_warning'  => L10n::t('Warning! Advanced function. Could make this server unreachable.'),
			'$baseurl'           => $a->getBaseURL(true),

			// name, label, value, help string, extra data...
			'$sitename'         => ['sitename', L10n::t('Site name'), Config::get('config', 'sitename'), ''],
			'$sender_email'     => ['sender_email', L10n::t('Sender Email'), Config::get('config', 'sender_email'), L10n::t('The email address your server shall use to send notification emails from.'), '', '', 'email'],
			'$banner'           => ['banner', L10n::t('Banner/Logo'), $banner, ''],
			'$shortcut_icon'    => ['shortcut_icon', L10n::t('Shortcut icon'), Config::get('system', 'shortcut_icon'), L10n::t('Link to an icon that will be used for browsers.')],
			'$touch_icon'       => ['touch_icon', L10n::t('Touch icon'), Config::get('system', 'touch_icon'), L10n::t('Link to an icon that will be used for tablets and mobiles.')],
			'$additional_info'  => ['additional_info', L10n::t('Additional Info'), $additional_info, L10n::t('For public servers: you can add additional information here that will be listed at %s/servers.', get_server())],
			'$language'         => ['language', L10n::t('System language'), Config::get('system', 'language'), '', $lang_choices],
			'$theme'            => ['theme', L10n::t('System theme'), Config::get('system', 'theme'), L10n::t('Default system theme - may be over-ridden by user profiles - <a href="/admin/themes" id="cnftheme">Change default theme settings</a>'), $theme_choices],
			'$theme_mobile'     => ['theme_mobile', L10n::t('Mobile system theme'), Config::get('system', 'mobile-theme', '---'), L10n::t('Theme for mobile devices'), $theme_choices_mobile],
			'$ssl_policy'       => ['ssl_policy', L10n::t('SSL link policy'), (string)intval(Config::get('system', 'ssl_policy')), L10n::t('Determines whether generated links should be forced to use SSL'), $ssl_choices],
			'$force_ssl'        => ['force_ssl', L10n::t('Force SSL'), Config::get('system', 'force_ssl'), L10n::t('Force all Non-SSL requests to SSL - Attention: on some systems it could lead to endless loops.')],
			'$hide_help'        => ['hide_help', L10n::t('Hide help entry from navigation menu'), Config::get('system', 'hide_help'), L10n::t('Hides the menu entry for the Help pages from the navigation menu. You can still access it calling /help directly.')],
			'$singleuser'       => ['singleuser', L10n::t('Single user instance'), Config::get('system', 'singleuser', '---'), L10n::t('Make this instance multi-user or single-user for the named user'), $user_names],

			'$storagebackend'   => ['storagebackend', L10n::t('File storage backend'), $current_storage_backend, L10n::t('The backend used to store uploaded data. If you change the storage backend, you can manually move the existing files. If you do not do so, the files uploaded before the change will still be available at the old backend. Please see <a href="/help/Settings#1_2_3_1">the settings documentation</a> for more information about the choices and the moving procedure.'), $available_storage_backends],
			'$storageform'      => $storage_form,
			'$maximagesize'     => ['maximagesize', L10n::t('Maximum image size'), Config::get('system', 'maximagesize'), L10n::t('Maximum size in bytes of uploaded images. Default is 0, which means no limits.')],
			'$maximagelength'   => ['maximagelength', L10n::t('Maximum image length'), Config::get('system', 'max_image_length'), L10n::t('Maximum length in pixels of the longest side of uploaded images. Default is -1, which means no limits.')],
			'$jpegimagequality' => ['jpegimagequality', L10n::t('JPEG image quality'), Config::get('system', 'jpeg_quality'), L10n::t('Uploaded JPEGS will be saved at this quality setting [0-100]. Default is 100, which is full quality.')],

			'$register_policy'        => ['register_policy', L10n::t('Register policy'), Config::get('config', 'register_policy'), '', $register_choices],
			'$daily_registrations'    => ['max_daily_registrations', L10n::t('Maximum Daily Registrations'), Config::get('system', 'max_daily_registrations'), L10n::t('If registration is permitted above, this sets the maximum number of new user registrations to accept per day.  If register is set to closed, this setting has no effect.')],
			'$register_text'          => ['register_text', L10n::t('Register text'), Config::get('config', 'register_text'), L10n::t('Will be displayed prominently on the registration page. You can use BBCode here.')],
			'$forbidden_nicknames'    => ['forbidden_nicknames', L10n::t('Forbidden Nicknames'), Config::get('system', 'forbidden_nicknames'), L10n::t('Comma separated list of nicknames that are forbidden from registration. Preset is a list of role names according RFC 2142.')],
			'$abandon_days'           => ['abandon_days', L10n::t('Accounts abandoned after x days'), Config::get('system', 'account_abandon_days'), L10n::t('Will not waste system resources polling external sites for abandonded accounts. Enter 0 for no time limit.')],
			'$allowed_sites'          => ['allowed_sites', L10n::t('Allowed friend domains'), Config::get('system', 'allowed_sites'), L10n::t('Comma separated list of domains which are allowed to establish friendships with this site. Wildcards are accepted. Empty to allow any domains')],
			'$allowed_email'          => ['allowed_email', L10n::t('Allowed email domains'), Config::get('system', 'allowed_email'), L10n::t('Comma separated list of domains which are allowed in email addresses for registrations to this site. Wildcards are accepted. Empty to allow any domains')],
			'$no_oembed_rich_content' => ['no_oembed_rich_content', L10n::t('No OEmbed rich content'), Config::get('system', 'no_oembed_rich_content'), L10n::t('Don\'t show the rich content (e.g. embedded PDF), except from the domains listed below.')],
			'$allowed_oembed'         => ['allowed_oembed', L10n::t('Allowed OEmbed domains'), Config::get('system', 'allowed_oembed'), L10n::t('Comma separated list of domains which oembed content is allowed to be displayed. Wildcards are accepted.')],
			'$block_public'           => ['block_public', L10n::t('Block public'), Config::get('system', 'block_public'), L10n::t('Check to block public access to all otherwise public personal pages on this site unless you are currently logged in.')],
			'$force_publish'          => ['publish_all', L10n::t('Force publish'), Config::get('system', 'publish_all'), L10n::t('Check to force all profiles on this site to be listed in the site directory.') . '<strong>' . L10n::t('Enabling this may violate privacy laws like the GDPR') . '</strong>'],
			'$global_directory'       => ['directory', L10n::t('Global directory URL'), Config::get('system', 'directory', 'https://dir.friendica.social'), L10n::t('URL to the global directory. If this is not set, the global directory is completely unavailable to the application.')],
			'$newuser_private'        => ['newuser_private', L10n::t('Private posts by default for new users'), Config::get('system', 'newuser_private'), L10n::t('Set default post permissions for all new members to the default privacy group rather than public.')],
			'$enotify_no_content'     => ['enotify_no_content', L10n::t('Don\'t include post content in email notifications'), Config::get('system', 'enotify_no_content'), L10n::t('Don\'t include the content of a post/comment/private message/etc. in the email notifications that are sent out from this site, as a privacy measure.')],
			'$private_addons'         => ['private_addons', L10n::t('Disallow public access to addons listed in the apps menu.'), Config::get('config', 'private_addons'), L10n::t('Checking this box will restrict addons listed in the apps menu to members only.')],
			'$disable_embedded'       => ['disable_embedded', L10n::t('Don\'t embed private images in posts'), Config::get('system', 'disable_embedded'), L10n::t('Don\'t replace locally-hosted private photos in posts with an embedded copy of the image. This means that contacts who receive posts containing private photos will have to authenticate and load each image, which may take a while.')],
			'$explicit_content'       => ['explicit_content', L10n::t('Explicit Content'), Config::get('system', 'explicit_content', false), L10n::t('Set this to announce that your node is used mostly for explicit content that might not be suited for minors. This information will be published in the node information and might be used, e.g. by the global directory, to filter your node from listings of nodes to join. Additionally a note about this will be shown at the user registration page.')],
			'$allow_users_remote_self'=> ['allow_users_remote_self', L10n::t('Allow Users to set remote_self'), Config::get('system', 'allow_users_remote_self'), L10n::t('With checking this, every user is allowed to mark every contact as a remote_self in the repair contact dialog. Setting this flag on a contact causes mirroring every posting of that contact in the users stream.')],
			'$no_multi_reg'           => ['no_multi_reg', L10n::t('Block multiple registrations'), Config::get('system', 'block_extended_register'), L10n::t('Disallow users to register additional accounts for use as pages.')],
			'$no_openid'              => ['no_openid', L10n::t('Disable OpenID'), Config::get('system', 'no_openid'), L10n::t('Disable OpenID support for registration and logins.')],
			'$no_regfullname'         => ['no_regfullname', L10n::t('No Fullname check'), Config::get('system', 'no_regfullname'), L10n::t('Allow users to register without a space between the first name and the last name in their full name.')],
			'$community_page_style'   => ['community_page_style', L10n::t('Community pages for visitors'), Config::get('system', 'community_page_style'), L10n::t('Which community pages should be available for visitors. Local users always see both pages.'), $community_page_style_choices],
			'$max_author_posts_community_page' => ['max_author_posts_community_page', L10n::t('Posts per user on community page'), Config::get('system', 'max_author_posts_community_page'), L10n::t('The maximum number of posts per user on the community page. (Not valid for "Global Community")')],
			'$ostatus_disabled'       => ['ostatus_disabled', L10n::t('Disable OStatus support'), Config::get('system', 'ostatus_disabled'), L10n::t('Disable built-in OStatus (StatusNet, GNU Social etc.) compatibility. All communications in OStatus are public, so privacy warnings will be occasionally displayed.')],
			'$ostatus_not_able'       => L10n::t('OStatus support can only be enabled if threading is enabled.'),
			'$diaspora_able'          => $diaspora_able,
			'$diaspora_not_able'      => L10n::t('Diaspora support can\'t be enabled because Friendica was installed into a sub directory.'),
			'$diaspora_enabled'       => ['diaspora_enabled', L10n::t('Enable Diaspora support'), Config::get('system', 'diaspora_enabled', $diaspora_able), L10n::t('Provide built-in Diaspora network compatibility.')],
			'$dfrn_only'              => ['dfrn_only', L10n::t('Only allow Friendica contacts'), Config::get('system', 'dfrn_only'), L10n::t('All contacts must use Friendica protocols. All other built-in communication protocols disabled.')],
			'$verifyssl'              => ['verifyssl', L10n::t('Verify SSL'), Config::get('system', 'verifyssl'), L10n::t('If you wish, you can turn on strict certificate checking. This will mean you cannot connect (at all) to self-signed SSL sites.')],
			'$proxyuser'              => ['proxyuser', L10n::t('Proxy user'), Config::get('system', 'proxyuser'), ''],
			'$proxy'                  => ['proxy', L10n::t('Proxy URL'), Config::get('system', 'proxy'), ''],
			'$timeout'                => ['timeout', L10n::t('Network timeout'), Config::get('system', 'curl_timeout', 60), L10n::t('Value is in seconds. Set to 0 for unlimited (not recommended).')],
			'$maxloadavg'             => ['maxloadavg', L10n::t('Maximum Load Average'), Config::get('system', 'maxloadavg', 20), L10n::t('Maximum system load before delivery and poll processes are deferred - default %d.', 20)],
			'$maxloadavg_frontend'    => ['maxloadavg_frontend', L10n::t('Maximum Load Average (Frontend)'), Config::get('system', 'maxloadavg_frontend', 50), L10n::t('Maximum system load before the frontend quits service - default 50.')],
			'$min_memory'             => ['min_memory', L10n::t('Minimal Memory'), Config::get('system', 'min_memory', 0), L10n::t('Minimal free memory in MB for the worker. Needs access to /proc/meminfo - default 0 (deactivated).')],
			'$optimize_max_tablesize' => ['optimize_max_tablesize', L10n::t('Maximum table size for optimization'), $optimize_max_tablesize, L10n::t('Maximum table size (in MB) for the automatic optimization. Enter -1 to disable it.')],
			'$optimize_fragmentation' => ['optimize_fragmentation', L10n::t('Minimum level of fragmentation'), Config::get('system', 'optimize_fragmentation', 30), L10n::t('Minimum fragmenation level to start the automatic optimization - default value is 30%.')],

			'$poco_completion'        => ['poco_completion', L10n::t('Periodical check of global contacts'), Config::get('system', 'poco_completion'), L10n::t('If enabled, the global contacts are checked periodically for missing or outdated data and the vitality of the contacts and servers.')],
			'$poco_requery_days'      => ['poco_requery_days', L10n::t('Days between requery'), Config::get('system', 'poco_requery_days'), L10n::t('Number of days after which a server is requeried for his contacts.')],
			'$poco_discovery'         => ['poco_discovery', L10n::t('Discover contacts from other servers'), (string)intval(Config::get('system', 'poco_discovery')), L10n::t('Periodically query other servers for contacts. You can choose between "Users": the users on the remote system, "Global Contacts": active contacts that are known on the system. The fallback is meant for Redmatrix servers and older friendica servers, where global contacts weren\'t available. The fallback increases the server load, so the recommended setting is "Users, Global Contacts".'), $poco_discovery_choices],
			'$poco_discovery_since'   => ['poco_discovery_since', L10n::t('Timeframe for fetching global contacts'), (string)intval(Config::get('system', 'poco_discovery_since')), L10n::t('When the discovery is activated, this value defines the timeframe for the activity of the global contacts that are fetched from other servers.'), $poco_discovery_since_choices],
			'$poco_local_search'      => ['poco_local_search', L10n::t('Search the local directory'), Config::get('system', 'poco_local_search'), L10n::t('Search the local directory instead of the global directory. When searching locally, every search will be executed on the global directory in the background. This improves the search results when the search is repeated.')],

			'$nodeinfo'               => ['nodeinfo', L10n::t('Publish server information'), Config::get('system', 'nodeinfo'), L10n::t('If enabled, general server and usage data will be published. The data contains the name and version of the server, number of users with public profiles, number of posts and the activated protocols and connectors. See <a href="http://the-federation.info/">the-federation.info</a> for details.')],

			'$check_new_version_url'  => ['check_new_version_url', L10n::t('Check upstream version'), Config::get('system', 'check_new_version_url'), L10n::t('Enables checking for new Friendica versions at github. If there is a new version, you will be informed in the admin panel overview.'), $check_git_version_choices],
			'$suppress_tags'          => ['suppress_tags', L10n::t('Suppress Tags'), Config::get('system', 'suppress_tags'), L10n::t('Suppress showing a list of hashtags at the end of the posting.')],
			'$dbclean'                => ['dbclean', L10n::t('Clean database'), Config::get('system', 'dbclean', false), L10n::t('Remove old remote items, orphaned database records and old content from some other helper tables.')],
			'$dbclean_expire_days'    => ['dbclean_expire_days', L10n::t('Lifespan of remote items'), Config::get('system', 'dbclean-expire-days', 0), L10n::t('When the database cleanup is enabled, this defines the days after which remote items will be deleted. Own items, and marked or filed items are always kept. 0 disables this behaviour.')],
			'$dbclean_unclaimed'      => ['dbclean_unclaimed', L10n::t('Lifespan of unclaimed items'), Config::get('system', 'dbclean-expire-unclaimed', 90), L10n::t('When the database cleanup is enabled, this defines the days after which unclaimed remote items (mostly content from the relay) will be deleted. Default value is 90 days. Defaults to the general lifespan value of remote items if set to 0.')],
			'$dbclean_expire_conv'    => ['dbclean_expire_conv', L10n::t('Lifespan of raw conversation data'), Config::get('system', 'dbclean_expire_conversation', 90), L10n::t('The conversation data is used for ActivityPub and OStatus, as well as for debug purposes. It should be safe to remove it after 14 days, default is 90 days.')],
			'$itemcache'              => ['itemcache', L10n::t('Path to item cache'), Config::get('system', 'itemcache'), L10n::t('The item caches buffers generated bbcode and external images.')],
			'$itemcache_duration'     => ['itemcache_duration', L10n::t('Cache duration in seconds'), Config::get('system', 'itemcache_duration'), L10n::t('How long should the cache files be hold? Default value is 86400 seconds (One day). To disable the item cache, set the value to -1.')],
			'$max_comments'           => ['max_comments', L10n::t('Maximum numbers of comments per post'), Config::get('system', 'max_comments'), L10n::t('How much comments should be shown for each post? Default value is 100.')],
			'$temppath'               => ['temppath', L10n::t('Temp path'), Config::get('system', 'temppath'), L10n::t('If you have a restricted system where the webserver can\'t access the system temp path, enter another path here.')],
			'$proxy_disabled'         => ['proxy_disabled', L10n::t('Disable picture proxy'), Config::get('system', 'proxy_disabled'), L10n::t('The picture proxy increases performance and privacy. It shouldn\'t be used on systems with very low bandwidth.')],
			'$only_tag_search'        => ['only_tag_search', L10n::t('Only search in tags'), Config::get('system', 'only_tag_search'), L10n::t('On large systems the text search can slow down the system extremely.')],

			'$relocate_url'           => ['relocate_url', L10n::t('New base url'), $a->getBaseURL(), L10n::t('Change base url for this server. Sends relocate message to all Friendica and Diaspora* contacts of all users.')],

			'$rino'                   => ['rino', L10n::t('RINO Encryption'), intval(Config::get('system', 'rino_encrypt')), L10n::t('Encryption layer between nodes.'), [0 => L10n::t('Disabled'), 1 => L10n::t('Enabled')]],

			'$worker_queues'          => ['worker_queues', L10n::t('Maximum number of parallel workers'), Config::get('system', 'worker_queues'), L10n::t('On shared hosters set this to %d. On larger systems, values of %d are great. Default value is %d.', 5, 20, 10)],
			'$worker_dont_fork'       => ['worker_dont_fork', L10n::t('Don\'t use "proc_open" with the worker'), Config::get('system', 'worker_dont_fork'), L10n::t('Enable this if your system doesn\'t allow the use of "proc_open". This can happen on shared hosters. If this is enabled you should increase the frequency of worker calls in your crontab.')],
			'$worker_fastlane'        => ['worker_fastlane', L10n::t('Enable fastlane'), Config::get('system', 'worker_fastlane'), L10n::t('When enabed, the fastlane mechanism starts an additional worker if processes with higher priority are blocked by processes of lower priority.')],
			'$worker_frontend'        => ['worker_frontend', L10n::t('Enable frontend worker'), Config::get('system', 'frontend_worker'), L10n::t('When enabled the Worker process is triggered when backend access is performed (e.g. messages being delivered). On smaller sites you might want to call %s/worker on a regular basis via an external cron job. You should only enable this option if you cannot utilize cron/scheduled jobs on your server.', $a->getBaseURL())],

			'$relay_subscribe'        => ['relay_subscribe', L10n::t('Subscribe to relay'), Config::get('system', 'relay_subscribe'), L10n::t('Enables the receiving of public posts from the relay. They will be included in the search, subscribed tags and on the global community page.')],
			'$relay_server'           => ['relay_server', L10n::t('Relay server'), Config::get('system', 'relay_server', 'https://relay.diasp.org'), L10n::t('Address of the relay server where public posts should be send to. For example https://relay.diasp.org')],
			'$relay_directly'         => ['relay_directly', L10n::t('Direct relay transfer'), Config::get('system', 'relay_directly'), L10n::t('Enables the direct transfer to other servers without using the relay servers')],
			'$relay_scope'            => ['relay_scope', L10n::t('Relay scope'), Config::get('system', 'relay_scope'), L10n::t('Can be "all" or "tags". "all" means that every public post should be received. "tags" means that only posts with selected tags should be received.'), ['' => L10n::t('Disabled'), 'all' => L10n::t('all'), 'tags' => L10n::t('tags')]],
			'$relay_server_tags'      => ['relay_server_tags', L10n::t('Server tags'), Config::get('system', 'relay_server_tags'), L10n::t('Comma separated list of tags for the "tags" subscription.')],
			'$relay_user_tags'        => ['relay_user_tags', L10n::t('Allow user tags'), Config::get('system', 'relay_user_tags', true), L10n::t('If enabled, the tags from the saved searches will used for the "tags" subscription in addition to the "relay_server_tags".')],

			'$form_security_token'    => parent::getFormSecurityToken('admin_site'),
			'$relocate_button'        => L10n::t('Start Relocation'),
		]);
	}
}
