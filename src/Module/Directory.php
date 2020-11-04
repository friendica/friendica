<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Widget;
use Friendica\Core\Hook;
use Friendica\Core\Session;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model;
use Friendica\Model\Profile;
use Friendica\Network\HTTPException;
use Friendica\Util\Strings;

/**
 * Shows the local directory of this node
 */
class Directory extends BaseModule
{
	public static function content(array $parameters = [])
	{
		$app = DI::app();
		$config = DI::config();

		if (($config->get('system', 'block_public') && !Session::isAuthenticated()) ||
			($config->get('system', 'block_local_dir') && !Session::isAuthenticated())) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Public access denied.'));
		}

		if (local_user()) {
			DI::page()['aside'] .= Widget::findPeople();
			DI::page()['aside'] .= Widget::follow();
		}

		$output = '';
		$entries = [];

		Nav::setSelected('directory');

		$search = (!empty($_REQUEST['search']) ?
			Strings::escapeTags(trim(rawurldecode($_REQUEST['search']))) :
			'');

		$gDirPath = '';
		$dirURL = $config->get('system', 'directory');
		if (strlen($dirURL)) {
			$gDirPath = Profile::zrl($dirURL, true);
		}

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), 60);

		$profiles = Profile::searchProfiles($pager->getStart(), $pager->getItemsPerPage(), $search);

		if ($profiles['total'] === 0) {
			notice(DI::l10n()->t('No entries (some entries may be hidden).'));
		} else {
			if (in_array('small', $app->argv)) {
				$photo = 'thumb';
			} else {
				$photo = 'photo';
			}

			foreach ($profiles['entries'] as $entry) {
				$contact = Model\Contact::getByURLForUser($entry['url'], local_user());
				if (!empty($contact)) {
					$entries[] = Model\Contact::getContactTemplateVars($contact);
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
	public static function formatEntry(array $contact, $photo_size = 'photo')
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
			'account_type' => Model\Contact::getAccountType($contact),
			'profile'      => $profile,
			'location'     => $location_e,
			'tags'         => $contact['pub_keywords'],
			'about'        => $about,
			'homepage'     => $homepage,
			'photo_menu'   => $photo_menu,

		];

		$hook = ['contact' => $contact, 'entry' => $entry];

		Hook::callAll('directory_item', $hook);

		unset($profile);
		unset($location);

		return $hook['entry'];
	}
}
