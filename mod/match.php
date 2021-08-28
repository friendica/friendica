<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

use Friendica\App;
use Friendica\Content\Widget;
use Friendica\Core\Renderer;
use Friendica\Core\Search;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Module\Contact as ModuleContact;

/**
 * Controller for /match.
 *
 * It takes keywords from your profile and queries the directory server for
 * matching keywords from other profiles.
 *
 * @param App $a App
 *
 * @return string
 * @throws ImagickException
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 * @throws Exception
 */
function match_content(App $a)
{
	if (!local_user()) {
		return '';
	}

	DI::page()['aside'] .= Widget::findPeople();
	DI::page()['aside'] .= Widget::follow();

	$_SESSION['return_path'] = DI::args()->getCommand();

	$profile = Profile::getByUID(local_user());

	if (!DBA::isResult($profile)) {
		return '';
	}
	if (!$profile['pub_keywords'] && (!$profile['prv_keywords'])) {
		notice(DI::l10n()->t('No keywords to match. Please add keywords to your profile.'));
		return '';
	}

	$params = [];
	$tags = trim($profile['pub_keywords'] . ' ' . $profile['prv_keywords']);

	if (DI::mode()->isMobile()) {
		$limit = DI::pConfig()->get(local_user(), 'system', 'itemspage_mobile_network',
			DI::config()->get('system', 'itemspage_network_mobile'));
	} else {
		$limit = DI::pConfig()->get(local_user(), 'system', 'itemspage_network',
			DI::config()->get('system', 'itemspage_network'));
	}

	$params['s'] = $tags;
	$params['n'] = 100;

	$entries = [];
	foreach ([Search::getGlobalDirectory(), DI::baseUrl()] as $server) {
		if (empty($server)) {
			continue;
		}

		$msearch = json_decode(DI::httpClient()->post($server . '/msearch', $params)->getBody());
		if (!empty($msearch)) {
			$entries = match_get_contacts($msearch, $entries, $limit);
		}
	}

	if (empty($entries)) {
		info(DI::l10n()->t('No matches'));
	}

	$tpl = Renderer::getMarkupTemplate('viewcontact_template.tpl');
	$o = Renderer::replaceMacros($tpl, [
		'$title'    => DI::l10n()->t('Profile Match'),
		'$contacts' => array_slice($entries, 0, $limit),
	]);

	return $o;
}

function match_get_contacts($msearch, $entries, $limit)
{
	if (empty($msearch->results)) {
		return $entries;
	}

	foreach ($msearch->results as $profile) {
		if (!$profile) {
			continue;
		}

		// Already known contact
		$contact = Contact::getByURL($profile->url, null, ['rel'], local_user());
		if (!empty($contact) && in_array($contact['rel'], [Contact::FRIEND, Contact::SHARING])) {
			continue;
		}

		$contact = Contact::getByURLForUser($profile->url, local_user());
		if (!empty($contact)) {
			$entries[$contact['id']] = ModuleContact::getContactTemplateVars($contact);
		}

		if (count($entries) == $limit) {
			break;
		}
	}
	return $entries;
}