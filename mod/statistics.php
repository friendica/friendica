<?php
$json = file_get_contents($a->get_baseurl(true).'/statistics.json');

$jsonarray = json_decode($json, TRUE);

function verifjsonval($val, $valopen, $valclose)
{
	if(empty($val))
	{
		switch ($val)
		{
			case TRUE:
				return $valopen;
				break;
			case FALSE:
				return $valclose;
				break;
		}
	}
	else
	{
		switch ($val)
		{
			case 1:
				return $valopen;
				break;
			default:
				return $val;
				break;
		}
	}
}

$valopen = $a->strings["Open"];
$valclose = $a->strings["Close"];

$head_tpl = get_markup_template('statistics.tpl');
	$a->page['htmlhead'] .= replace_macros($head_tpl,array(
		'$baseurl' => $a->get_baseurl(true),
		'$base' => $base,
		'$name' => verifjsonval($jsonarray['name'], $valopen, $valclose),
		'$network' => verifjsonval($jsonarray['network'], $valopen, $valclose),
		'$version' => verifjsonval($jsonarray['version'], $valopen, $valclose),
		'$registrations_open' => verifjsonval($jsonarray['registrations_open'], $valopen, $valclose),
		'$total_users' => verifjsonval($jsonarray['total_users'], $valopen, $valclose),
		'$active_users_halfyear' => verifjsonval($jsonarray['active_users_halfyear'], $valopen, $valclose),
		'$active_users_monthly' => verifjsonval($jsonarray['active_users_monthly'], $valopen, $valclose),
		'$local_posts' => verifjsonval($jsonarray['local_posts'], $valopen, $valclose),
		'$appnet' => verifjsonval($jsonarray['appnet'], $valopen, $valclose),
		'$blogger' => verifjsonval($jsonarray['blogger'], $valopen, $valclose),
		'$buffer' => verifjsonval($jsonarray['buffer'], $valopen, $valclose),
		'$dreamwidth' => verifjsonval($jsonarray['dreamwidth'], $valopen, $valclose),
		'$facebook' => verifjsonval($jsonarray['facebook'], $valopen, $valclose),
		'$gnusocial' => verifjsonval($jsonarray['gnusocial'], $valopen, $valclose),
		'$googleplus' => verifjsonval($jsonarray['googleplus'], $valopen, $valclose),
		'$libertree' => verifjsonval($jsonarray['libertree'], $valopen, $valclose),
		'$livejournal' => verifjsonval($jsonarray['livejournal'], $valopen, $valclose),
		'$pumpio' => verifjsonval($jsonarray['pumpio'], $valopen, $valclose),
		'$twitter' => verifjsonval($jsonarray['twitter'], $valopen, $valclose),
		'$tumblr' => verifjsonval($jsonarray['tumblr'], $valopen, $valclose),
		'$wordpress' => verifjsonval($jsonarray['wordpress'], $valopen, $valclose),
		
		'$n_name' => $a->strings["Name:"],
		'$n_network' => $a->strings["Network"],
		'$n_version' => $a->strings["Version"],
		'$n_registrations_open' => $a->strings["Registration"].' '.$valopen,
		'$n_total_users' => $a->strings["Users"],
		'$n_active_users_halfyear' => $a->strings["Users"].'/halfyear',
		'$n_active_users_monthly' => $a->strings["Users"].'/monthly',
		'$n_local_posts' => $a->strings["Site Directory"],
		'$n_appnet' => 'Appnet',
		'$n_blogger' => 'Blogger',
		'$n_buffer' => 'Buffer',
		'$n_dreamwidth' => 'Dreamwidth',
		'$n_facebook' => 'Facebook',
		'$n_gnusocial' => 'GNUSocial',
		'$n_googleplus' => 'Google+',
		'$n_libertree' => 'Libertree',
		'$n_livejournal' => 'Livejournal',
		'$n_pumpio' => 'Pumpio',
		'$n_twitter' => 'Twitter',
		'$n_tumblr' => 'Tumblr',
		'$n_wordpress' => 'Wordpress',
	));