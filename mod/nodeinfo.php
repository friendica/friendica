<?php
/**
 * @file mod/nodeinfo.php
 *
 * Documentation: http://nodeinfo.diaspora.software/schema.html
*/

use Friendica\App;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\System;

function nodeinfo_wellknown(App $a)
{
	$nodeinfo = ['links' => [['rel' => 'http://nodeinfo.diaspora.software/ns/schema/1.0',
					'href' => System::baseUrl().'/nodeinfo/1.0']]];

	header('Content-type: application/json; charset=utf-8');
	echo json_encode($nodeinfo, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	exit;
}

function nodeinfo_init(App $a)
{
	if (!Config::get('system', 'nodeinfo')) {
		http_status_exit(404);
		killme();
	}

	if (($a->argc != 2) || ($a->argv[1] != '1.0')) {
		http_status_exit(404);
		killme();
	}

	$smtp = (function_exists('imap_open') && !Config::get('system', 'imap_disabled') && !Config::get('system', 'dfrn_only'));

	$nodeinfo = [];
	$nodeinfo['version'] = '1.0';
	$nodeinfo['software'] = ['name' => 'friendica', 'version' => FRIENDICA_VERSION.'-'.DB_UPDATE_VERSION];

	$nodeinfo['protocols'] = [];
	$nodeinfo['protocols']['inbound'] = [];
	$nodeinfo['protocols']['outbound'] = [];

	if (Config::get('system', 'diaspora_enabled')) {
		$nodeinfo['protocols']['inbound'][] = 'diaspora';
		$nodeinfo['protocols']['outbound'][] = 'diaspora';
	}

	$nodeinfo['protocols']['inbound'][] = 'friendica';
	$nodeinfo['protocols']['outbound'][] = 'friendica';

	if (!Config::get('system', 'ostatus_disabled')) {
		$nodeinfo['protocols']['inbound'][] = 'gnusocial';
		$nodeinfo['protocols']['outbound'][] = 'gnusocial';
	}

	$nodeinfo['services'] = [];
	$nodeinfo['services']['inbound'] = [];
	$nodeinfo['services']['outbound'] = [];

	$nodeinfo['usage'] = [];

	$nodeinfo['openRegistrations'] = ($a->config['register_policy'] != 0);

	$nodeinfo['metadata'] = ['nodeName' => $a->config['sitename']];

	if (Config::get('system', 'nodeinfo')) {
		$nodeinfo['usage']['users'] = ['total' => (int)Config::get('nodeinfo', 'total_users'),
					'activeHalfyear' => (int)Config::get('nodeinfo', 'active_users_halfyear'),
					'activeMonth' => (int)Config::get('nodeinfo', 'active_users_monthly')];
		$nodeinfo['usage']['localPosts'] = (int)Config::get('nodeinfo', 'local_posts');
		$nodeinfo['usage']['localComments'] = (int)Config::get('nodeinfo', 'local_comments');

		if (Addon::isEnabled('appnet')) {
			$nodeinfo['services']['inbound'][] = 'appnet';
		}
		if (Addon::isEnabled('appnet') || Addon::isEnabled('buffer')) {
			$nodeinfo['services']['outbound'][] = 'appnet';
		}
		if (Addon::isEnabled('blogger')) {
			$nodeinfo['services']['outbound'][] = 'blogger';
		}
		if (Addon::isEnabled('dwpost')) {
			$nodeinfo['services']['outbound'][] = 'dreamwidth';
		}
		if (Addon::isEnabled('fbpost') || Addon::isEnabled('buffer')) {
			$nodeinfo['services']['outbound'][] = 'facebook';
		}
		if (Addon::isEnabled('statusnet')) {
			$nodeinfo['services']['inbound'][] = 'gnusocial';
			$nodeinfo['services']['outbound'][] = 'gnusocial';
		}

		if (Addon::isEnabled('gpluspost') || Addon::isEnabled('buffer')) {
			$nodeinfo['services']['outbound'][] = 'google';
		}
		if (Addon::isEnabled('ijpost')) {
			$nodeinfo['services']['outbound'][] = 'insanejournal';
		}
		if (Addon::isEnabled('libertree')) {
			$nodeinfo['services']['outbound'][] = 'libertree';
		}
		if (Addon::isEnabled('buffer')) {
			$nodeinfo['services']['outbound'][] = 'linkedin';
		}
		if (Addon::isEnabled('ljpost')) {
			$nodeinfo['services']['outbound'][] = 'livejournal';
		}
		if (Addon::isEnabled('buffer')) {
			$nodeinfo['services']['outbound'][] = 'pinterest';
		}
		if (Addon::isEnabled('posterous')) {
			$nodeinfo['services']['outbound'][] = 'posterous';
		}
		if (Addon::isEnabled('pumpio')) {
			$nodeinfo['services']['inbound'][] = 'pumpio';
			$nodeinfo['services']['outbound'][] = 'pumpio';
		}

		if ($smtp) {
			$nodeinfo['services']['outbound'][] = 'smtp';
		}
		if (Addon::isEnabled('tumblr')) {
			$nodeinfo['services']['outbound'][] = 'tumblr';
		}
		if (Addon::isEnabled('twitter') || Addon::isEnabled('buffer')) {
			$nodeinfo['services']['outbound'][] = 'twitter';
		}
		if (Addon::isEnabled('wppost')) {
			$nodeinfo['services']['outbound'][] = 'wordpress';
		}
		$nodeinfo['metadata']['protocols'] = $nodeinfo['protocols'];
		$nodeinfo['metadata']['protocols']['outbound'][] = 'atom1.0';
		$nodeinfo['metadata']['protocols']['inbound'][] = 'atom1.0';
		$nodeinfo['metadata']['protocols']['inbound'][] = 'rss2.0';

		$nodeinfo['metadata']['services'] = $nodeinfo['services'];

		if (Addon::isEnabled('twitter')) {
			$nodeinfo['metadata']['services']['inbound'][] = 'twitter';
		}
	}

	header('Content-type: application/json; charset=utf-8');
	echo json_encode($nodeinfo, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	exit;
}

function nodeinfo_cron()
{
	$a = get_app();

	// If the plugin 'statistics_json' is enabled then disable it and actrivate nodeinfo.
	if (Addon::isEnabled('statistics_json')) {
		Config::set('system', 'nodeinfo', true);

		$plugin = 'statistics_json';
		$plugins = Config::get('system', 'addon');
		$plugins_arr = [];

		if ($plugins) {
			$plugins_arr = explode(',', str_replace(' ', '', $plugins));

			$idx = array_search($plugin, $plugins_arr);
			if ($idx !== false) {
				unset($plugins_arr[$idx]);
				Addon::uninstallPlugin($plugin);
				Config::set('system', 'addon', implode(', ', $plugins_arr));
			}
		}
	}

	if (!Config::get('system', 'nodeinfo')) {
		return;
	}
	$last = Config::get('nodeinfo', 'last_calucation');

	if ($last) {
		// Calculate every 24 hours
		$next = $last + (24 * 60 * 60);
		if ($next > time()) {
			logger('calculation intervall not reached');
			return;
		}
	}
	logger('cron_start');

	$users = q("SELECT `user`.`uid`, `user`.`login_date`, `contact`.`last-item`
			FROM `user`
			INNER JOIN `profile` ON `profile`.`uid` = `user`.`uid` AND `profile`.`is-default`
			INNER JOIN `contact` ON `contact`.`uid` = `user`.`uid` AND `contact`.`self`
			WHERE (`profile`.`publish` OR `profile`.`net-publish`) AND `user`.`verified`
				AND NOT `user`.`blocked` AND NOT `user`.`account_removed`
				AND NOT `user`.`account_expired`");
	if (is_array($users)) {
			$total_users = count($users);
			$active_users_halfyear = 0;
			$active_users_monthly = 0;

			$halfyear = time() - (180 * 24 * 60 * 60);
			$month = time() - (30 * 24 * 60 * 60);

			foreach ($users AS $user) {
				if ((strtotime($user['login_date']) > $halfyear) ||
					(strtotime($user['last-item']) > $halfyear)) {
					++$active_users_halfyear;
				}
				if ((strtotime($user['login_date']) > $month) ||
					(strtotime($user['last-item']) > $month)) {
					++$active_users_monthly;
				}
			}
			Config::set('nodeinfo', 'total_users', $total_users);
		        logger('total_users: '.$total_users, LOGGER_DEBUG);

			Config::set('nodeinfo', 'active_users_halfyear', $active_users_halfyear);
			Config::set('nodeinfo', 'active_users_monthly', $active_users_monthly);
	}

	$posts = q("SELECT COUNT(*) AS `local_posts` FROM `thread` WHERE `thread`.`wall` AND `thread`.`uid` != 0");

	if (!is_array($posts)) {
		$local_posts = -1;
	} else {
		$local_posts = $posts[0]['local_posts'];
	}
	Config::set('nodeinfo', 'local_posts', $local_posts);

        logger('local_posts: '.$local_posts, LOGGER_DEBUG);

	$posts = q("SELECT COUNT(*) AS `local_comments` FROM `contact`
			INNER JOIN `item` ON `item`.`contact-id` = `contact`.`id` AND `item`.`uid` = `contact`.`uid` AND
				`item`.`id` != `item`.`parent` AND `item`.`network` IN ('%s', '%s', '%s')
			WHERE `contact`.`self`",
			dbesc(NETWORK_OSTATUS), dbesc(NETWORK_DIASPORA), dbesc(NETWORK_DFRN));

	if (!is_array($posts)) {
		$local_comments = -1;
	} else {
		$local_comments = $posts[0]['local_comments'];
	}
	Config::set('nodeinfo', 'local_comments', $local_comments);

	// Now trying to register
	$url = 'http://the-federation.info/register/'.$a->get_hostname();
        logger('registering url: '.$url, LOGGER_DEBUG);
	$ret = fetch_url($url);
        logger('registering answer: '.$ret, LOGGER_DEBUG);

        logger('cron_end');
	Config::set('nodeinfo', 'last_calucation', time());
}
