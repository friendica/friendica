<?php

function friendica_init(&$a) {
	if ($a->argv[1]=="json"){
		$register_policy = Array('REGISTER_CLOSED', 'REGISTER_APPROVE', 'REGISTER_OPEN');

		$sql_extra = '';
		if(x($a->config,'admin_nickname')) {
			$sql_extra = sprintf(" AND nickname = '%s' ",dbesc($a->config['admin_nickname']));
		}
		if (isset($a->config['admin_email']) && $a->config['admin_email']!=''){
	                $adminlist = explode(",", str_replace(" ", "", $a->config['admin_email']));

			//$r = q("SELECT username, nickname FROM user WHERE email='%s' $sql_extra", dbesc($a->config['admin_email']));
			$r = q("SELECT username, nickname FROM user WHERE email='%s' $sql_extra", dbesc($adminlist[0]));
			$admin = array(
				'name' => $r[0]['username'],
				'profile'=> App::get_baseurl().'/profile/'.$r[0]['nickname'],
			);
		} else {
			$admin = false;
		}

		$visible_plugins = array();
		if(is_array($a->plugins) && count($a->plugins)) {
			$r = q("select * from addon where hidden = 0");
			if (dbm::is_result($r))
				foreach($r as $rr)
					$visible_plugins[] = $rr['name'];
		}

		load_config('feature_lock');
		$locked_features = array();
		if(is_array($a->config['feature_lock']) && count($a->config['feature_lock'])) {
			foreach($a->config['feature_lock'] as $k => $v) {
				if($k === 'config_loaded')
					continue;
				$locked_features[$k] = intval($v);
			}
		}

		$data = Array(
			'version' => FRIENDICA_VERSION,
			'url' => z_root(),
			'plugins' => $visible_plugins,
			'locked_features' => $locked_features,
			'register_policy' =>  $register_policy[$a->config['register_policy']],
			'admin' => $admin,
			'site_name' => $a->config['sitename'],
			'platform' => FRIENDICA_PLATFORM,
			'info' => ((x($a->config,'info')) ? $a->config['info'] : ''),
			'no_scrape_url' => App::get_baseurl().'/noscrape'
		);

		echo json_encode($data);
		killme();
	}
}



function friendica_content(&$a) {

	$o = '';
	$o .= '<h3>Friendica</h3>';


	$o .= '<p></p><p>';

	$o .= t('This is Friendica, version') . ' ' . FRIENDICA_VERSION . ' ';
	$o .= t('running at web location') . ' ' . z_root() . '</p><p>';

	$o .= t('Please visit <a href="http://friendica.com">Friendica.com</a> to learn more about the Friendica project.') . '</p><p>';	

	$o .= t('Bug reports and issues: please visit') . ' ' . '<a href="https://github.com/friendica/friendica/issues?state=open">'.t('the bugtracker at github').'</a></p><p>';
	$o .= t('Suggestions, praise, donations, etc. - please email "Info" at Friendica - dot com') . '</p>';

	$o .= '<p></p>';

	$visible_plugins = array();
	if(is_array($a->plugins) && count($a->plugins)) {
		$r = q("select * from addon where hidden = 0");
		if (dbm::is_result($r))
			foreach($r as $rr)
				$visible_plugins[] = $rr['name'];
	}


	if(count($visible_plugins)) {
		$o .= '<p>' . t('Installed plugins/addons/apps:') . '</p>';
		$sorted = $visible_plugins;
		$s = '';
		sort($sorted);
		foreach($sorted as $p) {
			if(strlen($p)) {
				if(strlen($s)) $s .= ', ';
				$s .= $p;
			}
		}
		$o .= '<div style="margin-left: 25px; margin-right: 25px;">' . $s . '</div>';
	}
	else
		$o .= '<p>' . t('No installed plugins/addons/apps') . '</p>';

	call_hooks('about_hook', $o); 	

	return $o;

}
