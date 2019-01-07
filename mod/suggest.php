<?php
/**
 * @file mod/suggest.php
 */

use Friendica\App;
use Friendica\Content\ContactSelector;
use Friendica\Content\Widget;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\GContact;
use Friendica\Util\Proxy as ProxyUtils;

function suggest_init(App $a)
{
	if (! local_user()) {
		return;
	}

	if (!empty($_GET['ignore'])) {
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

			$a->page['content'] = Renderer::replaceMacros(Renderer::getMarkupTemplate('confirm.tpl'), [
				'$method' => 'get',
				'$message' => L10n::t('Do you really want to delete this suggestion?'),
				'$extra_inputs' => $inputs,
				'$confirm' => L10n::t('Yes'),
				'$confirm_url' => $query['base'],
				'$confirm_name' => 'confirmed',
				'$cancel' => L10n::t('Cancel'),
			]);
			$a->error = 1; // Set $a->error so the other module functions don't execute
			return;
		}
		// Now check how the user responded to the confirmation query
		if (!$_REQUEST['canceled']) {
			DBA::insert('gcign', ['uid' => local_user(), 'gcid' => $_GET['ignore']]);
		}
	}

}

function suggest_content(App $a)
{
	$o = '';

	if (! local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	$_SESSION['return_path'] = $a->cmd;

	$a->page['aside'] .= Widget::findPeople();
	$a->page['aside'] .= Widget::follow();


	$r = GContact::suggestionQuery(local_user());

	if (! DBA::isResult($r)) {
		$o .= L10n::t('No suggestions available. If this is a new site, please try again in 24 hours.');
		return $o;
	}

	$id = 0;
	$entries = [];

	foreach ($r as $rr) {

		$connlnk = System::baseUrl() . '/follow/?url=' . (($rr['connect']) ? $rr['connect'] : $rr['url']);
		$ignlnk = System::baseUrl() . '/suggest?ignore=' . $rr['id'];
		$photo_menu = [
			'profile' => [L10n::t("View Profile"), Contact::magicLink($rr["url"])],
			'follow' => [L10n::t("Connect/Follow"), $connlnk],
			'hide' => [L10n::t('Ignore/Hide'), $ignlnk]
		];

		$contact_details = Contact::getDetailsByURL($rr["url"], local_user(), $rr);

		$entry = [
			'url' => Contact::magicLink($rr['url']),
			'itemurl' => (($contact_details['addr'] != "") ? $contact_details['addr'] : $rr['url']),
			'img_hover' => $rr['url'],
			'name' => $contact_details['name'],
			'thumb' => ProxyUtils::proxifyUrl($contact_details['thumb'], false, ProxyUtils::SIZE_THUMB),
			'details'       => $contact_details['location'],
			'tags'          => $contact_details['keywords'],
			'about'         => $contact_details['about'],
			'account_type'  => Contact::getAccountType($contact_details),
			'ignlnk' => $ignlnk,
			'ignid' => $rr['id'],
			'conntxt' => L10n::t('Connect'),
			'connlnk' => $connlnk,
			'photo_menu' => $photo_menu,
			'ignore' => L10n::t('Ignore/Hide'),
			'network' => ContactSelector::networkToName($rr['network'], $rr['url']),
			'id' => ++$id,
		];
		$entries[] = $entry;
	}

	$tpl = Renderer::getMarkupTemplate('viewcontact_template.tpl');

	$o .= Renderer::replaceMacros($tpl,[
		'$title' => L10n::t('Friend Suggestions'),
		'$contacts' => $entries,
	]);

	return $o;
}
