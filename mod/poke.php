<?php
/**
 * Poke, prod, finger, or otherwise do unspeakable things to somebody - who must be a connection in your address book
 * This function can be invoked with the required arguments (verb and cid and private and possibly parent) silently via ajax or
 * other web request. You must be logged in and connected to a profile.
 * If the required arguments aren't present, we'll display a simple form to choose a recipient and a verb.
 * parent is a special argument which let's you attach this activity as a comment to an existing conversation, which
 * may have started with somebody else poking (etc.) somebody, but this isn't necessary. This can be used in the more pokes
 * addon version to have entire conversations where Alice poked Bob, Bob fingered Alice, Alice hugged Bob, etc.
 *
 * private creates a private conversation with the recipient. Otherwise your profile's default post privacy is used.
 *
 * @file mod/poke.php
 */

use Friendica\App;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Protocol\Activity;
use Friendica\Registry\App as AppR;
use Friendica\Util\Strings;
use Friendica\Util\XML;

function poke_init(App $a)
{
	if (!local_user()) {
		return;
	}

	$uid = local_user();

	if (empty($_GET['verb'])) {
		return;
	}

	$verb = Strings::escapeTags(trim($_GET['verb']));

	$verbs = L10n::getPokeVerbs();

	if (!array_key_exists($verb, $verbs)) {
		return;
	}

	$activity = Activity::POKE . '#' . urlencode($verbs[$verb][0]);

	$contact_id = intval($_GET['cid']);
	if (!$contact_id) {
		return;
	}

	$parent = (!empty($_GET['parent']) ? intval($_GET['parent']) : 0);


	Logger::log('poke: verb ' . $verb . ' contact ' . $contact_id, Logger::DEBUG);


	$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($contact_id),
		intval($uid)
	);

	if (!DBA::isResult($r)) {
		Logger::log('poke: no contact ' . $contact_id);
		return;
	}

	$target = $r[0];

	if ($parent) {
		$fields = ['uri', 'private', 'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid'];
		$condition = ['id' => $parent, 'parent' => $parent, 'uid' => $uid];
		$item = Item::selectFirst($fields, $condition);

		if (DBA::isResult($item)) {
			$parent_uri = $item['uri'];
			$private    = $item['private'];
			$allow_cid  = $item['allow_cid'];
			$allow_gid  = $item['allow_gid'];
			$deny_cid   = $item['deny_cid'];
			$deny_gid   = $item['deny_gid'];
		}
	} else {
		$private = (!empty($_GET['private']) ? intval($_GET['private']) : 0);

		$allow_cid     = ($private ? '<' . $target['id']. '>' : $a->user['allow_cid']);
		$allow_gid     = ($private ? '' : $a->user['allow_gid']);
		$deny_cid      = ($private ? '' : $a->user['deny_cid']);
		$deny_gid      = ($private ? '' : $a->user['deny_gid']);
	}

	$poster = $a->contact;

	$uri = Item::newURI($uid);

	$arr = [];

	$arr['guid']          = System::createUUID();
	$arr['uid']           = $uid;
	$arr['uri']           = $uri;
	$arr['parent-uri']    = (!empty($parent_uri) ? $parent_uri : $uri);
	$arr['wall']          = 1;
	$arr['contact-id']    = $poster['id'];
	$arr['owner-name']    = $poster['name'];
	$arr['owner-link']    = $poster['url'];
	$arr['owner-avatar']  = $poster['thumb'];
	$arr['author-name']   = $poster['name'];
	$arr['author-link']   = $poster['url'];
	$arr['author-avatar'] = $poster['thumb'];
	$arr['title']         = '';
	$arr['allow_cid']     = $allow_cid;
	$arr['allow_gid']     = $allow_gid;
	$arr['deny_cid']      = $deny_cid;
	$arr['deny_gid']      = $deny_gid;
	$arr['visible']       = 1;
	$arr['verb']          = $activity;
	$arr['private']       = $private;
	$arr['object-type']   = Activity\ObjectType::PERSON;

	$arr['origin']        = 1;
	$arr['body']          = '[url=' . $poster['url'] . ']' . $poster['name'] . '[/url]' . ' ' . L10n::t($verbs[$verb][0]) . ' ' . '[url=' . $target['url'] . ']' . $target['name'] . '[/url]';

	$arr['object'] = '<object><type>' . Activity\ObjectType::PERSON . '</type><title>' . $target['name'] . '</title><id>' . $target['url'] . '</id>';
	$arr['object'] .= '<link>' . XML::escape('<link rel="alternate" type="text/html" href="' . $target['url'] . '" />' . "\n");

	$arr['object'] .= XML::escape('<link rel="photo" type="image/jpeg" href="' . $target['photo'] . '" />' . "\n");
	$arr['object'] .= '</link></object>' . "\n";

	Item::insert($arr);

	Hook::callAll('post_local_end', $arr);

	return;
}

function poke_content(App $a)
{
	if (!local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	if (empty($_GET['c'])) {
		return;
	}

	$contact = DBA::selectFirst('contact', ['id', 'name'], ['id' => $_GET['c'], 'uid' => local_user()]);
	if (!DBA::isResult($contact)) {
		return;
	}

	$name = $contact['name'];
	$id = $contact['id'];

	$head_tpl                    = Renderer::getMarkupTemplate('poke_head.tpl');
	AppR::page()['htmlhead'] .= Renderer::replaceMacros($head_tpl,[
		'$baseurl' => AppR::baseUrl()->get(true),
	]);

	$parent = (!empty($_GET['parent']) ? intval($_GET['parent']) : '0');


	$verbs = L10n::getPokeVerbs();

	$shortlist = [];
	foreach ($verbs as $k => $v) {
		if ($v[1] !== 'NOTRANSLATION') {
			$shortlist[] = [$k, $v[1]];
		}
	}

	$tpl = Renderer::getMarkupTemplate('poke_content.tpl');

	$o = Renderer::replaceMacros($tpl,[
		'$title' => L10n::t('Poke/Prod'),
		'$desc' => L10n::t('poke, prod or do other things to somebody'),
		'$clabel' => L10n::t('Recipient'),
		'$choice' => L10n::t('Choose what you wish to do to recipient'),
		'$verbs' => $shortlist,
		'$parent' => $parent,
		'$prv_desc' => L10n::t('Make this post private'),
		'$submit' => L10n::t('Submit'),
		'$name' => $name,
		'$id' => $id
	]);

	return $o;
}
