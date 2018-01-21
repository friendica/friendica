<?php
/**
 * @file mod/suggest.php
 */
use Friendica\App;
use Friendica\Content\ContactSelector;
use Friendica\Content\Widget;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\GContact;
use Friendica\Model\Profile;

function suggest_init(App $a) {
	if (! local_user()) {
		return;
	}

	if (x($_GET,'ignore') && intval($_GET['ignore'])) {
		// Check if we should do HTML-based delete confirmation
		if ($_REQUEST['confirm']) {
			// <form> can't take arguments in its "action" parameter
			// so add any arguments as hidden inputs
			$query = explode_querystring($a->query_string);
			$inputs = [];
			foreach ($query['args'] as $arg) {
				if (strpos($arg, 'confirm=') === false) {
					$arg_parts = explode('=', $arg);
					$inputs[] = ['name' => $arg_parts[0], 'value' => $arg_parts[1]];
				}
			}

			$a->page['content'] = replace_macros(get_markup_template('confirm.tpl'), [
				'$method' => 'get',
				'$message' => t('Do you really want to delete this suggestion?'),
				'$extra_inputs' => $inputs,
				'$confirm' => t('Yes'),
				'$confirm_url' => $query['base'],
				'$confirm_name' => 'confirmed',
				'$cancel' => t('Cancel'),
			]);
			$a->error = 1; // Set $a->error so the other module functions don't execute
			return;
		}
		// Now check how the user responded to the confirmation query
		if (!$_REQUEST['canceled']) {
			dba::insert('gcign', ['uid' => local_user(), 'gcid' => $_GET['ignore']]);
		}
	}

}

function suggest_content(App $a) {

	require_once("mod/proxy.php");

	$o = '';
	if (! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$_SESSION['return_url'] = System::baseUrl() . '/' . $a->cmd;

	$a->page['aside'] .= Widget::findPeople();
	$a->page['aside'] .= Widget::follow();


	$r = GContact::suggestionQuery(local_user());

	if (! DBM::isResult($r)) {
		$o .= t('No suggestions available. If this is a new site, please try again in 24 hours.');
		return $o;
	}

	foreach ($r as $rr) {

		$connlnk = System::baseUrl() . '/follow/?url=' . (($rr['connect']) ? $rr['connect'] : $rr['url']);
		$ignlnk = System::baseUrl() . '/suggest?ignore=' . $rr['id'];
		$photo_menu = [
			'profile' => [t("View Profile"), Profile::zrl($rr["url"])],
			'follow' => [t("Connect/Follow"), $connlnk],
			'hide' => [t('Ignore/Hide'), $ignlnk]
		];

		$contact_details = Contact::getDetailsByURL($rr["url"], local_user(), $rr);

		$entry = [
			'url' => Profile::zrl($rr['url']),
			'itemurl' => (($contact_details['addr'] != "") ? $contact_details['addr'] : $rr['url']),
			'img_hover' => $rr['url'],
			'name' => $contact_details['name'],
			'thumb' => proxy_url($contact_details['thumb'], false, PROXY_SIZE_THUMB),
			'details'       => $contact_details['location'],
			'tags'          => $contact_details['keywords'],
			'about'         => $contact_details['about'],
			'account_type'  => Contact::getAccountType($contact_details),
			'ignlnk' => $ignlnk,
			'ignid' => $rr['id'],
			'conntxt' => t('Connect'),
			'connlnk' => $connlnk,
			'photo_menu' => $photo_menu,
			'ignore' => t('Ignore/Hide'),
			'network' => ContactSelector::networkToName($rr['network'], $rr['url']),
			'id' => ++$id,
		];
		$entries[] = $entry;
	}

	$tpl = get_markup_template('viewcontact_template.tpl');

	$o .= replace_macros($tpl,[
		'$title' => t('Friend Suggestions'),
		'$contacts' => $entries,
	]);

	return $o;
}
