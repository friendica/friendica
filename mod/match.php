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
use Friendica\Model\Contact;
use Friendica\Util\Network;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Util\Strings;

require_once 'include/text.php';

/**
 * @brief Controller for /match.
 *
 * It takes keywords from your profile and queries the directory server for
 * matching keywords from other profiles.
 *
 * @param App $a App
 *
 * @return void|string
 * @throws ImagickException
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function match_content(App $a)
{
	$o = '';
	if (! local_user()) {
		return;
	}

	$a->page['aside'] .= Widget::findPeople();
	$a->page['aside'] .= Widget::follow();

	$_SESSION['return_path'] = $a->cmd;

	$r = q(
		"SELECT `pub_keywords`, `prv_keywords` FROM `profile` WHERE `is-default` = 1 AND `uid` = %d LIMIT 1",
		intval(local_user())
	);
	if (! DBA::isResult($r)) {
		return;
	}
	if (! $r[0]['pub_keywords'] && (! $r[0]['prv_keywords'])) {
		notice(L10n::t('No keywords to match. Please add keywords to your default profile.') . EOL);
		return;
	}

	$params = [];
	$tags = trim($r[0]['pub_keywords'] . ' ' . $r[0]['prv_keywords']);

	if ($tags) {
		$pager = new Pager($a->query_string);

		$params['s'] = $tags;
		if ($pager->getPage() != 1) {
			$params['p'] = $pager->getPage();
		}

		if (strlen(Config::get('system', 'directory'))) {
			$x = Network::post(get_server().'/msearch', $params)->getBody();
		} else {
			$x = Network::post(System::baseUrl() . '/msearch', $params)->getBody();
		}

		$j = json_decode($x);

		if (count($j->results)) {
			$pager->setItemsPerPage($j->items_page);

			$id = 0;

			$entries = [];
			foreach ($j->results as $jj) {
				$match_nurl = Strings::normaliseLink($jj->url);
				$match = q(
					"SELECT `nurl` FROM `contact` WHERE `uid` = '%d' AND nurl='%s' LIMIT 1",
					intval(local_user()),
					DBA::escape($match_nurl)
				);

				if (!count($match)) {
					$jj->photo = str_replace("http:///photo/", get_server()."/photo/", $jj->photo);
					$connlnk = System::baseUrl() . '/follow/?url=' . $jj->url;
					$photo_menu = [
						'profile' => [L10n::t("View Profile"), Contact::magicLink($jj->url)],
						'follow' => [L10n::t("Connect/Follow"), $connlnk]
					];

					$contact_details = Contact::getDetailsByURL($jj->url, local_user());

					$entry = [
						'url' => Contact::magicLink($jj->url),
						'itemurl' => defaults($contact_details, 'addr', $jj->url),
						'name' => $jj->name,
						'details'       => defaults($contact_details, 'location', ''),
						'tags'          => defaults($contact_details, 'keywords', ''),
						'about'         => defaults($contact_details, 'about', ''),
						'account_type'  => Contact::getAccountType($contact_details),
						'thumb' => ProxyUtils::proxifyUrl($jj->photo, false, ProxyUtils::SIZE_THUMB),
						'inttxt' => ' ' . L10n::t('is interested in:'),
						'conntxt' => L10n::t('Connect'),
						'connlnk' => $connlnk,
						'img_hover' => $jj->tags,
						'photo_menu' => $photo_menu,
						'id' => ++$id,
					];
					$entries[] = $entry;
				}
			}

			$tpl = Renderer::getMarkupTemplate('viewcontact_template.tpl');

			$o .= Renderer::replaceMacros($tpl, [
				'$title'    => L10n::t('Profile Match'),
				'$contacts' => $entries,
				'$paginate' => $pager->renderFull($j->total)
			]);
		} else {
			info(L10n::t('No matches') . EOL);
		}
	}

	return $o;
}
