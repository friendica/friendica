<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content\ContactSelector;
use Friendica\Content\Pager;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model;
use Friendica\Network\HTTPException;
use Friendica\Util\Proxy as ProxyUtils;

/**
 * This module shows all public friends of the selected contact
 */
class AllFriends extends BaseModule
{
	public static function content(array $parameters = [])
	{
		$app = DI::app();

		if (!local_user()) {
			throw new HTTPException\ForbiddenException();
		}

		$cid = 0;

		// @TODO: Replace with parameter from router
		if ($app->argc > 1) {
			$cid = intval($app->argv[1]);
		}

		if (!$cid) {
			throw new HTTPException\BadRequestException(L10n::t('Invalid contact.'));
		}

		$uid = $app->user['uid'];

		$contact = Model\Contact::getContactForUser($cid, local_user(), ['name', 'url', 'photo', 'uid', 'id']);

		if (empty($contact)) {
			throw new HTTPException\BadRequestException(L10n::t('Invalid contact.'));
		}

		$app->page['aside'] = "";
		Model\Profile::load($app, "", 0, Model\Contact::getDetailsByURL($contact["url"]));

		$total = Model\GContact::countAllFriends(local_user(), $cid);

		$pager = new Pager($app->query_string);

		$friends = Model\GContact::allFriends(local_user(), $cid, $pager->getStart(), $pager->getItemsPerPage());
		if (empty($friends)) {
			return L10n::t('No friends to display.');
		}

		$id = 0;

		$entries = [];
		foreach ($friends as $friend) {
			//get further details of the contact
			$contactDetails = Model\Contact::getDetailsByURL($friend['url'], $uid, $friend);

			$connlnk = '';
			// $friend[cid] is only available for common contacts. So if the contact is a common one, use contact_photo_menu to generate the photoMenu
			// If the contact is not common to the user, Connect/Follow' will be added to the photo menu
			if ($friend['cid']) {
				$friend['id'] = $friend['cid'];
				$photoMenu = Model\Contact::photoMenu($friend);
			} else {
				$connlnk = DI::baseUrl()->get() . '/follow/?url=' . $friend['url'];
				$photoMenu = [
					'profile' => [L10n::t('View Profile'), Model\Contact::magicLinkbyId($friend['id'], $friend['url'])],
					'follow'  => [L10n::t('Connect/Follow'), $connlnk]
				];
			}

			$entry = [
				'url'          => Model\Contact::magicLinkbyId($friend['id'], $friend['url']),
				'itemurl'      => ($contactDetails['addr'] ?? '') ?: $friend['url'],
				'name'         => $contactDetails['name'],
				'thumb'        => ProxyUtils::proxifyUrl($contactDetails['thumb'], false, ProxyUtils::SIZE_THUMB),
				'img_hover'    => $contactDetails['name'],
				'details'      => $contactDetails['location'],
				'tags'         => $contactDetails['keywords'],
				'about'        => $contactDetails['about'],
				'account_type' => Model\Contact::getAccountType($contactDetails),
				'network'      => ContactSelector::networkToName($contactDetails['network'], $contactDetails['url']),
				'photoMenu'    => $photoMenu,
				'conntxt'      => L10n::t('Connect'),
				'connlnk'      => $connlnk,
				'id'           => ++$id,
			];
			$entries[] = $entry;
		}

		$tab_str = Contact::getTabsHTML($app, $contact, 4);

		$tpl = Renderer::getMarkupTemplate('viewcontact_template.tpl');
		return Renderer::replaceMacros($tpl, [
			'$tab_str'  => $tab_str,
			'$contacts' => $entries,
			'$paginate' => $pager->renderFull($total),
		]);
	}
}
