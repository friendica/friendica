<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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
 * Name: Vier
 * Version: 1.2
 * Author: Fabio <http://kirgroup.com/profile/fabrixxm>
 * Author: Ike <http://pirati.ca/profile/heluecht>
 * Author: Beanow <https://fc.oscp.info/profile/beanow>
 * Maintainer: Ike <http://pirati.ca/profile/heluecht>
 * Description: "Vier" is a very compact and modern theme. It uses the font awesome font library: http://fortawesome.github.com/Font-Awesome/
 */

use Friendica\App;
use Friendica\Content\ForumManager;
use Friendica\Core\Addon;
use Friendica\Core\Renderer;
use Friendica\Core\Search;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Util\Strings;

/*
 * This script can be included even when the app is in maintenance mode which requires us to avoid any config call
 */

function vier_init(App $a)
{
	Renderer::setActiveTemplateEngine('smarty3');

	$args = DI::args();

	if (
		DI::mode()->has(App\Mode::MAINTENANCEDISABLED)
		&& (
			$args->get(0) === 'profile' && $args->get(1) === ($a->getLoggedInUserNickname() ?? '')
			|| $args->get(0) === 'network' && DI::userSession()->getLocalUserId()
		)
	) {
		vier_community_info();

		DI::page()['htmlhead'] .= "<link rel='stylesheet' type='text/css' href='view/theme/vier/wide.css' media='screen and (min-width: 1300px)'/>\n";
	}

	if (DI::mode()->isMobile() || DI::mode()->isMobile()) {
		DI::page()['htmlhead'] .= '<meta name=viewport content="width=device-width, initial-scale=1">'."\n";
		DI::page()['htmlhead'] .= '<link rel="stylesheet" type="text/css" href="view/theme/vier/mobile.css" media="screen"/>'."\n";
	}
	/// @todo deactivated since it doesn't work with desktop browsers at the moment
	//DI::page()['htmlhead'] .= '<link rel="stylesheet" type="text/css" href="view/theme/vier/mobile.css" media="screen and (max-width: 1000px)"/>'."\n";

	DI::page()['htmlhead'] .= <<< EOT
<link rel='stylesheet' type='text/css' href='view/theme/vier/narrow.css' media='screen and (max-width: 1100px)' />
<script type="text/javascript">
function showThread(id) {
	$("#collapsed-comments-" + id).show()
	$("#collapsed-comments-" + id + " .collapsed-comments").show()
}
function hideThread(id) {
	$("#collapsed-comments-" + id).hide()
	$("#collapsed-comments-" + id + " .collapsed-comments").hide()
}

function cmtBbOpen(id) {
	$("#comment-edit-bb-" + id).show();
}
function cmtBbClose(id) {
	$("#comment-edit-bb-" + id).hide();
}
</script>
EOT;

	if (DI::mode()->isMobile() || DI::mode()->isMobile()) {
		DI::page()['htmlhead'] .= <<< EOT
<script>
	$(document).ready(function() {
		$(".mobile-aside-toggle a").click(function(e){
			e.preventDefault();
			$("aside").toggleClass("show");
		});
		$(".tabs").click(function(e){
			$(this).toggleClass("show");
		});
	});
</script>
EOT;
	}

	// Hide the left menu bar
	/// @TODO maybe move this static array out where it should belong?
	if (empty(DI::page()['aside']) && in_array($args->get(0), ["community", "calendar", "help", "delegation", "notifications",
			"probe", "webfinger", "login", "invite", "credits"])) {
		DI::page()['htmlhead'] .= "<link rel='stylesheet' href='view/theme/vier/hide.css' />";
	}
}

function get_vier_config($key, $default = false, $admin = false)
{
	if (DI::userSession()->getLocalUserId() && !$admin) {
		$result = DI::pConfig()->get(DI::userSession()->getLocalUserId(), "vier", $key);
		if (!is_null($result)) {
			return $result;
		}
	}

	$result = DI::config()->get("vier", $key);
	if (!is_null($result)) {
		return $result;
	}

	return $default;
}

function vier_community_info()
{
	$show_pages      = get_vier_config("show_pages", 1);
	$show_profiles   = get_vier_config("show_profiles", 1);
	$show_helpers    = get_vier_config("show_helpers", 1);
	$show_services   = get_vier_config("show_services", 1);
	$show_friends    = get_vier_config("show_friends", 1);
	$show_lastusers  = get_vier_config("show_lastusers", 1);

	// get_baseurl
	$url = DI::baseUrl();
	$aside['$url'] = $url;

	// comunity_profiles
	if ($show_profiles) {
		$contacts = Contact\Relation::getCachedSuggestions(DI::userSession()->getLocalUserId(), 0, 9);

		$tpl = Renderer::getMarkupTemplate('ch_directory_item.tpl');
		if (DBA::isResult($contacts)) {
			$aside['$comunity_profiles_title'] = DI::l10n()->t('Community Profiles');
			$aside['$comunity_profiles_items'] = [];

			foreach ($contacts as $contact) {
				$entry = Renderer::replaceMacros($tpl, [
					'$id' => $contact['id'],
					'$profile_link' => 'contact/follow?url=' . urlencode($contact['url']),
					'$photo' => Contact::getMicro($contact),
					'$alt_text' => $contact['name'],
				]);
				$aside['$comunity_profiles_items'][] = $entry;
			}
		}
	}

	// last 9 users
	if ($show_lastusers) {
		$condition = ['blocked' => false];
		if (!DI::config()->get('system', 'publish_all')) {
			$condition['publish'] = true;
		}

		$tpl = Renderer::getMarkupTemplate('ch_directory_item.tpl');

		$profiles = DBA::selectToArray('owner-view', [], $condition, ['order' => ['register_date' => true], 'limit' => [0, 9]]);

		if (DBA::isResult($profiles)) {
			$aside['$lastusers_title'] = DI::l10n()->t('Last users');
			$aside['$lastusers_items'] = [];

			foreach ($profiles as $profile) {
				$profile_link = 'profile/' . ((strlen($profile['nickname'])) ? $profile['nickname'] : $profile['uid']);
				$entry = Renderer::replaceMacros($tpl, [
					'$id' => $profile['id'],
					'$profile_link' => $profile_link,
					'$photo' => DI::baseUrl()->remove($profile['thumb']),
					'$alt_text' => $profile['name']]);
				$aside['$lastusers_items'][] = $entry;
			}
		}
	}

	//right_aside FIND FRIENDS
	if ($show_friends && DI::userSession()->getLocalUserId()) {
		$nv = [];
		$nv['findpeople'] = DI::l10n()->t('Find People');
		$nv['desc'] = DI::l10n()->t('Enter name or interest');
		$nv['label'] = DI::l10n()->t('Connect/Follow');
		$nv['hint'] = DI::l10n()->t('Examples: Robert Morgenstein, Fishing');
		$nv['findthem'] = DI::l10n()->t('Find');
		$nv['suggest'] = DI::l10n()->t('Friend Suggestions');
		$nv['similar'] = DI::l10n()->t('Similar Interests');
		$nv['random'] = DI::l10n()->t('Random Profile');
		$nv['inv'] = DI::l10n()->t('Invite Friends');
		$nv['directory'] = DI::l10n()->t('Global Directory');
		$nv['global_dir'] = Search::getGlobalDirectory();
		$nv['local_directory'] = DI::l10n()->t('Local Directory');

		$aside['$nv'] = $nv;
	}

	//Community_Pages at right_aside
	if ($show_pages && DI::userSession()->getLocalUserId()) {
		$aside['$page'] = ForumManager::widget('network/forum', DI::userSession()->getLocalUserId());;
	}
	// END Community Page

	// helpers
	if ($show_helpers) {
		$r = [];

		$helperlist = DI::config()->get("vier", "helperlist");

		$helpers = explode(",", $helperlist);

		if ($helpers) {
			foreach ($helpers as $helper) {
				$urls[] = Strings::normaliseLink(trim($helper));
			}
			$r = DBA::selectToArray('contact', ['url', 'name'], ['uid' => 0, 'nurl' => $urls]);
		}

		foreach ($r as $index => $helper) {
			$r[$index]["url"] = Contact::magicLink($helper["url"]);
		}

		$r[] = ["url" => "help/Quick-Start-guide", "name" => DI::l10n()->t("Quick Start")];

		$tpl = Renderer::getMarkupTemplate('ch_helpers.tpl');

		if ($r) {
			$helpers = [];
			$helpers['title'] = ["", DI::l10n()->t('Help'), "", ""];

			$aside['$helpers_items'] = [];

			foreach ($r as $rr) {
				$entry = Renderer::replaceMacros($tpl, [
					'$url' => $rr['url'],
					'$title' => $rr['name'],
				]);
				$aside['$helpers_items'][] = $entry;
			}

			$aside['$helpers'] = $helpers;
		}
	}
	// end helpers

	// connectable services
	if ($show_services) {
		/// @TODO This whole thing is hard-coded, better rewrite to Intercepting Filter Pattern (future-todo)
		$r = [];

		if (Addon::isEnabled("buffer")) {
			$r[] = ["photo" => "images/buffer.png", "name" => "Buffer"];
		}

		if (Addon::isEnabled("blogger")) {
			$r[] = ["photo" => "images/blogger.png", "name" => "Blogger"];
		}

		if (Addon::isEnabled("dwpost")) {
			$r[] = ["photo" => "images/dreamwidth.png", "name" => "Dreamwidth"];
		}

		if (Addon::isEnabled("ifttt")) {
			$r[] = ["photo" => "addon/ifttt/ifttt.png", "name" => "IFTTT"];
		}

		if (Addon::isEnabled("statusnet")) {
			$r[] = ["photo" => "images/gnusocial.png", "name" => "GNU Social"];
		}

		/// @TODO old-lost code (and below)?
		//if (Addon::isEnabled("ijpost")) {
		//	$r[] = array("photo" => "images/", "name" => "");
		//}

		if (Addon::isEnabled("libertree")) {
			$r[] = ["photo" => "images/libertree.png", "name" => "Libertree"];
		}

		//if (Addon::isEnabled("ljpost")) {
		//	$r[] = array("photo" => "images/", "name" => "");
		//}

		if (Addon::isEnabled("pumpio")) {
			$r[] = ["photo" => "images/pumpio.png", "name" => "pump.io"];
		}

		if (Addon::isEnabled("tumblr")) {
			$r[] = ["photo" => "images/tumblr.png", "name" => "Tumblr"];
		}

		if (Addon::isEnabled("twitter")) {
			$r[] = ["photo" => "images/twitter.png", "name" => "Twitter"];
		}

		if (Addon::isEnabled("wppost")) {
			$r[] = ["photo" => "images/wordpress.png", "name" => "Wordpress"];
		}

		if (function_exists("imap_open") && !DI::config()->get("system", "imap_disabled")) {
			$r[] = ["photo" => "images/mail.png", "name" => "E-Mail"];
		}

		$tpl = Renderer::getMarkupTemplate('ch_connectors.tpl');

		if (DBA::isResult($r)) {
			$con_services = [];
			$con_services['title'] = ["", DI::l10n()->t('Connect Services'), "", ""];
			$aside['$con_services'] = $con_services;

			foreach ($r as $rr) {
				$entry = Renderer::replaceMacros($tpl, [
					'$url' => $url,
					'$photo' => $rr['photo'],
					'$alt_text' => $rr['name'],
				]);
				$aside['$connector_items'][] = $entry;
			}
		}
	}
	//end connectable services

	//print right_aside
	$tpl = Renderer::getMarkupTemplate('communityhome.tpl');
	DI::page()['right_aside'] = Renderer::replaceMacros($tpl, $aside);
}

/**
 * @param int|null $uid
 * @return null
 * @see \Friendica\Core\Theme::getBackgroundColor()
 * @TODO Implement this function
 */
function vier_get_background_color(int $uid = null)
{
	return null;
}

/**
 * @param int|null $uid
 * @return null
 * @see \Friendica\Core\Theme::getThemeColor()
 * @TODO Implement this function
 */
function vier_get_theme_color(int $uid = null)
{
	return null;
}
