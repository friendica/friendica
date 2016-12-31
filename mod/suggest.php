<?php

require_once('include/socgraph.php');
require_once('include/contact_widgets.php');


function suggest_init(&$a) {
	if (! local_user()) {
		return;
	}

	if (x($_GET,'ignore') && intval($_GET['ignore'])) {
		// Check if we should do HTML-based delete confirmation
		if ($_REQUEST['confirm']) {
			// <form> can't take arguments in its "action" parameter
			// so add any arguments as hidden inputs
			$query = explode_querystring($a->query_string);
			$inputs = array();
			foreach($query['args'] as $arg) {
				if(strpos($arg, 'confirm=') === false) {
					$arg_parts = explode('=', $arg);
					$inputs[] = array('name' => $arg_parts[0], 'value' => $arg_parts[1]);
				}
			}

			$a->page['content'] = replace_macros(get_markup_template('confirm.tpl'), array(
				'$method' => 'get',
				'$message' => t('Do you really want to delete this suggestion?'),
				'$extra_inputs' => $inputs,
				'$confirm' => t('Yes'),
				'$confirm_url' => $query['base'],
				'$confirm_name' => 'confirmed',
				'$cancel' => t('Cancel'),
			));
			$a->error = 1; // Set $a->error so the other module functions don't execute
			return;
		}
		// Now check how the user responded to the confirmation query
		if (!$_REQUEST['canceled']) {
			q("INSERT INTO `gcign` ( `uid`, `gcid` ) VALUES ( %d, %d ) ",
				intval(local_user()),
				intval($_GET['ignore'])
			);
		}
	}

}





function suggest_content(&$a) {

	require_once("mod/proxy.php");

	$o = '';
	if (! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$_SESSION['return_url'] = App::get_baseurl() . '/' . $a->cmd;

	$a->page['aside'] .= findpeople_widget();
	$a->page['aside'] .= follow_widget();


	$r = suggestion_query(local_user());

	if (! dbm::is_result($r)) {
		$o .= t('No suggestions available. If this is a new site, please try again in 24 hours.');
		return $o;
	}

	require_once 'include/contact_selectors.php';

	foreach ($r as $rr) {

		$connlnk = App::get_baseurl() . '/follow/?url=' . (($rr['connect']) ? $rr['connect'] : $rr['url']);
		$ignlnk = App::get_baseurl() . '/suggest?ignore=' . $rr['id'];
		$photo_menu = array(
			'profile' => array(t("View Profile"), zrl($rr["url"])),
			'follow' => array(t("Connect/Follow"), $connlnk),
			'hide' => array(t('Ignore/Hide'), $ignlnk)
		);

		$contact_details = get_contact_details_by_url($rr["url"], local_user(), $rr);

		$entry = array(
			'url' => zrl($rr['url']),
			'itemurl' => (($contact_details['addr'] != "") ? $contact_details['addr'] : $rr['url']),
			'img_hover' => $rr['url'],
			'name' => $contact_details['name'],
			'thumb' => proxy_url($contact_details['thumb'], false, PROXY_SIZE_THUMB),
			'details'       => $contact_details['location'],
			'tags'          => $contact_details['keywords'],
			'about'         => $contact_details['about'],
			'account_type'  => account_type($contact_details),
			'ignlnk' => $ignlnk,
			'ignid' => $rr['id'],
			'conntxt' => t('Connect'),
			'connlnk' => $connlnk,
			'photo_menu' => $photo_menu,
			'ignore' => t('Ignore/Hide'),
			'network' => network_to_name($rr['network'], $rr['url']),
			'id' => ++$id,
		);
		$entries[] = $entry;
	}

	$tpl = get_markup_template('viewcontact_template.tpl');

	$o .= replace_macros($tpl,array(
		'$title' => t('Friend Suggestions'),
		'$contacts' => $entries,
	));

	return $o;
}
