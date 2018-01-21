<?php
/**
 * @file mod/match.php
 */
use Friendica\App;
use Friendica\Content\Widget;
use Friendica\Core\Config;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Profile;

require_once 'include/text.php';
require_once 'mod/proxy.php';

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
	if (! DBM::isResult($r)) {
		return;
	}
	if (! $r[0]['pub_keywords'] && (! $r[0]['prv_keywords'])) {
		notice(t('No keywords to match. Please add keywords to your default profile.') . EOL);
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
			$x = post_url(get_server().'/msearch', $params);
		} else {
			$x = post_url(System::baseUrl() . '/msearch', $params);
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
					dbesc($match_nurl)
				);

				if (!count($match)) {
					$jj->photo = str_replace("http:///photo/", get_server()."/photo/", $jj->photo);
					$connlnk = System::baseUrl() . '/follow/?url=' . $jj->url;
					$photo_menu = [
						'profile' => [t("View Profile"), Profile::zrl($jj->url)],
						'follow' => [t("Connect/Follow"), $connlnk]
					];

					$contact_details = Contact::getDetailsByURL($jj->url, local_user());

					$entry = [
						'url' => Profile::zrl($jj->url),
						'itemurl' => (($contact_details['addr'] != "") ? $contact_details['addr'] : $jj->url),
						'name' => $jj->name,
						'details'       => $contact_details['location'],
						'tags'          => $contact_details['keywords'],
						'about'         => $contact_details['about'],
						'account_type'  => Contact::getAccountType($contact_details),
						'thumb' => proxy_url($jj->photo, false, PROXY_SIZE_THUMB),
						'inttxt' => ' ' . t('is interested in:'),
						'conntxt' => t('Connect'),
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
				'$title' => t('Profile Match'),
				'$contacts' => $entries,
				'$paginate' => paginate($a)]
			);
		} else {
			info(t('No matches') . EOL);
		}
	}

	return $o;
}
