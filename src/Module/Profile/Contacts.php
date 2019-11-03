<?php

namespace Friendica\Module\Profile;

use Friendica\BaseModule;
use Friendica\Content\ContactSelector;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Core\Config;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Util\Proxy as ProxyUtils;

class Contacts extends BaseModule
{
	public static function content(array $parameters = [])
	{
		if (Config::get('system', 'block_public') && !Session::isAuthenticated()) {
			throw new \Friendica\Network\HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
		}

		$a = DI::app();

		//@TODO: Get value from router parameters
		$nickname = $a->argv[1];
		$type = ($a->argv[3] ?? '') ?: 'all';

		Nav::setSelected('home');

		$user = DBA::selectFirst('user', [], ['nickname' => $nickname, 'blocked' => false]);
		if (!DBA::isResult($user)) {
			throw new \Friendica\Network\HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
		}

		$a->profile_uid  = $user['uid'];

		Profile::load($a, $nickname);

		$is_owner = $a->profile['uid'] == local_user();

		// tabs
		$o = Profile::getTabs($a, 'contacts', $is_owner, $nickname);

		if (!count($a->profile) || $a->profile['hide-friends']) {
			notice(DI::l10n()->t('Permission denied.') . EOL);
			return $o;
		}

		$condition = [
			'uid'     => $a->profile['uid'],
			'blocked' => false,
			'pending' => false,
			'hidden'  => false,
			'archive' => false,
			'network' => [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, Protocol::FEED]
		];

		switch ($type) {
			case 'followers': $condition['rel'] = [1, 3]; break;
			case 'following': $condition['rel'] = [2, 3]; break;
			case 'mutuals': $condition['rel'] = 3; break;
		}

		$total = DBA::count('contact', $condition);

		$pager = new Pager(DI::args()->getQueryString());

		$params = ['order' => ['name' => false], 'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];

		$contacts_stmt = DBA::select('contact', [], $condition, $params);

		if (!DBA::isResult($contacts_stmt)) {
			info(DI::l10n()->t('No contacts.') . EOL);
			return $o;
		}

		$contacts = [];

		while ($contact = DBA::fetch($contacts_stmt)) {
			if ($contact['self']) {
				continue;
			}

			$contact_details = Contact::getDetailsByURL($contact['url'], $a->profile['uid'], $contact);

			$contacts[] = [
				'id'           => $contact['id'],
				'img_hover'    => DI::l10n()->t('Visit %s\'s profile [%s]', $contact_details['name'], $contact['url']),
				'photo_menu'   => Contact::photoMenu($contact),
				'thumb'        => ProxyUtils::proxifyUrl($contact_details['thumb'], false, ProxyUtils::SIZE_THUMB),
				'name'         => substr($contact_details['name'], 0, 20),
				'username'     => $contact_details['name'],
				'details'      => $contact_details['location'],
				'tags'         => $contact_details['keywords'],
				'about'        => $contact_details['about'],
				'account_type' => Contact::getAccountType($contact_details),
				'url'          => Contact::magicLink($contact['url']),
				'sparkle'      => '',
				'itemurl'      => $contact_details['addr'] ? : $contact['url'],
				'network'      => ContactSelector::networkToName($contact['network'], $contact['url'], $contact['protocol']),
			];
		}

		DBA::close($contacts_stmt);

		switch ($type) {
			case 'followers':    $title = DI::l10n()->tt('Follower (%s)', 'Followers (%s)', $total); break;
			case 'following':    $title = DI::l10n()->tt('Following (%s)', 'Following (%s)', $total); break;
			case 'mutuals':      $title = DI::l10n()->tt('Mutual friend (%s)', 'Mutual friends (%s)', $total); break;

			case 'all': default: $title = DI::l10n()->tt('Contact (%s)', 'Contacts (%s)', $total); break;
		}

		$tpl = Renderer::getMarkupTemplate('profile/contacts.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$title'    => $title,
			'$nickname' => $nickname,
			'$type'     => $type,

			'$all_label' => DI::l10n()->t('All contacts'),
			'$followers_label' => DI::l10n()->t('Followers'),
			'$following_label' => DI::l10n()->t('Following'),
			'$mutuals_label' => DI::l10n()->t('Mutual friends'),

			'$contacts' => $contacts,
			'$paginate' => $pager->renderFull($total),
		]);

		return $o;
	}
}
