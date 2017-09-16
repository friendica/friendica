<?php

use Friendica\App;
use Friendica\Core\Config;

function friendica_init(App $a) {
	if ($a->argv[1] == "json"){
		$register_policy = Array('REGISTER_CLOSED', 'REGISTER_APPROVE', 'REGISTER_OPEN');

		$sql_extra = '';
		if (x($a->config,'admin_nickname')) {
			$sql_extra = sprintf(" AND `nickname` = '%s' ", dbesc($a->config['admin_nickname']));
		}
		if (isset($a->config['admin_email']) && $a->config['admin_email']!='') {
			$adminlist = explode(",", str_replace(" ", "", $a->config['admin_email']));

			$r = q("SELECT `username`, `nickname` FROM `user` WHERE `email` = '%s' $sql_extra", dbesc($adminlist[0]));
			$admin = array(
				'name' => $r[0]['username'],
				'profile'=> App::get_baseurl() . '/profile/' . $r[0]['nickname'],
			);
		} else {
			$admin = false;
		}

		$visible_plugins = array();
		if (is_array($a->plugins) && count($a->plugins)) {
			$r = q("SELECT * FROM `addon` WHERE `hidden` = 0");
			if (dbm::is_result($r)) {
				foreach($r as $rr) {
					$visible_plugins[] = $rr['name'];
				}
			}
		}

		Config::load('feature_lock');
		$locked_features = array();
		if (is_array($a->config['feature_lock']) && count($a->config['feature_lock'])) {
			foreach ($a->config['feature_lock'] as $k => $v) {
				if ($k === 'config_loaded') {
					continue;
				}

				$locked_features[$k] = intval($v);
			}
		}

		$data = Array(
			'version'         => FRIENDICA_VERSION,
			'url'             => z_root(),
			'plugins'         => $visible_plugins,
			'locked_features' => $locked_features,
			'register_policy' =>  $register_policy[$a->config['register_policy']],
			'admin'           => $admin,
			'site_name'       => $a->config['sitename'],
			'platform'        => FRIENDICA_PLATFORM,
			'info'            => ((x($a->config,'info')) ? $a->config['info'] : ''),
			'no_scrape_url'   => App::get_baseurl().'/noscrape'
		);

		echo json_encode($data);
		killme();
	}
}

function friendica_content(App $a) {
	$o = '<h1>Friendica</h1>' . PHP_EOL;
	$o .= '<p>';
	$o .= t('This is Friendica, version') . ' <strong>' . FRIENDICA_VERSION . '</strong> ';
	$o .= t('running at web location') . ' ' . z_root();
	$o .= '</p>' . PHP_EOL;

	$o .= '<p>';
	$o .= t('Please visit <a href="http://friendica.com">Friendica.com</a> to learn more about the Friendica project.') . PHP_EOL;
	$o .= '</p>' . PHP_EOL;

	$o .= '<p>';
	$o .= t('Bug reports and issues: please visit') . ' ' . '<a href="https://github.com/friendica/friendica/issues?state=open">'.t('the bugtracker at github').'</a>';
	$o .= '</p>' . PHP_EOL;
	$o .= '<p>';
	$o .= t('Suggestions, praise, donations, etc. - please email "Info" at Friendica - dot com');
	$o .= '</p>' . PHP_EOL;

	$visible_plugins = array();
	if (is_array($a->plugins) && count($a->plugins)) {
		$r = q("SELECT * FROM `addon` WHERE `hidden` = 0");
		if (dbm::is_result($r)) {
			foreach($r as $rr) {
				$visible_plugins[] = $rr['name'];
			}
		}
	}

	if (count($visible_plugins)) {
		$o .= '<p>' . t('Installed plugins/addons/apps:') . '</p>' . PHP_EOL;
		$sorted = $visible_plugins;
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
		$o .= '<div style="margin-left: 25px; margin-right: 25px;">' . $s . '</div>' . PHP_EOL;
	} else {
		$o .= '<p>' . t('No installed plugins/addons/apps') . '</p>' . PHP_EOL;
	}

	$blocklist = Config::get('system', 'blocklist');
	if (count($blocklist)) {
		$o .= '<div id="about_blocklist"><p>' . t('On this server the following remote servers are blocked.') . '</p>' . PHP_EOL;
		$o .= '<table class="table"><thead><tr><th>' . t('Blocked domain') . '</th><th>' . t('Reason for the block') . '</th></thead><tbody>' . PHP_EOL;
		foreach ($blocklist as $b) {
			$o .= '<tr><td>' . $b['domain'] .'</td><td>' . $b['reason'] . '</td></tr>' . PHP_EOL;
		}
		$o .= '</tbody></table></div>' . PHP_EOL;
	}

	call_hooks('about_hook', $o);

	return $o;
}
