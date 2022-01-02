<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Module\Item;

use DateTime;
use Friendica\BaseModule;
use Friendica\Content\Feature;
use Friendica\Core\ACL;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\Core\Theme;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException\NotImplementedException;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Temporal;

class Compose extends BaseModule
{
	protected function post(array $request = [])
	{
		if (!empty($_REQUEST['body'])) {
			$_REQUEST['return'] = 'network';
			require_once 'mod/item.php';
			item_post(DI::app());
		} else {
			notice(DI::l10n()->t('Please enter a post body.'));
		}
	}

	protected function content(array $request = []): string
	{
		if (!local_user()) {
			return Login::form('compose', false);
		}

		$a = DI::app();

		if ($a->getCurrentTheme() !== 'frio') {
			throw new NotImplementedException(DI::l10n()->t('This feature is only available with the frio theme.'));
		}

		/// @TODO Retrieve parameter from router
		$posttype = $this->parameters['type'] ?? Item::PT_ARTICLE;
		if (!in_array($posttype, [Item::PT_ARTICLE, Item::PT_PERSONAL_NOTE])) {
			switch ($posttype) {
				case 'note':
					$posttype = Item::PT_PERSONAL_NOTE;
					break;
				default:
					$posttype = Item::PT_ARTICLE;
					break;
			}
		}

		$user = User::getById(local_user(), ['allow_cid', 'allow_gid', 'deny_cid', 'deny_gid', 'default-location']);

		$aclFormatter = DI::aclFormatter();

		$contact_allow_list = $aclFormatter->expand($user['allow_cid']);
		$group_allow_list   = $aclFormatter->expand($user['allow_gid']);
		$contact_deny_list  = $aclFormatter->expand($user['deny_cid']);
		$group_deny_list    = $aclFormatter->expand($user['deny_gid']);

		switch ($posttype) {
			case Item::PT_PERSONAL_NOTE:
				$compose_title = DI::l10n()->t('Compose new personal note');
				$type = 'note';
				$doesFederate = false;
				$contact_allow_list = [$a->getContactId()];
				$group_allow_list = [];
				$contact_deny_list = [];
				$group_deny_list = [];
				break;
			default:
				$compose_title = DI::l10n()->t('Compose new post');
				$type = 'post';
				$doesFederate = true;

				$contact_allow = $_REQUEST['contact_allow'] ?? '';
				$group_allow = $_REQUEST['group_allow'] ?? '';
				$contact_deny = $_REQUEST['contact_deny'] ?? '';
				$group_deny = $_REQUEST['group_deny'] ?? '';

				if ($contact_allow
					. $group_allow
					. $contact_deny
				    . $group_deny)
				{
					$contact_allow_list = $contact_allow ? explode(',', $contact_allow) : [];
					$group_allow_list   = $group_allow   ? explode(',', $group_allow)   : [];
					$contact_deny_list  = $contact_deny  ? explode(',', $contact_deny)  : [];
					$group_deny_list    = $group_deny    ? explode(',', $group_deny)    : [];
				}

				break;
		}

		$title         = $_REQUEST['title']         ?? '';
		$category      = $_REQUEST['category']      ?? '';
		$body          = $_REQUEST['body']          ?? '';
		$location      = $_REQUEST['location']      ?? $user['default-location'];
		$wall          = $_REQUEST['wall']          ?? $type == 'post';

		$jotplugins = '';
		Hook::callAll('jot_tool', $jotplugins);

		// Output
		DI::page()->registerFooterScript(Theme::getPathForFile('js/ajaxupload.js'));
		DI::page()->registerFooterScript(Theme::getPathForFile('js/linkPreview.js'));
		DI::page()->registerFooterScript(Theme::getPathForFile('js/compose.js'));

		$contact = Contact::getById($a->getContactId());

		$tpl = Renderer::getMarkupTemplate('item/compose.tpl');
		return Renderer::replaceMacros($tpl, [
			'$compose_title'=> $compose_title,
			'$visibility_title'=> DI::l10n()->t('Visibility'),
			'$id'           => 0,
			'$posttype'     => $posttype,
			'$type'         => $type,
			'$wall'         => $wall,
			'$default'      => '',
			'$mylink'       => DI::baseUrl()->remove($contact['url']),
			'$mytitle'      => DI::l10n()->t('This is you'),
			'$myphoto'      => DI::baseUrl()->remove($contact['thumb']),
			'$submit'       => DI::l10n()->t('Submit'),
			'$edbold'       => DI::l10n()->t('Bold'),
			'$editalic'     => DI::l10n()->t('Italic'),
			'$eduline'      => DI::l10n()->t('Underline'),
			'$edquote'      => DI::l10n()->t('Quote'),
			'$edcode'       => DI::l10n()->t('Code'),
			'$edimg'        => DI::l10n()->t('Image'),
			'$edurl'        => DI::l10n()->t('Link'),
			'$edattach'     => DI::l10n()->t('Link or Media'),
			'$prompttext'   => DI::l10n()->t('Please enter a image/video/audio/webpage URL:'),
			'$preview'      => DI::l10n()->t('Preview'),
			'$location_set' => DI::l10n()->t('Set your location'),
			'$location_clear' => DI::l10n()->t('Clear the location'),
			'$location_unavailable' => DI::l10n()->t('Location services are unavailable on your device'),
			'$location_disabled' => DI::l10n()->t('Location services are disabled. Please check the website\'s permissions on your device'),
			'$wait'         => DI::l10n()->t('Please wait'),
			'$placeholdertitle' => DI::l10n()->t('Set title'),
			'$placeholdercategory' => (Feature::isEnabled(local_user(),'categories') ? DI::l10n()->t('Categories (comma-separated list)') : ''),
			'$scheduled_at' => Temporal::getDateTimeField(
				new DateTime(),
				new DateTime('now + 6 months'),
				null,
				DI::l10n()->t('Scheduled at'),
				'scheduled_at'
			),

			'$title'        => $title,
			'$category'     => $category,
			'$body'         => $body,
			'$location'     => $location,

			'$contact_allow'=> implode(',', $contact_allow_list),
			'$group_allow'  => implode(',', $group_allow_list),
			'$contact_deny' => implode(',', $contact_deny_list),
			'$group_deny'   => implode(',', $group_deny_list),

			'$jotplugins'   => $jotplugins,
			'$rand_num'     => Crypto::randomDigits(12),
			'$acl_selector'  => ACL::getFullSelectorHTML(DI::page(), $a->getLoggedInUserId(), $doesFederate, [
				'allow_cid' => $contact_allow_list,
				'allow_gid' => $group_allow_list,
				'deny_cid'  => $contact_deny_list,
				'deny_gid'  => $group_deny_list,
			]),
		]);
	}
}
