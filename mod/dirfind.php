<?php
/**
 * @file mod/dirfind.php
 */

use Friendica\App;
use Friendica\Content\ContactSelector;
use Friendica\Content\Pager;
use Friendica\Content\Text;
use Friendica\Content\Widget;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model;
use Friendica\Module;
use Friendica\Network\Probe;
use Friendica\Protocol\PortableContact;
use Friendica\Util\Network;
use Friendica\Util\Proxy as ProxyUtils;


function dirfind_init(App $a) {

	if (! local_user()) {
		notice(L10n::t('Permission denied.') . EOL );
		return;
	}

	if (! x($a->page,'aside')) {
		$a->page['aside'] = '';
	}

	$a->page['aside'] .= Widget::findPeople();

	$a->page['aside'] .= Widget::follow();
}

function dirfind_content(App $a, $prefix = "") {

	$community = false;
	$discover_user = false;

	$local = Config::get('system','poco_local_search');

	$search = $prefix.Text::noTags(trim(defaults($_REQUEST, 'search', '')));

	$header = '';

	if (strpos($search,'@') === 0) {
		$search = substr($search,1);
		$header = L10n::t('People Search - %s', $search);
		if ((valid_email($search) && Network::isEmailDomainValid($search)) ||
			(substr(normalise_link($search), 0, 7) == "http://")) {
			$user_data = Probe::uri($search);
			$discover_user = (in_array($user_data["network"], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::OSTATUS, Protocol::DIASPORA]));
		}
	}

	if (strpos($search,'!') === 0) {
		$search = substr($search,1);
		$community = true;
		$header = L10n::t('Forum Search - %s', $search);
	}

	$o = '';

	if ($search) {
		$pager = new Pager($a->query_string);

		if ($discover_user) {
			$j = new stdClass();
			$j->total = 1;
			$j->items_page = 1;
			$j->page = $pager->getPage();

			$objresult = new stdClass();
			$objresult->cid = 0;
			$objresult->name = $user_data["name"];
			$objresult->addr = $user_data["addr"];
			$objresult->url = $user_data["url"];
			$objresult->photo = $user_data["photo"];
			$objresult->tags = "";
			$objresult->network = $user_data["network"];

			$contact = Model\Contact::getDetailsByURL($user_data["url"], local_user());
			$objresult->cid = $contact["cid"];
			$objresult->pcid = $contact["zid"];

			$j->results[] = $objresult;

			// Add the contact to the global contacts if it isn't already in our system
			if (($contact["cid"] == 0) && ($contact["zid"] == 0) && ($contact["gid"] == 0)) {
				Model\GContact::update($user_data);
			}
		} elseif ($local) {
			if ($community) {
				$extra_sql = " AND `community`";
			} else {
				$extra_sql = "";
			}

			$pager->setItemsPerPage(80);

			if (Config::get('system','diaspora_enabled')) {
				$diaspora = Protocol::DIASPORA;
			} else {
				$diaspora = Protocol::DFRN;
			}

			if (!Config::get('system','ostatus_disabled')) {
				$ostatus = Protocol::OSTATUS;
			} else {
				$ostatus = Protocol::DFRN;
			}

			$search2 = "%".$search."%";

			/// @TODO These 2 SELECTs are not checked on validity with DBA::isResult()
			$count = q("SELECT count(*) AS `total` FROM `gcontact`
					WHERE NOT `hide` AND `network` IN ('%s', '%s', '%s') AND
						((`last_contact` >= `last_failure`) OR (`updated` >= `last_failure`)) AND
						(`url` LIKE '%s' OR `name` LIKE '%s' OR `location` LIKE '%s' OR
						`addr` LIKE '%s' OR `about` LIKE '%s' OR `keywords` LIKE '%s') $extra_sql",
					DBA::escape(Protocol::DFRN), DBA::escape($ostatus), DBA::escape($diaspora),
					DBA::escape(Text::escapeTags($search2)), DBA::escape(Text::escapeTags($search2)), DBA::escape(Text::escapeTags($search2)),
					DBA::escape(Text::escapeTags($search2)), DBA::escape(Text::escapeTags($search2)), DBA::escape(Text::escapeTags($search2)));

			$results = q("SELECT `nurl`
					FROM `gcontact`
					WHERE NOT `hide` AND `network` IN ('%s', '%s', '%s') AND
						((`last_contact` >= `last_failure`) OR (`updated` >= `last_failure`)) AND
						(`url` LIKE '%s' OR `name` LIKE '%s' OR `location` LIKE '%s' OR
						`addr` LIKE '%s' OR `about` LIKE '%s' OR `keywords` LIKE '%s') $extra_sql
						GROUP BY `nurl`
						ORDER BY `updated` DESC LIMIT %d, %d",
					DBA::escape(Protocol::DFRN), DBA::escape($ostatus), DBA::escape($diaspora),
					DBA::escape(Text::escapeTags($search2)), DBA::escape(Text::escapeTags($search2)), DBA::escape(Text::escapeTags($search2)),
					DBA::escape(Text::escapeTags($search2)), DBA::escape(Text::escapeTags($search2)), DBA::escape(Text::escapeTags($search2)),
					$pager->getStart(), $pager->getItemsPerPage());
			$j = new stdClass();
			$j->total = $count[0]["total"];
			$j->items_page = $pager->getItemsPerPage();
			$j->page = $pager->getPage();
			foreach ($results AS $result) {
				if (PortableContact::alternateOStatusUrl($result["nurl"])) {
					continue;
				}

				$urlparts = parse_url($result["nurl"]);

				// Ignore results that look strange.
				// For historic reasons the gcontact table does contain some garbage.
				if (!empty($urlparts['query']) || !empty($urlparts['fragment'])) {
					continue;
				}

				$result = Model\Contact::getDetailsByURL($result["nurl"], local_user());

				if ($result["name"] == "") {
					$result["name"] = end(explode("/", $urlparts["path"]));
				}

				$objresult = new stdClass();
				$objresult->cid = $result["cid"];
				$objresult->pcid = $result["zid"];
				$objresult->name = $result["name"];
				$objresult->addr = $result["addr"];
				$objresult->url = $result["url"];
				$objresult->photo = $result["photo"];
				$objresult->tags = $result["keywords"];
				$objresult->network = $result["network"];

				$j->results[] = $objresult;
			}

			// Add found profiles from the global directory to the local directory
			Worker::add(PRIORITY_LOW, 'DiscoverPoCo', "dirsearch", urlencode($search));
		} else {
			$p = (($pager->getPage() != 1) ? '&p=' . $pager->getPage() : '');

			if (strlen(Config::get('system','directory'))) {
				$x = Network::fetchUrl(get_server() . '/lsearch?f=' . $p .  '&search=' . urlencode($search));
			}

			$j = json_decode($x);

			$pager->setItemsPerPage($j->items_page);
		}

		if (!empty($j->results)) {
			$id = 0;

			foreach ($j->results as $jj) {

				$alt_text = "";

				$contact_details = Model\Contact::getDetailsByURL($jj->url, local_user());

				$itemurl = (($contact_details["addr"] != "") ? $contact_details["addr"] : $jj->url);

				// If We already know this contact then don't show the "connect" button
				if ($jj->cid > 0) {
					$connlnk = "";
					$conntxt = "";
					$contact = DBA::selectFirst('contact', [], ['id' => $jj->cid]);
					if (DBA::isResult($contact)) {
						$photo_menu = Model\Contact::photoMenu($contact);
						$details = Module\Contact::getContactTemplateVars($contact);
						$alt_text = $details['alt_text'];
					} else {
						$photo_menu = [];
					}
				} else {
					$connlnk = System::baseUrl().'/follow/?url='.(!empty($jj->connect) ? $jj->connect : $jj->url);
					$conntxt = L10n::t('Connect');

					$contact = DBA::selectFirst('contact', [], ['id' => $jj->pcid]);
					if (DBA::isResult($contact)) {
						$photo_menu = Model\Contact::photoMenu($contact);
					} else {
						$photo_menu = [];
					}

					$photo_menu['profile'] = [L10n::t("View Profile"), Model\Contact::magicLink($jj->url)];
					$photo_menu['follow'] = [L10n::t("Connect/Follow"), $connlnk];
				}

				$jj->photo = str_replace("http:///photo/", get_server()."/photo/", $jj->photo);

				$entry = [
					'alt_text' => $alt_text,
					'url' => Model\Contact::magicLink($jj->url),
					'itemurl' => $itemurl,
					'name' => htmlentities($jj->name),
					'thumb' => ProxyUtils::proxifyUrl($jj->photo, false, ProxyUtils::SIZE_THUMB),
					'img_hover' => $jj->tags,
					'conntxt' => $conntxt,
					'connlnk' => $connlnk,
					'photo_menu' => $photo_menu,
					'details'       => $contact_details['location'],
					'tags'          => $contact_details['keywords'],
					'about'         => $contact_details['about'],
					'account_type'  => Model\Contact::getAccountType($contact_details),
					'network' => ContactSelector::networkToName($jj->network, $jj->url),
					'id' => ++$id,
				];
				$entries[] = $entry;
			}

			$tpl = Text::getMarkupTemplate('viewcontact_template.tpl');
			$o .= Text::replaceMacros($tpl,[
				'title' => $header,
				'$contacts' => $entries,
				'$paginate' => $pager->renderFull($j->total),
			]);
		} else {
			info(L10n::t('No matches') . EOL);
		}

	}

	return $o;
}
