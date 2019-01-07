<?php

/**
 * Name: Frio Hovercard
 * Description: Hovercard addon for the frio theme
 * Version: 0.1
 * Author: Rabuzarus <https://github.com/rabuzarus>
 * License: GNU AFFERO GENERAL PUBLIC LICENSE (Version 3)
 */

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\GContact;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Util\Strings;

function hovercard_init(App $a)
{
	// Just for testing purposes
	$_GET['mode'] = 'minimal';
}

function hovercard_content()
{
	$profileurl = defaults($_REQUEST, 'profileurl', '');
	$datatype   = defaults($_REQUEST, 'datatype'  , 'json');

	// Get out if the system doesn't have public access allowed
	if (intval(Config::get('system', 'block_public'))) {
		System::httpExit(401);
	}

	// Return the raw content of the template. We use this to make templates usable for js functions.
	// Look at hovercard.js (function getHoverCardTemplate()).
	// This part should be moved in its own module. Maybe we could make more templates accessible.
	// (We need to discuss possible security leaks before doing this)
	if ($datatype == 'tpl') {
		$templatecontent = get_template_content('hovercard.tpl');
		echo $templatecontent;
		exit();
	}

	// If a contact is connected the url is internally changed to 'redir/CID'. We need the pure url to search for
	// the contact. So we strip out the contact id from the internal url and look in the contact table for
	// the real url (nurl)
	if (strpos($profileurl, 'redir/') === 0) {
		$cid = intval(substr($profileurl, 6));
		$remote_contact = DBA::selectFirst('contact', ['nurl'], ['id' => $cid]);
		$profileurl = defaults($remote_contact, 'nurl', '');
	}

	$contact = [];
	// if it's the url containing https it should be converted to http
	$nurl = Strings::normaliseLink(GContact::cleanContactUrl($profileurl));
	if (!$nurl) {
		return;
	}

	// Search for contact data
	// Look if the local user has got the contact
	if (local_user()) {
		$contact = Contact::getDetailsByURL($nurl, local_user());
	}

	// If not then check the global user
	if (!count($contact)) {
		$contact = Contact::getDetailsByURL($nurl);
	}

	// Feeds url could have been destroyed through "cleanContactUrl", so we now use the original url
	if (!count($contact) && local_user()) {
		$nurl = Strings::normaliseLink($profileurl);
		$contact = Contact::getDetailsByURL($nurl, local_user());
	}

	if (!count($contact)) {
		$nurl = Strings::normaliseLink($profileurl);
		$contact = Contact::getDetailsByURL($nurl);
	}

	if (!count($contact)) {
		return;
	}

	// Get the photo_menu - the menu if possible contact actions
	if (local_user()) {
		$actions = Contact::photoMenu($contact);
	} else {
		$actions = [];
	}

	// Move the contact data to the profile array so we can deliver it to
	$profile = [
		'name'     => $contact['name'],
		'nick'     => $contact['nick'],
		'addr'     => defaults($contact, 'addr', $contact['url']),
		'thumb'    => ProxyUtils::proxifyUrl($contact['thumb'], false, ProxyUtils::SIZE_THUMB),
		'url'      => Contact::magicLink($contact['url']),
		'nurl'     => $contact['nurl'], // We additionally store the nurl as identifier
		'location' => $contact['location'],
		'gender'   => $contact['gender'],
		'about'    => $contact['about'],
		'network'  => Strings::formatNetworkName($contact['network'], $contact['url']),
		'tags'     => $contact['keywords'],
		'bd'       => $contact['birthday'] <= DBA::NULL_DATE ? '' : $contact['birthday'],
		'account_type' => Contact::getAccountType($contact),
		'actions'  => $actions,
	];
	if ($datatype == 'html') {
		$tpl = Renderer::getMarkupTemplate('hovercard.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$profile' => $profile,
		]);

		return $o;
	} else {
		System::jsonExit($profile);
	}
}

/**
 * @brief Get the raw content of a template file
 *
 * @param string $template The name of the template
 * @param string $root     Directory of the template
 *
 * @return string|bool Output the raw content if existent, otherwise false
 * @throws Exception
 */
function get_template_content($template, $root = '')
{
	// We load the whole template system to get the filename.
	// Maybe we can do it a little bit smarter if I get time.
	$templateEngine = Renderer::getTemplateEngine();
	$template = $templateEngine->getTemplateFile($template, $root);

	$filename = $template->filename;

	// Get the content of the template file
	if (file_exists($filename)) {
		$content = file_get_contents($filename);

		return $content;
	}

	return false;
}
