<?php

namespace Friendica\Module\Item;

use Friendica\BaseModule;
use Friendica\Content\Feature;
use Friendica\Core\ACL;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Theme;
use Friendica\Registry\DI;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException\NotImplementedException;
use Friendica\Registry\App;
use Friendica\Registry\Util;
use Friendica\Util\Crypto;

class Compose extends BaseModule
{
	public static function post(array $parameters = [])
	{
		if (!empty($_REQUEST['body'])) {
			$_REQUEST['return'] = 'network';
			require_once 'mod/item.php';
			item_post(DI::app());
		} else {
			notice(L10n::t('Please enter a post body.'));
		}
	}

	public static function content(array $parameters = [])
	{
		if (!local_user()) {
			return Login::form('compose', false);
		}

		$a = DI::app();

		if ($a->getCurrentTheme() !== 'frio') {
			throw new NotImplementedException(L10n::t('This feature is only available with the frio theme.'));
		}

		/// @TODO Retrieve parameter from router
		$posttype = $parameters['type'] ?? Item::PT_ARTICLE;
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

		$user = User::getById(local_user(), ['allow_cid', 'allow_gid', 'deny_cid', 'deny_gid', 'hidewall', 'default-location']);

		$aclFormatter = Util::aclFormatter();

		$contact_allow_list = $aclFormatter->expand($user['allow_cid']);
		$group_allow_list   = $aclFormatter->expand($user['allow_gid']);
		$contact_deny_list  = $aclFormatter->expand($user['deny_cid']);
		$group_deny_list    = $aclFormatter->expand($user['deny_gid']);

		switch ($posttype) {
			case Item::PT_PERSONAL_NOTE:
				$compose_title = L10n::t('Compose new personal note');
				$type = 'note';
				$doesFederate = false;
				$contact_allow_list = [$a->contact['id']];
				$group_allow_list = [];
				$contact_deny_list = [];
				$group_deny_list = [];
				break;
			default:
				$compose_title = L10n::t('Compose new post');
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
		App::page()->registerFooterScript(Theme::getPathForFile('js/ajaxupload.js'));
		App::page()->registerFooterScript(Theme::getPathForFile('js/linkPreview.js'));
		App::page()->registerFooterScript(Theme::getPathForFile('js/compose.js'));

		$tpl = Renderer::getMarkupTemplate('item/compose.tpl');
		return Renderer::replaceMacros($tpl, [
			'$compose_title'=> $compose_title,
			'$visibility_title'=> L10n::t('Visibility'),
			'$id'           => 0,
			'$posttype'     => $posttype,
			'$type'         => $type,
			'$wall'         => $wall,
			'$default'      => '',
			'$mylink'       => App::baseUrl()->remove($a->contact['url']),
			'$mytitle'      => L10n::t('This is you'),
			'$myphoto'      => App::baseUrl()->remove($a->contact['thumb']),
			'$submit'       => L10n::t('Submit'),
			'$edbold'       => L10n::t('Bold'),
			'$editalic'     => L10n::t('Italic'),
			'$eduline'      => L10n::t('Underline'),
			'$edquote'      => L10n::t('Quote'),
			'$edcode'       => L10n::t('Code'),
			'$edimg'        => L10n::t('Image'),
			'$edurl'        => L10n::t('Link'),
			'$edattach'     => L10n::t('Link or Media'),
			'$prompttext'   => L10n::t('Please enter a image/video/audio/webpage URL:'),
			'$preview'      => L10n::t('Preview'),
			'$location_set' => L10n::t('Set your location'),
			'$location_clear' => L10n::t('Clear the location'),
			'$location_unavailable' => L10n::t('Location services are unavailable on your device'),
			'$location_disabled' => L10n::t('Location services are disabled. Please check the website\'s permissions on your device'),
			'$wait'         => L10n::t('Please wait'),
			'$placeholdertitle' => L10n::t('Set title'),
			'$placeholdercategory' => (Feature::isEnabled(local_user(),'categories') ? L10n::t('Categories (comma-separated list)') : ''),

			'$title'        => $title,
			'$category'     => $category,
			'$body'         => $body,
			'$location'     => $location,

			'$contact_allow'=> implode(',', $contact_allow_list),
			'$group_allow'  => implode(',', $group_allow_list),
			'$contact_deny' => implode(',', $contact_deny_list),
			'$group_deny'   => implode(',', $group_deny_list),

			'$jotplugins'   => $jotplugins,
			'$sourceapp'    => L10n::t($a->sourcename),
			'$rand_num'     => Crypto::randomDigits(12),
			'$acl_selector'  => ACL::getFullSelectorHTML(App::page(), $a->user, $doesFederate, [
				'allow_cid' => $contact_allow_list,
				'allow_gid' => $group_allow_list,
				'deny_cid'  => $contact_deny_list,
				'deny_gid'  => $group_deny_list,
			]),
		]);
	}
}
