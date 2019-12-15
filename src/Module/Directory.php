<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Widget;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Session;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Network\HTTPException;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Util\Strings;

/**
 * Shows the local directory of this node
 */
class Directory extends BaseModule
{
	public static function content(array $parameters = [])
	{
		$app = DI::app();
		$config = $app->getConfig();

		if (($config->get('system', 'block_public') && !Session::isAuthenticated()) ||
			($config->get('system', 'block_local_dir') && !Session::isAuthenticated())) {
			throw new HTTPException\ForbiddenException(L10n::t('Public access denied.'));
		}

		if (local_user()) {
			$app->page['aside'] .= Widget::findPeople();
			$app->page['aside'] .= Widget::follow();
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

		$pager = new Pager($app->query_string, 60);

		$profiles = Profile::searchProfiles($pager->getStart(), $pager->getItemsPerPage(), $search);

		if ($profiles['total'] === 0) {
			info(L10n::t('No entries (some entries may be hidden).') . EOL);
		} else {
			if (in_array('small', $app->argv)) {
				$photo = 'thumb';
			} else {
				$photo = 'photo';
			}

			foreach ($profiles['entries'] as $entry) {
				$entries[] = self::formatEntry($entry, $photo);
			}
		}

		$tpl = Renderer::getMarkupTemplate('directory_header.tpl');

		$output .= Renderer::replaceMacros($tpl, [
			'$search'     => $search,
			'$globaldir'  => L10n::t('Global Directory'),
			'$gDirPath'   => $gDirPath,
			'$desc'       => L10n::t('Find on this site'),
			'$contacts'   => $entries,
			'$finding'    => L10n::t('Results for:'),
			'$findterm'   => (strlen($search) ? $search : ""),
			'$title'      => L10n::t('Site Directory'),
			'$search_mod' => 'directory',
			'$submit'     => L10n::t('Find'),
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
		$itemurl = (($contact['addr'] != "") ? $contact['addr'] : $contact['profile_url']);

		$profile_link = $contact['profile_url'];

		$pdesc = (($contact['pdesc']) ? $contact['pdesc'] . '<br />' : '');

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
			$location = L10n::t('Location:');
		} else {
			$location = '';
		}

		$gender =   (!empty($profile['gender'])   ? L10n::t('Gender:')   : false);
		$marital =  (!empty($profile['marital'])  ? L10n::t('Status:')   : false);
		$homepage = (!empty($profile['homepage']) ? L10n::t('Homepage:') : false);
		$about =    (!empty($profile['about'])    ? L10n::t('About:')    : false);

		$location_e = $location;

		$photo_menu = [
			'profile' => [L10n::t("View Profile"), Contact::magicLink($profile_link)]
		];

		$entry = [
			'id'           => $contact['id'],
			'url'          => Contact::magicLink($profile_link),
			'itemurl'      => $itemurl,
			'thumb'        => ProxyUtils::proxifyUrl($contact[$photo_size], false, ProxyUtils::SIZE_THUMB),
			'img_hover'    => $contact['name'],
			'name'         => $contact['name'],
			'details'      => $details,
			'account_type' => Contact::getAccountType($contact),
			'profile'      => $profile,
			'location'     => $location_e,
			'tags'         => $contact['pub_keywords'],
			'gender'       => $gender,
			'pdesc'        => $pdesc,
			'marital'      => $marital,
			'homepage'     => $homepage,
			'about'        => $about,
			'photo_menu'   => $photo_menu,

		];

		$hook = ['contact' => $contact, 'entry' => $entry];

		Hook::callAll('directory_item', $hook);

		unset($profile);
		unset($location);

		return $hook['entry'];
	}
}
