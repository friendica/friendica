<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Widget;
use Friendica\Core\Renderer;
use Friendica\Core\Search;
use Friendica\DI;
use Friendica\Event\ArrayFilterEvent;
use Friendica\Model;
use Friendica\Model\Profile;
use Friendica\Network\HTTPException;
use Friendica\Security\OpenWebAuth;

/**
 * Shows the local directory of this node
 */
class Directory extends BaseModule
{
	protected function content(array $request = []): string
	{
		$config = DI::config();

		if (($config->get('system', 'block_public') && !DI::userSession()->isAuthenticated()) ||
			($config->get('system', 'block_local_dir') && !DI::userSession()->isAuthenticated())) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Public access denied.'));
		}

		if (DI::userSession()->getLocalUserId()) {
			DI::page()['aside'] .= Widget::findPeople();
			DI::page()['aside'] .= Widget::follow();
		}

		$output  = '';
		$entries = [];

		Nav::setSelected('directory');

		$search = trim(rawurldecode($_REQUEST['search'] ?? ''));

		$gDirPath = '';
		$dirURL   = Search::getGlobalDirectory();
		if (strlen($dirURL)) {
			$gDirPath = OpenWebAuth::getZrlUrl($dirURL, true);
		}

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), 60);

		$profiles = Profile::searchProfiles($pager->getStart(), $pager->getItemsPerPage(), $search);

		if ($profiles['total'] === 0) {
			DI::sysmsg()->addNotice(DI::l10n()->t('No entries (some entries may be hidden).'));
		} else {
			foreach ($profiles['entries'] as $entry) {
				$contact = Model\Contact::getByURLForUser($entry['url'], DI::userSession()->getLocalUserId());
				if (!empty($contact)) {
					$entries[] = Contact::getContactTemplateVars($contact);
				}
			}
		}

		$tpl = Renderer::getMarkupTemplate('directory_header.tpl');

		$output .= Renderer::replaceMacros($tpl, [
			'$search'     => $search,
			'$globaldir'  => DI::l10n()->t('Global Directory'),
			'$gDirPath'   => $gDirPath,
			'$desc'       => DI::l10n()->t('Find on this site'),
			'$contacts'   => $entries,
			'$finding'    => DI::l10n()->t('Results for:'),
			'$findterm'   => (strlen($search) ? $search : ""),
			'$title'      => DI::l10n()->t('Site Directory'),
			'$search_mod' => 'directory',
			'$submit'     => DI::l10n()->t('Find'),
			'$paginate'   => $pager->renderFull($profiles['total']),
		]);

		return $output;
	}

	/**
	 * Format contact/profile/user data from the database into an usable
	 * array for displaying directory entries.
	 *
	 * @param array  $contact    The directory entry from the database.
	 * @param string $photo_size Avatar size (thumb, photo or micro).
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
	public static function formatEntry(array $contact, string $photo_size = 'photo'): array
	{
		$itemurl = (($contact['addr'] != "") ? $contact['addr'] : $contact['url']);

		$profile_link = $contact['url'];

		$about = (($contact['about']) ? $contact['about'] . '<br />' : '');

		$details = '';
		if (strlen($contact['locality'])) {
			$details .= $contact['locality'];
		}
		if (strlen($contact['region'])) {
			if (strlen($contact['locality'])) {
				$details .= ', ';
			}
			$details .= $contact['region'];
		}
		if (strlen($contact['country-name'])) {
			if (strlen($details)) {
				$details .= ', ';
			}
			$details .= $contact['country-name'];
		}

		$profile = $contact;

		if (!empty($profile['address'])
			|| !empty($profile['locality'])
			|| !empty($profile['region'])
			|| !empty($profile['postal-code'])
			|| !empty($profile['country-name'])
		) {
			$location = DI::l10n()->t('Location:');
		} else {
			$location = '';
		}

		$homepage = (!empty($profile['homepage']) ? DI::l10n()->t('Homepage:') : false);

		$location_e = $location;

		$photo_menu = [
			'profile' => [DI::l10n()->t("View Profile"), Model\Contact::magicLink($profile_link)]
		];

		$entry = [
			'id'           => $contact['id'],
			'url'          => Model\Contact::magicLink($profile_link),
			'itemurl'      => $itemurl,
			'thumb'        => Model\Contact::getThumb($contact),
			'img_hover'    => $contact['name'],
			'name'         => $contact['name'],
			'details'      => $details,
			'account_type' => Model\Contact::getAccountType($contact['contact-type'] ?? 0),
			'profile'      => $profile,
			'location'     => $location_e,
			'tags'         => $contact['pub_keywords'],
			'about'        => $about,
			'homepage'     => $homepage,
			'photo_menu'   => $photo_menu,

		];

		$eventDispatcher = DI::eventDispatcher();

		$hook_data = [
			'contact' => $contact,
			'entry'   => $entry,
		];

		$hook_data = $eventDispatcher->dispatch(
			new ArrayFilterEvent(ArrayFilterEvent::DIRECTORY_ITEM, $hook_data),
		)->getArray();

		$entry = $hook_data['entry'] ?? $entry;

		unset($profile);
		unset($location);

		return $entry;
	}
}
