<?php
/**
 * @file mod/match.php
 */

use Friendica\App;
use Friendica\Content\Pager;
use Friendica\Content\Widget;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Util\Network;
use Friendica\Util\Proxy as ProxyUtils;

/**
 * @brief Controller for /match.
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

	$a->page['aside'] .= Widget::findPeople();
	$a->page['aside'] .= Widget::follow();

	$_SESSION['return_path'] = DI::args()->getCommand();

	$profile = Profile::getByUID(local_user());

	if (!DBA::isResult($profile)) {
		return '';
	}
	if (!$profile['pub_keywords'] && (!$profile['prv_keywords'])) {
		notice(L10n::t('No keywords to match. Please add keywords to your default profile.') . EOL);
		return '';
	}

	$params = [];
	$tags = trim($profile['pub_keywords'] . ' ' . $profile['prv_keywords']);

	$params['s'] = $tags;
	$params['n'] = 100;

	if (strlen(Config::get('system', 'directory'))) {
		$host = get_server();
	} else {
		$host = System::baseUrl();
	}

	$msearch_json = Network::post($host . '/msearch', $params)->getBody();

	$msearch = json_decode($msearch_json);

	$start = $_GET['start'] ?? 0;
	$entries = [];
	$paginate = '';

	if (!empty($msearch->results)) {
		for ($i = $start;count($entries) < 10 && $i < $msearch->total; $i++) {
			$profile = $msearch->results[$i];

			// Already known contact
			if (!$profile || Contact::getIdForURL($profile->url, local_user(), true)) {
				continue;
			}

			// Workaround for wrong directory photo URL
			$profile->photo = str_replace('http:///photo/', get_server() . '/photo/', $profile->photo);

			$connlnk = System::baseUrl() . '/follow/?url=' . $profile->url;
			$photo_menu = [
				'profile' => [L10n::t("View Profile"), Contact::magicLink($profile->url)],
				'follow' => [L10n::t("Connect/Follow"), $connlnk]
			];

			$contact_details = Contact::getDetailsByURL($profile->url, 0);

			$entry = [
				'url'          => Contact::magicLink($profile->url),
				'itemurl'      => $contact_details['addr'] ?? $profile->url,
				'name'         => $profile->name,
				'details'      => $contact_details['location'] ?? '',
				'tags'         => $contact_details['keywords'] ?? '',
				'about'        => $contact_details['about'] ?? '',
				'account_type' => Contact::getAccountType($contact_details),
				'thumb'        => ProxyUtils::proxifyUrl($profile->photo, false, ProxyUtils::SIZE_THUMB),
				'conntxt'      => L10n::t('Connect'),
				'connlnk'      => $connlnk,
				'img_hover'    => $profile->tags,
				'photo_menu'   => $photo_menu,
				'id'           => $i,
			];
			$entries[] = $entry;
		}

		$data = [
			'class' => 'pager',
			'first' => [
				'url'   => 'match',
				'text'  => L10n::t('first'),
				'class' => 'previous' . ($start == 0 ? 'disabled' : '')
			],
			'next'  => [
				'url'   => 'match?start=' . $i,
				'text'  => L10n::t('next'),
				'class' =>  'next' . ($i >= $msearch->total ? ' disabled' : '')
			]
		];

		$tpl = Renderer::getMarkupTemplate('paginate.tpl');
		$paginate = Renderer::replaceMacros($tpl, ['pager' => $data]);
	}

	if (empty($entries)) {
		info(L10n::t('No matches') . EOL);
	}

	$tpl = Renderer::getMarkupTemplate('viewcontact_template.tpl');
	$o = Renderer::replaceMacros($tpl, [
		'$title'    => L10n::t('Profile Match'),
		'$contacts' => $entries,
		'$paginate' => $paginate
	]);

	return $o;
}
