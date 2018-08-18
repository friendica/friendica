<?php
/**
 * @file mod/match.php
 */

use Friendica\App;
use Friendica\Content\Widget;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Util\Network;
use Friendica\Util\Proxy as ProxyUtils;

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
 */
function match_content(App $a)
{
	$o = '';
	if (! local_user()) {
		return;
	}

	$a->page['aside'] .= Widget::findPeople();
	$a->page['aside'] .= Widget::follow();

	$_SESSION['return_url'] = System::baseUrl() . '/' . $a->cmd;

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
		$params['s'] = $tags;
		if ($a->pager['page'] != 1) {
			$params['p'] = $a->pager['page'];
		}

		if (strlen(Config::get('system', 'directory'))) {
			$x = Network::post(get_server().'/msearch', $params);
		} else {
			$x = Network::post(System::baseUrl() . '/msearch', $params);
		}

		$j = json_decode($x);

		if ($j->total) {
			$a->set_pager_total($j->total);
			$a->set_pager_itemspage($j->items_page);
		}

		if (count($j->results)) {
			$id = 0;

			foreach ($j->results as $jj) {
				$match_nurl = normalise_link($jj->url);
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

			$tpl = get_markup_template('viewcontact_template.tpl');

			$o .= replace_macros(
				$tpl,
				[
				'$title' => L10n::t('Profile Match'),
				'$contacts' => $entries,
				'$paginate' => paginate($a)]
			);
		} else {
			info(L10n::t('No matches') . EOL);
		}
	}

	return $o;
}
