<?php
/**
 * @file mod/friendica.php
 */

use Friendica\App;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\DBA;

function friendica_init(App $a)
{
	if (!empty($a->argv[1]) && ($a->argv[1] == "json")) {
		$register_policy = ['REGISTER_CLOSED', 'REGISTER_APPROVE', 'REGISTER_OPEN'];

		$sql_extra = '';
		if (x($a->config, 'admin_nickname')) {
			$sql_extra = sprintf(" AND `nickname` = '%s' ", DBA::escape(Config::get('config', 'admin_nickname')));
		}
		if (!empty(Config::get('config', 'admin_email'))) {
			$adminlist = explode(",", str_replace(" ", "", Config::get('config', 'admin_email')));

			$r = q("SELECT `username`, `nickname` FROM `user` WHERE `email` = '%s' $sql_extra", DBA::escape($adminlist[0]));
			$admin = [
				'name' => $r[0]['username'],
				'profile'=> System::baseUrl() . '/profile/' . $r[0]['nickname'],
			];
		} else {
			$admin = false;
		}

		$visible_addons = [];
		if (is_array($a->addons) && count($a->addons)) {
			$r = q("SELECT * FROM `addon` WHERE `hidden` = 0");
			if (DBA::isResult($r)) {
				foreach ($r as $rr) {
					$visible_addons[] = $rr['name'];
				}
			}
		}

		Config::load('feature_lock');
		$locked_features = [];
		if (!empty($a->config['feature_lock']) && count($a->config['feature_lock'])) {
			foreach ($a->config['feature_lock'] as $k => $v) {
				if ($k === 'config_loaded') {
					continue;
				}

				$locked_features[$k] = intval($v);
			}
		}

		$data = [
			'version'         => FRIENDICA_VERSION,
			'url'             => System::baseUrl(),
			'addons'          => $visible_addons,
			'locked_features' => $locked_features,
			'register_policy' => $register_policy[intval(Config::get('config', 'register_policy'))],
			'admin'           => $admin,
			'site_name'       => Config::get('config', 'sitename'),
			'platform'        => FRIENDICA_PLATFORM,
			'info'            => Config::get('config', 'info'),
			'no_scrape_url'   => System::baseUrl().'/noscrape'
		];

		echo json_encode($data);
		killme();
	}
}

function friendica_content(App $a)
{
	$o = '<h1>Friendica</h1>' . PHP_EOL;
	$o .= '<p>';
	$o .= L10n::t('This is Friendica, version') . ' <strong>' . FRIENDICA_VERSION .
		' (' . DB_UPDATE_VERSION . '/' . Config::get("system", "post_update_version") . ') </strong> ';
	$o .= L10n::t('running at web location') . ' ' . System::baseUrl();
	$o .= '</p>' . PHP_EOL;

	$o .= '<p>';
	$o .= L10n::t('Please visit <a href="https://friendi.ca">Friendi.ca</a> to learn more about the Friendica project.') . PHP_EOL;
	$o .= '</p>' . PHP_EOL;

	$o .= '<p>';
	$o .= L10n::t('Bug reports and issues: please visit') . ' ' . '<a href="https://github.com/friendica/friendica/issues?state=open">'.L10n::t('the bugtracker at github').'</a>';
	$o .= '</p>' . PHP_EOL;
	$o .= '<p>';
	$o .= L10n::t('Suggestions, praise, etc. - please email "info" at "friendi - dot - ca');
	$o .= '</p>' . PHP_EOL;

	$visible_addons = [];
	if (is_array($a->addons) && count($a->addons)) {
		$r = q("SELECT * FROM `addon` WHERE `hidden` = 0");
		if (DBA::isResult($r)) {
			foreach ($r as $rr) {
				$visible_addons[] = $rr['name'];
			}
		}
	}

	if (count($visible_addons)) {
		$o .= '<p>' . L10n::t('Installed addons/apps:') . '</p>' . PHP_EOL;
		$sorted = $visible_addons;
		$s = '';
		sort($sorted);
		foreach ($sorted as $p) {
			if (strlen($p)) {
				if (strlen($s)) {
					$s .= ', ';
				}
				$s .= $p;
			}
		}
		$o .= '<div style="margin-left: 25px; margin-right: 25px; margin-bottom: 25px;">' . $s . '</div>' . PHP_EOL;
	} else {
		$o .= '<p>' . L10n::t('No installed addons/apps') . '</p>' . PHP_EOL;
	}

	if (Config::get('system', 'tosdisplay'))
	{
		$o .= '<p>'.L10n::t('Read about the <a href="%1$s/tos">Terms of Service</a> of this node.', System::baseurl()).'</p>';
	}

	$blocklist = Config::get('system', 'blocklist', []);
	if (!empty($blocklist)) {
		$o .= '<div id="about_blocklist"><p>' . L10n::t('On this server the following remote servers are blocked.') . '</p>' . PHP_EOL;
		$o .= '<table class="table"><thead><tr><th>' . L10n::t('Blocked domain') . '</th><th>' . L10n::t('Reason for the block') . '</th></thead><tbody>' . PHP_EOL;
		foreach ($blocklist as $b) {
			$o .= '<tr><td>' . $b['domain'] .'</td><td>' . $b['reason'] . '</td></tr>' . PHP_EOL;
		}
		$o .= '</tbody></table></div>' . PHP_EOL;
	}

	Addon::callHooks('about_hook', $o);

	return $o;
}
