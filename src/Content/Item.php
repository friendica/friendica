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

namespace Friendica\Content;

use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Item as ModelItem;
use Friendica\Model\Tag;
use Friendica\Model\Post;
use Friendica\Protocol\Activity;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Friendica\Util\XML;

/**
 * A content helper class for displaying items
 */
class Item
{
	/** @var Activity */
	private $activity;
	/** @var L10n */
	private $l10n;
	/** @var Profiler */
	private $profiler;

	public function __construct(Profiler $profiler, Activity $activity, L10n $l10n)
	{
		$this->profiler = $profiler;
		$this->activity = $activity;
		$this->l10n   = $l10n;
	}
	
	/**
	 * Return array with details for categories and folders for an item
	 *
	 * @param array $item
	 * @param int   $uid
	 * @return [array, array]
	 *
	 * [
	 *      [ // categories array
	 *          {
	 *               'name': 'category name',
	 *               'removeurl': 'url to remove this category',
	 *               'first': 'is the first in this array? true/false',
	 *               'last': 'is the last in this array? true/false',
	 *           } ,
	 *           ....
	 *       ],
	 *       [ //folders array
	 *			{
	 *               'name': 'folder name',
	 *               'removeurl': 'url to remove this folder',
	 *               'first': 'is the first in this array? true/false',
	 *               'last': 'is the last in this array? true/false',
	 *           } ,
	 *           ....
	 *       ]
	 *  ]
	 */
	public function determineCategoriesTerms(array $item, int $uid = 0)
	{
		$categories = [];
		$folders = [];
		$first = true;

		$uid = $item['uid'] ?: $uid;

		foreach (Post\Category::getArrayByURIId($item['uri-id'], $uid, Post\Category::CATEGORY) as $savedFolderName) {
			if (!empty($item['author-link'])) {
				$url = $item['author-link'] . "?category=" . rawurlencode($savedFolderName);
			} else {
				$url = '#';
			}
			$categories[] = [
				'name' => $savedFolderName,
				'url' => $url,
				'removeurl' => local_user() == $uid ? 'filerm/' . $item['id'] . '?cat=' . rawurlencode($savedFolderName) : '',
				'first' => $first,
				'last' => false
			];
			$first = false;
		}

		if (count($categories)) {
			$categories[count($categories) - 1]['last'] = true;
		}

		if (local_user() == $uid) {
			foreach (Post\Category::getArrayByURIId($item['uri-id'], $uid, Post\Category::FILE) as $savedFolderName) {
				$folders[] = [
					'name' => $savedFolderName,
					'url' => "#",
					'removeurl' => local_user() == $uid ? 'filerm/' . $item['id'] . '?term=' . rawurlencode($savedFolderName) : '',
					'first' => $first,
					'last' => false
				];
				$first = false;
			}
		}

		if (count($folders)) {
			$folders[count($folders) - 1]['last'] = true;
		}

		return [$categories, $folders];
	}

	/**
	 * This function removes the tag $tag from the text $body and replaces it with
	 * the appropriate link.
	 *
	 * @param string  $body        the text to replace the tag in
	 * @param string  $inform      a comma-seperated string containing everybody to inform
	 * @param integer $profile_uid the user id to replace the tag for (0 = anyone)
	 * @param string  $tag         the tag to replace
	 * @param string  $network     The network of the post
	 *
	 * @return array|bool ['replaced' => $replaced, 'contact' => $contact];
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function replaceTag(&$body, &$inform, $profile_uid, $tag, $network = '')
	{
		$replaced = false;

		//is it a person tag?
		if (Tag::isType($tag, Tag::MENTION, Tag::IMPLICIT_MENTION, Tag::EXCLUSIVE_MENTION)) {
			$tag_type = substr($tag, 0, 1);
			//is it already replaced?
			if (strpos($tag, '[url=')) {
				return $replaced;
			}

			//get the person's name
			$name = substr($tag, 1);

			// Sometimes the tag detection doesn't seem to work right
			// This is some workaround
			$nameparts = explode(' ', $name);
			$name = $nameparts[0];

			// Try to detect the contact in various ways
			if (strpos($name, 'http://') || strpos($name, '@')) {
				$contact = Contact::getByURLForUser($name, $profile_uid);
			} else {
				$contact = false;
				$fields = ['id', 'url', 'nick', 'name', 'alias', 'network', 'forum', 'prv'];

				if (strrpos($name, '+')) {
					// Is it in format @nick+number?
					$tagcid = intval(substr($name, strrpos($name, '+') + 1));
					$contact = DBA::selectFirst('contact', $fields, ['id' => $tagcid, 'uid' => $profile_uid]);
				}

				// select someone by nick in the current network
				if (!DBA::isResult($contact) && ($network != '')) {
					$condition = ["`nick` = ? AND `network` = ? AND `uid` = ?",
						$name, $network, $profile_uid];
					$contact = DBA::selectFirst('contact', $fields, $condition);
				}

				// select someone by attag in the current network
				if (!DBA::isResult($contact) && ($network != '')) {
					$condition = ["`attag` = ? AND `network` = ? AND `uid` = ?",
						$name, $network, $profile_uid];
					$contact = DBA::selectFirst('contact', $fields, $condition);
				}

				//select someone by name in the current network
				if (!DBA::isResult($contact) && ($network != '')) {
					$condition = ['name' => $name, 'network' => $network, 'uid' => $profile_uid];
					$contact = DBA::selectFirst('contact', $fields, $condition);
				}

				// select someone by nick in any network
				if (!DBA::isResult($contact)) {
					$condition = ["`nick` = ? AND `uid` = ?", $name, $profile_uid];
					$contact = DBA::selectFirst('contact', $fields, $condition);
				}

				// select someone by attag in any network
				if (!DBA::isResult($contact)) {
					$condition = ["`attag` = ? AND `uid` = ?", $name, $profile_uid];
					$contact = DBA::selectFirst('contact', $fields, $condition);
				}

				// select someone by name in any network
				if (!DBA::isResult($contact)) {
					$condition = ['name' => $name, 'uid' => $profile_uid];
					$contact = DBA::selectFirst('contact', $fields, $condition);
				}
			}

			// Check if $contact has been successfully loaded
			if (DBA::isResult($contact)) {
				if (strlen($inform) && (isset($contact['notify']) || isset($contact['id']))) {
					$inform .= ',';
				}

				if (isset($contact['id'])) {
					$inform .= 'cid:' . $contact['id'];
				} elseif (isset($contact['notify'])) {
					$inform  .= $contact['notify'];
				}

				$profile = $contact['url'];
				$newname = ($contact['name'] ?? '') ?: $contact['nick'];
			}

			//if there is an url for this persons profile
			if (isset($profile) && ($newname != '')) {
				$replaced = true;
				// create profile link
				$profile = str_replace(',', '%2c', $profile);
				$newtag = $tag_type.'[url=' . $profile . ']' . $newname . '[/url]';
				$body = str_replace($tag_type . $name, $newtag, $body);
			}
		}

		return ['replaced' => $replaced, 'contact' => $contact];
	}

	/**
	 * Render actions localized
	 *
	 * @param $item
	 * @throws ImagickException
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function localize(&$item)
	{
		$this->profiler->startRecording('rendering');
		/// @todo The following functionality needs to be cleaned up.
		if (!empty($item['verb'])) {
			$xmlhead = "<" . "?xml version='1.0' encoding='UTF-8' ?" . ">";

			if (stristr($item['verb'], Activity::POKE)) {
				$verb = urldecode(substr($item['verb'], strpos($item['verb'],'#') + 1));
				if (!$verb) {
					$this->profiler->stopRecording();
					return;
				}
				if ($item['object-type'] == "" || $item['object-type'] !== Activity\ObjectType::PERSON) {
					$this->profiler->stopRecording();
					return;
				}

				$obj = XML::parseString($xmlhead . $item['object']);

				$Bname = $obj->title;
				$Blink = $obj->id;
				$Bphoto = "";

				foreach ($obj->link as $l) {
					$atts = $l->attributes();
					switch ($atts['rel']) {
						case "alternate": $Blink = $atts['href'];
						case "photo": $Bphoto = $atts['href'];
					}
				}

				$author = ['uid' => 0, 'id' => $item['author-id'],
					'network' => $item['author-network'], 'url' => $item['author-link']];
				$A = '[url=' . Contact::magicLinkByContact($author) . ']' . $item['author-name'] . '[/url]';

				if (!empty($Blink)) {
					$B = '[url=' . Contact::magicLink($Blink) . ']' . $Bname . '[/url]';
				} else {
					$B = '';
				}

				if ($Bphoto != "" && !empty($Blink)) {
					$Bphoto = '[url=' . Contact::magicLink($Blink) . '][img=80x80]' . $Bphoto . '[/img][/url]';
				}

				/*
				* we can't have a translation string with three positions but no distinguishable text
				* So here is the translate string.
				*/
				$txt = $this->l10n->t('%1$s poked %2$s');

				// now translate the verb
				$poked_t = trim(sprintf($txt, '', ''));
				$txt = str_replace($poked_t, $this->l10n->t($verb), $txt);

				// then do the sprintf on the translation string

				$item['body'] = sprintf($txt, $A, $B) . "\n\n\n" . $Bphoto;

			}

			if ($this->activity->match($item['verb'], Activity::TAG)) {
				$fields = ['author-id', 'author-link', 'author-name', 'author-network',
					'verb', 'object-type', 'resource-id', 'body', 'plink'];
				$obj = Post::selectFirst($fields, ['uri' => $item['parent-uri']]);
				if (!DBA::isResult($obj)) {
					$this->profiler->stopRecording();
					return;
				}

				$author_arr = ['uid' => 0, 'id' => $item['author-id'],
					'network' => $item['author-network'], 'url' => $item['author-link']];
				$author  = '[url=' . Contact::magicLinkByContact($author_arr) . ']' . $item['author-name'] . '[/url]';

				$author_arr = ['uid' => 0, 'id' => $obj['author-id'],
					'network' => $obj['author-network'], 'url' => $obj['author-link']];
				$objauthor  = '[url=' . Contact::magicLinkByContact($author_arr) . ']' . $obj['author-name'] . '[/url]';

				switch ($obj['verb']) {
					case Activity::POST:
						switch ($obj['object-type']) {
							case Activity\ObjectType::EVENT:
								$post_type = $this->l10n->t('event');
								break;
							default:
								$post_type = $this->l10n->t('status');
						}
						break;
					default:
						if ($obj['resource-id']) {
							$post_type = $this->l10n->t('photo');
							$m=[]; preg_match("/\[url=([^]]*)\]/", $obj['body'], $m);
							$rr['plink'] = $m[1];
						} else {
							$post_type = $this->l10n->t('status');
						}
						// Let's break everthing ... ;-)
						break;
				}
				$plink = '[url=' . $obj['plink'] . ']' . $post_type . '[/url]';

				$parsedobj = XML::parseString($xmlhead . $item['object']);

				$tag = sprintf('#[url=%s]%s[/url]', $parsedobj->id, $parsedobj->content);
				$item['body'] = $this->l10n->t('%1$s tagged %2$s\'s %3$s with %4$s', $author, $objauthor, $plink, $tag);
			}
		}

		$matches = null;
		if (preg_match_all('/@\[url=(.*?)\]/is', $item['body'], $matches, PREG_SET_ORDER)) {
			foreach ($matches as $mtch) {
				if (!strpos($mtch[1], 'zrl=')) {
					$item['body'] = str_replace($mtch[0], '@[url=' . Contact::magicLink($mtch[1]) . ']', $item['body']);
				}
			}
		}

		// add sparkle links to appropriate permalinks
		// Only create a redirection to a magic link when logged in
		if (!empty($item['plink']) && Session::isAuthenticated() && $item['private'] == ModelItem::PRIVATE) {
			$author = ['uid' => 0, 'id' => $item['author-id'],
				'network' => $item['author-network'], 'url' => $item['author-link']];
			$item['plink'] = Contact::magicLinkByContact($author, $item['plink']);
		}
		$this->profiler->stopRecording();
	}

	public function photoMenu($item, string $formSecurityToken)
	{
		$this->profiler->startRecording('rendering');
		$sub_link = '';
		$poke_link = '';
		$contact_url = '';
		$pm_url = '';
		$status_link = '';
		$photos_link = '';
		$posts_link = '';
		$block_link = '';
		$ignore_link = '';

		if (local_user() && local_user() == $item['uid'] && $item['gravity'] == GRAVITY_PARENT && !$item['self'] && !$item['mention']) {
			$sub_link = 'javascript:doFollowThread(' . $item['id'] . '); return false;';
		}

		$author = ['uid' => 0, 'id' => $item['author-id'],
			'network' => $item['author-network'], 'url' => $item['author-link']];
		$profile_link = Contact::magicLinkByContact($author, $item['author-link']);
		$sparkle = (strpos($profile_link, 'redir/') === 0);

		$cid = 0;
		$pcid = $item['author-id'];
		$network = '';
		$rel = 0;
		$condition = ['uid' => local_user(), 'nurl' => Strings::normaliseLink($item['author-link'])];
		$contact = DBA::selectFirst('contact', ['id', 'network', 'rel'], $condition);
		if (DBA::isResult($contact)) {
			$cid = $contact['id'];
			$network = $contact['network'];
			$rel = $contact['rel'];
		}

		if ($sparkle) {
			$status_link = $profile_link . '/status';
			$photos_link = str_replace('/profile/', '/photos/', $profile_link);
			$profile_link = $profile_link . '/profile';
		}

		if (!empty($pcid)) {
			$contact_url = 'contact/' . $pcid;
			$posts_link  = $contact_url . '/posts';
			$block_link  = $item['self'] ? '' : $contact_url . '/block?t=' . $formSecurityToken;
			$ignore_link = $item['self'] ? '' : $contact_url . '/ignore?t=' . $formSecurityToken;
		}

		if ($cid && !$item['self']) {
			$contact_url = 'contact/' . $cid;
			$poke_link   = $contact_url . '/poke';
			$posts_link  = $contact_url . '/posts';

			if (in_array($network, [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA])) {
				$pm_url = 'message/new/' . $cid;
			}
		}

		if (local_user()) {
			$menu = [
				$this->l10n->t('Follow Thread') => $sub_link,
				$this->l10n->t('View Status') => $status_link,
				$this->l10n->t('View Profile') => $profile_link,
				$this->l10n->t('View Photos') => $photos_link,
				$this->l10n->t('Network Posts') => $posts_link,
				$this->l10n->t('View Contact') => $contact_url,
				$this->l10n->t('Send PM') => $pm_url,
				$this->l10n->t('Block') => $block_link,
				$this->l10n->t('Ignore') => $ignore_link
			];

			if (!empty($item['language'])) {
				$menu[$this->l10n->t('Languages')] = 'javascript:alert(\'' . ModelItem::getLanguageMessage($item) . '\');';
			}

			if ($network == Protocol::DFRN) {
				$menu[$this->l10n->t("Poke")] = $poke_link;
			}

			if ((($cid == 0) || ($rel == Contact::FOLLOWER)) &&
				in_array($item['network'], Protocol::FEDERATED)) {
				$menu[$this->l10n->t('Connect/Follow')] = 'follow?url=' . urlencode($item['author-link']) . '&auto=1';
			}
		} else {
			$menu = [$this->l10n->t('View Profile') => $item['author-link']];
		}

		$args = ['item' => $item, 'menu' => $menu];

		Hook::callAll('item_photo_menu', $args);

		$menu = $args['menu'];

		$o = '';
		foreach ($menu as $k => $v) {
			if (strpos($v, 'javascript:') === 0) {
				$v = substr($v, 11);
				$o .= '<li role="menuitem"><a onclick="' . $v . '">' . $k . '</a></li>' . PHP_EOL;
			} elseif ($v) {
				$o .= '<li role="menuitem"><a href="' . $v . '">' . $k . '</a></li>' . PHP_EOL;
			}
		}
		$this->profiler->stopRecording();
		return $o;
	}

	public function visibleActivity($item) {

		if (empty($item['verb']) || $this->activity->isHidden($item['verb'])) {
			return false;
		}
	
		// @TODO below if() block can be rewritten to a single line: $isVisible = allConditionsHere;
		if ($this->activity->match($item['verb'], Activity::FOLLOW) &&
			$item['object-type'] === Activity\ObjectType::NOTE &&
			empty($item['self']) &&
			$item['uid'] == local_user()) {
			return false;
		}
	
		return true;
	}
}
