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

use Friendica\App;
use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Core\ACL;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Core\Theme;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Item as ItemModel;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Model\Verb;
use Friendica\Object\Post as PostObject;
use Friendica\Object\Thread;
use Friendica\Protocol\Activity;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Friendica\Util\Proxy;
use Friendica\Util\Strings;
use Friendica\Util\Temporal;
use Psr\Log\LoggerInterface;

class Conversation
{
	/** @var Activity */
	private $activity;
	/** @var L10n */
	private $l10n;
	/** @var Profiler */
	private $profiler;
	/** @var LoggerInterface */
	private $logger;
	/** @var Item */
	private $item;
	/** @var App\Arguments */
	private $args;
	/** @var IManagePersonalConfigValues */
	private $pConfig;
	/** @var BaseURL */
	private $baseURL;
	/** @var IManageConfigValues */
	private $config;
	/** @var App */
	private $app;
	/** @var App\Page */
	private $page;
	/** @var App\Mode */
	private $mode;

	public function __construct(LoggerInterface $logger, Profiler $profiler, Activity $activity, L10n $l10n, Item $item, Arguments $args, BaseURL $baseURL, IManageConfigValues $config, IManagePersonalConfigValues $pConfig, App\Page $page, App\Mode $mode, App $app)
	{
		$this->activity = $activity;
		$this->item     = $item;
		$this->config   = $config;
		$this->mode     = $mode;
		$this->baseURL  = $baseURL;
		$this->profiler = $profiler;
		$this->logger   = $logger;
		$this->l10n     = $l10n;
		$this->args     = $args;
		$this->pConfig  = $pConfig;
		$this->page     = $page;
		$this->app      = $app;
	}

	/**
	 * Checks item to see if it is one of the builtin activities (like/dislike, event attendance, consensus items, etc.)
	 *
	 * Increments the count of each matching activity and adds a link to the author as needed.
	 *
	 * @param array  $activity
	 * @param array &$conv_responses (already created with builtin activity structure)
	 * @return void
	 * @throws ImagickException
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function builtinActivityPuller(array $activity, array &$conv_responses)
	{
		foreach ($conv_responses as $mode => $v) {
			$sparkle = '';

			switch ($mode) {
				case 'like':
					$verb = Activity::LIKE;
					break;
				case 'dislike':
					$verb = Activity::DISLIKE;
					break;
				case 'attendyes':
					$verb = Activity::ATTEND;
					break;
				case 'attendno':
					$verb = Activity::ATTENDNO;
					break;
				case 'attendmaybe':
					$verb = Activity::ATTENDMAYBE;
					break;
				case 'announce':
					$verb = Activity::ANNOUNCE;
					break;
				default:
					return;
			}

			if (!empty($activity['verb']) && $this->activity->match($activity['verb'], $verb) && ($activity['gravity'] != GRAVITY_PARENT)) {
				$author = [
					'uid'     => 0,
					'id'      => $activity['author-id'],
					'network' => $activity['author-network'],
					'url'     => $activity['author-link']
				];
				$url = Contact::magicLinkByContact($author);
				if (strpos($url, 'redir/') === 0) {
					$sparkle = ' class="sparkle" ';
				}

				$link = '<a href="' . $url . '"' . $sparkle . '>' . htmlentities($activity['author-name']) . '</a>';

				if (empty($activity['thr-parent-id'])) {
					$activity['thr-parent-id'] = $activity['parent-uri-id'];
				}

				// Skip when the causer of the parent is the same than the author of the announce
				if (($verb == Activity::ANNOUNCE) && Post::exists(['uri-id' => $activity['thr-parent-id'],
					'uid' => $activity['uid'], 'causer-id' => $activity['author-id'], 'gravity' => GRAVITY_PARENT])) {
					continue;
				}

				if (!isset($conv_responses[$mode][$activity['thr-parent-id']])) {
					$conv_responses[$mode][$activity['thr-parent-id']] = [
						'links' => [],
						'self'  => 0,
					];
				} elseif (in_array($link, $conv_responses[$mode][$activity['thr-parent-id']]['links'])) {
					// only list each unique author once
					continue;
				}

				if (public_contact() == $activity['author-id']) {
					$conv_responses[$mode][$activity['thr-parent-id']]['self'] = 1;
				}

				$conv_responses[$mode][$activity['thr-parent-id']]['links'][] = $link;

				// there can only be one activity verb per item so if we found anything, we can stop looking
				return;
			}
		}
	}

	/**
	 * Format the activity text for an item/photo/video
	 *
	 * @param array  $links = array of pre-linked names of actors
	 * @param string $verb  = one of 'like, 'dislike', 'attendyes', 'attendno', 'attendmaybe'
	 * @param int    $id    = item id
	 * @return string formatted text
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function formatActivity(array $links, $verb, $id)
	{
		$this->profiler->startRecording('rendering');
		$o        = '';
		$expanded = '';
		$phrase   = '';

		$total = count($links);
		if ($total == 1) {
			$likers = $links[0];

			// Phrase if there is only one liker. In other cases it will be uses for the expanded
			// list which show all likers
			switch ($verb) {
				case 'like':
					$phrase = $this->l10n->t('%s likes this.', $likers);
					break;
				case 'dislike':
					$phrase = $this->l10n->t('%s doesn\'t like this.', $likers);
					break;
				case 'attendyes':
					$phrase = $this->l10n->t('%s attends.', $likers);
					break;
				case 'attendno':
					$phrase = $this->l10n->t('%s doesn\'t attend.', $likers);
					break;
				case 'attendmaybe':
					$phrase = $this->l10n->t('%s attends maybe.', $likers);
					break;
				case 'announce':
					$phrase = $this->l10n->t('%s reshared this.', $likers);
					break;
			}
		} elseif ($total > 1) {
			if ($total < $this->config->get('system', 'max_likers')) {
				$likers = implode(', ', array_slice($links, 0, -1));
				$likers .= ' ' . $this->l10n->t('and') . ' ' . $links[count($links) - 1];
			} else {
				$likers = implode(', ', array_slice($links, 0, $this->config->get('system', 'max_likers') - 1));
				$likers .= ' ' . $this->l10n->t('and %d other people', $total - $this->config->get('system', 'max_likers'));
			}

			$spanatts = "class=\"fakelink\" onclick=\"openClose('{$verb}list-$id');\"";

			$explikers = '';
			switch ($verb) {
				case 'like':
					$phrase    = $this->l10n->t('<span  %1$s>%2$d people</span> like this', $spanatts, $total);
					$explikers = $this->l10n->t('%s like this.', $likers);
					break;
				case 'dislike':
					$phrase    = $this->l10n->t('<span  %1$s>%2$d people</span> don\'t like this', $spanatts, $total);
					$explikers = $this->l10n->t('%s don\'t like this.', $likers);
					break;
				case 'attendyes':
					$phrase    = $this->l10n->t('<span  %1$s>%2$d people</span> attend', $spanatts, $total);
					$explikers = $this->l10n->t('%s attend.', $likers);
					break;
				case 'attendno':
					$phrase    = $this->l10n->t('<span  %1$s>%2$d people</span> don\'t attend', $spanatts, $total);
					$explikers = $this->l10n->t('%s don\'t attend.', $likers);
					break;
				case 'attendmaybe':
					$phrase    = $this->l10n->t('<span  %1$s>%2$d people</span> attend maybe', $spanatts, $total);
					$explikers = $this->l10n->t('%s attend maybe.', $likers);
					break;
				case 'announce':
					$phrase    = $this->l10n->t('<span  %1$s>%2$d people</span> reshared this', $spanatts, $total);
					$explikers = $this->l10n->t('%s reshared this.', $likers);
					break;
			}

			$expanded .= "\t" . '<p class="wall-item-' . $verb . '-expanded" id="' . $verb . 'list-' . $id . '" style="display: none;" >' . $explikers . EOL . '</p>';
		}

		$o .= Renderer::replaceMacros(Renderer::getMarkupTemplate('voting_fakelink.tpl'), [
			'$phrase' => $phrase,
			'$type'   => $verb,
			'$id'     => $id
		]);
		$o .= $expanded;

		$this->profiler->stopRecording();
		return $o;
	}

	public function statusEditor(array $x = [], $notes_cid = 0, $popup = false)
	{
		$user = User::getById($this->app->getLoggedInUserId(), ['uid', 'nickname', 'allow_location', 'default-location']);
		if (empty($user['uid'])) {
			return '';
		}

		$this->profiler->startRecording('rendering');
		$o = '';

		$x['allow_location']   = $x['allow_location']   ?? $user['allow_location'];
		$x['default_location'] = $x['default_location'] ?? $user['default-location'];
		$x['nickname']         = $x['nickname']         ?? $user['nickname'];
		$x['lockstate']        = $x['lockstate']        ?? ACL::getLockstateForUserId($user['uid']) ? 'lock' : 'unlock';
		$x['acl']              = $x['acl']              ?? ACL::getFullSelectorHTML($this->page, $user['uid'], true);
		$x['bang']             = $x['bang']             ?? '';
		$x['visitor']          = $x['visitor']          ?? 'block';
		$x['is_owner']         = $x['is_owner']         ?? true;
		$x['profile_uid']      = $x['profile_uid']      ?? local_user();


		$geotag = !empty($x['allow_location']) ? Renderer::replaceMacros(Renderer::getMarkupTemplate('jot_geotag.tpl'), []) : '';

		$tpl = Renderer::getMarkupTemplate('jot-header.tpl');
		$this->page['htmlhead'] .= Renderer::replaceMacros($tpl, [
			'$newpost'   => 'true',
			'$baseurl'   => $this->baseURL->get(true),
			'$geotag'    => $geotag,
			'$nickname'  => $x['nickname'],
			'$ispublic'  => $this->l10n->t('Visible to <strong>everybody</strong>'),
			'$linkurl'   => $this->l10n->t('Please enter a image/video/audio/webpage URL:'),
			'$term'      => $this->l10n->t('Tag term:'),
			'$fileas'    => $this->l10n->t('Save to Folder:'),
			'$whereareu' => $this->l10n->t('Where are you right now?'),
			'$delitems'  => $this->l10n->t("Delete item\x28s\x29?"),
			'$is_mobile' => $this->mode->isMobile(),
		]);

		$jotplugins = '';
		Hook::callAll('jot_tool', $jotplugins);

		$tpl = Renderer::getMarkupTemplate("jot.tpl");

		$o .= Renderer::replaceMacros($tpl, [
			'$new_post'            => $this->l10n->t('New Post'),
			'$return_path'         => $this->args->getQueryString(),
			'$action'              => 'item',
			'$share'               => ($x['button'] ?? '') ?: $this->l10n->t('Share'),
			'$loading'             => $this->l10n->t('Loading...'),
			'$upload'              => $this->l10n->t('Upload photo'),
			'$shortupload'         => $this->l10n->t('upload photo'),
			'$attach'              => $this->l10n->t('Attach file'),
			'$shortattach'         => $this->l10n->t('attach file'),
			'$edbold'              => $this->l10n->t('Bold'),
			'$editalic'            => $this->l10n->t('Italic'),
			'$eduline'             => $this->l10n->t('Underline'),
			'$edquote'             => $this->l10n->t('Quote'),
			'$edcode'              => $this->l10n->t('Code'),
			'$edimg'               => $this->l10n->t('Image'),
			'$edurl'               => $this->l10n->t('Link'),
			'$edattach'            => $this->l10n->t('Link or Media'),
			'$edvideo'             => $this->l10n->t('Video'),
			'$setloc'              => $this->l10n->t('Set your location'),
			'$shortsetloc'         => $this->l10n->t('set location'),
			'$noloc'               => $this->l10n->t('Clear browser location'),
			'$shortnoloc'          => $this->l10n->t('clear location'),
			'$title'               => $x['title'] ?? '',
			'$placeholdertitle'    => $this->l10n->t('Set title'),
			'$category'            => $x['category'] ?? '',
			'$placeholdercategory' => Feature::isEnabled(local_user(), 'categories') ? $this->l10n->t("Categories \x28comma-separated list\x29") : '',
			'$scheduled_at'        => Temporal::getDateTimeField(
				new \DateTime(),
				new \DateTime('now + 6 months'),
				null,
				$this->l10n->t('Scheduled at'),
				'scheduled_at'
			),
			'$wait'         => $this->l10n->t('Please wait'),
			'$permset'      => $this->l10n->t('Permission settings'),
			'$shortpermset' => $this->l10n->t('Permissions'),
			'$wall'         => $notes_cid ? 0 : 1,
			'$posttype'     => $notes_cid ? ItemModel::PT_PERSONAL_NOTE : ItemModel::PT_ARTICLE,
			'$content'      => $x['content'] ?? '',
			'$post_id'      => $x['post_id'] ?? '',
			'$baseurl'      => $this->baseURL->get(true),
			'$defloc'       => $x['default_location'],
			'$visitor'      => $x['visitor'],
			'$pvisit'       => $notes_cid ? 'none' : $x['visitor'],
			'$public'       => $this->l10n->t('Public post'),
			'$lockstate'    => $x['lockstate'],
			'$bang'         => $x['bang'],
			'$profile_uid'  => $x['profile_uid'],
			'$preview'      => $this->l10n->t('Preview'),
			'$jotplugins'   => $jotplugins,
			'$notes_cid'    => $notes_cid,
			'$cancel'       => $this->l10n->t('Cancel'),
			'$rand_num'     => Crypto::randomDigits(12),

			// ACL permissions box
			'$acl' => $x['acl'],

			//jot nav tab (used in some themes)
			'$message' => $this->l10n->t('Message'),
			'$browser' => $this->l10n->t('Browser'),

			'$compose_link_title' => $this->l10n->t('Open Compose page'),
		]);


		if ($popup == true) {
			$o = '<div id="jot-popup" style="display: none;">' . $o . '</div>';
		}

		$this->profiler->stopRecording();
		return $o;
	}

	/**
	 * "Render" a conversation or list of items for HTML display.
	 * There are two major forms of display:
	 *      - Sequential or unthreaded ("New Item View" or search results)
	 *      - conversation view
	 * The $mode parameter decides between the various renderings and also
	 * figures out how to determine page owner and other contextual items
	 * that are based on unique features of the calling module.
	 * @param array  $items
	 * @param        $mode
	 * @param        $update
	 * @param bool   $preview
	 * @param string $order
	 * @param int    $uid
	 * @return string
	 * @throws ImagickException
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function create(array $items, $mode, $update, $preview = false, $order = 'commented', $uid = 0)
	{
		$this->profiler->startRecording('rendering');

		$this->page->registerFooterScript(Theme::getPathForFile('asset/typeahead.js/dist/typeahead.bundle.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.js'));
		$this->page->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.css'));
		$this->page->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput-typeahead.css'));

		$ssl_state = (local_user() ? true : false);

		$live_update_div = '';

		$blocklist = $this->getBlocklist();

		$previewing = (($preview) ? ' preview ' : '');

		if ($mode === 'network') {
			$items = $this->addChildren($items, false, $order, $uid);
			if (!$update) {
				/*
				* The special div is needed for liveUpdate to kick in for this page.
				* We only launch liveUpdate if you aren't filtering in some incompatible
				* way and also you aren't writing a comment (discovered in javascript).
				*/
				$live_update_div = '<div id="live-network"></div>' . "\r\n"
					. "<script> var profile_uid = " . $_SESSION['uid']
					. "; var netargs = '" . substr($this->args->getCommand(), 8)
					. '?f='
					. (!empty($_GET['contactid']) ? '&contactid=' . rawurlencode($_GET['contactid']) : '')
					. (!empty($_GET['search'])    ? '&search='    . rawurlencode($_GET['search'])    : '')
					. (!empty($_GET['star'])      ? '&star='      . rawurlencode($_GET['star'])      : '')
					. (!empty($_GET['order'])     ? '&order='     . rawurlencode($_GET['order'])     : '')
					. (!empty($_GET['bmark'])     ? '&bmark='     . rawurlencode($_GET['bmark'])     : '')
					. (!empty($_GET['liked'])     ? '&liked='     . rawurlencode($_GET['liked'])     : '')
					. (!empty($_GET['conv'])      ? '&conv='      . rawurlencode($_GET['conv'])      : '')
					. (!empty($_GET['nets'])      ? '&nets='      . rawurlencode($_GET['nets'])      : '')
					. (!empty($_GET['cmin'])      ? '&cmin='      . rawurlencode($_GET['cmin'])      : '')
					. (!empty($_GET['cmax'])      ? '&cmax='      . rawurlencode($_GET['cmax'])      : '')
					. (!empty($_GET['file'])      ? '&file='      . rawurlencode($_GET['file'])      : '')

					. "'; </script>\r\n";
			}
		} elseif ($mode === 'profile') {
			$items = $this->addChildren($items, false, $order, local_user());

			if (!$update) {
				$tab = !empty($_GET['tab']) ? trim($_GET['tab']) : 'posts';

				if ($tab === 'posts') {
					/*
					* This is ugly, but we can't pass the profile_uid through the session to the ajax updater,
					* because browser prefetching might change it on us. We have to deliver it with the page.
					*/

					$live_update_div = '<div id="live-profile"></div>' . "\r\n"
						. "<script> var profile_uid = " . $uid
						. "; var netargs = '?f='; </script>\r\n";
				}
			}
		} elseif ($mode === 'notes') {
			$items = $this->addChildren($items, false, $order, local_user());

			if (!$update) {
				$live_update_div = '<div id="live-notes"></div>' . "\r\n"
					. "<script> var profile_uid = " . local_user()
					. "; var netargs = '/?f='; </script>\r\n";
			}
		} elseif ($mode === 'display') {
			$items = $this->addChildren($items, false, $order, $uid);

			if (!$update) {
				$live_update_div = '<div id="live-display"></div>' . "\r\n"
					. "<script> var profile_uid = " . Session::get('uid', 0) . ";"
					. "</script>";
			}
		} elseif ($mode === 'community') {
			$items = $this->addChildren($items, true, $order, $uid);

			if (!$update) {
				$live_update_div = '<div id="live-community"></div>' . "\r\n"
					. "<script> var profile_uid = -1; var netargs = '" . substr($this->args->getCommand(), 10)
					. '?f='
					. (!empty($_GET['no_sharer']) ? '&no_sharer=' . rawurlencode($_GET['no_sharer']) : '')
					. "'; </script>\r\n";
			}
		} elseif ($mode === 'contacts') {
			$items = $this->addChildren($items, false, $order, $uid);

			if (!$update) {
				$live_update_div = '<div id="live-contact"></div>' . "\r\n"
					. "<script> var profile_uid = -1; var netargs = '" . substr($this->args->getCommand(), 8)
					."/?f='; </script>\r\n";
			}
		} elseif ($mode === 'search') {
			$live_update_div = '<div id="live-search"></div>' . "\r\n";
		}

		$page_dropping = ((local_user() && local_user() == $uid) ? true : false);

		if (!$update) {
			$_SESSION['return_path'] = $this->args->getQueryString();
		}

		$cb = ['items' => $items, 'mode' => $mode, 'update' => $update, 'preview' => $preview];
		Hook::callAll('conversation_start', $cb);

		$items = $cb['items'];

		$conv_responses = [
			'like'        => [],
			'dislike'     => [],
			'attendyes'   => [],
			'attendno'    => [],
			'attendmaybe' => [],
			'announce'    => [],
		];

		if ($this->pConfig->get(local_user(), 'system', 'hide_dislike')) {
			unset($conv_responses['dislike']);
		}

		// array with html for each thread (parent+comments)
		$threads   = [];
		$threadsid = -1;

		$page_template     = Renderer::getMarkupTemplate("conversation.tpl");
		$formSecurityToken = BaseModule::getFormSecurityToken('contact_action');

		if (!empty($items)) {
			if (in_array($mode, ['community', 'contacts', 'profile'])) {
				$writable = true;
			} else {
				$writable = ($items[0]['uid'] == 0) && in_array($items[0]['network'], Protocol::FEDERATED);
			}

			if (!local_user()) {
				$writable = false;
			}

			if (in_array($mode, ['filed', 'search', 'contact-posts'])) {

				/*
				* "New Item View" on network page or search page results
				* - just loop through the items and format them minimally for display
				*/

				$tpl = 'search_item.tpl';

				$uriids = [];

				foreach ($items as $item) {
					if (in_array($item['uri-id'], $uriids)) {
						continue;
					}

					$uriids[] = $item['uri-id'];

					if (!$this->item->visibleActivity($item)) {
						continue;
					}

					if (in_array($item['author-id'], $blocklist)) {
						continue;
					}

					$threadsid++;

					// prevent private email from leaking.
					if ($item['network'] === Protocol::MAIL && local_user() != $item['uid']) {
						continue;
					}

					$profile_name = $item['author-name'];
					if (!empty($item['author-link']) && empty($item['author-name'])) {
						$profile_name = $item['author-link'];
					}

					$tags = Tag::populateFromItem($item);

					$author       = ['uid' => 0, 'id' => $item['author-id'], 'network' => $item['author-network'], 'url' => $item['author-link']];
					$profile_link = Contact::magicLinkByContact($author);

					$sparkle = '';
					if (strpos($profile_link, 'redir/') === 0) {
						$sparkle = ' sparkle';
					}

					$locate = ['location' => $item['location'], 'coord' => $item['coord'], 'html' => ''];
					Hook::callAll('render_location', $locate);
					$location_html = $locate['html'] ?: Strings::escapeHtml($locate['location'] ?: $locate['coord'] ?: '');

					$this->item->localize($item);
					if ($mode === 'filed') {
						$dropping = true;
					} else {
						$dropping = false;
					}

					$drop = [
						'dropping' => $dropping,
						'pagedrop' => $page_dropping,
						'select'   => $this->l10n->t('Select'),
						'delete'   => $this->l10n->t('Delete'),
					];

					$likebuttons = [
						'like'     => null,
						'dislike'  => null,
						'share'    => null,
						'announce' => null,
					];

					if ($this->pConfig->get(local_user(), 'system', 'hide_dislike')) {
						unset($likebuttons['dislike']);
					}

					$body_html = ItemModel::prepareBody($item, true, $preview);

					[$categories, $folders] = $this->item->determineCategoriesTerms($item, local_user());

					if (!empty($item['content-warning']) && $this->pConfig->get(local_user(), 'system', 'disable_cw', false)) {
						$title = ucfirst($item['content-warning']);
					} else {
						$title = $item['title'];
					}

					$tmp_item = [
						'template'             => $tpl,
						'id'                   => ($preview ? 'P0' : $item['id']),
						'guid'                 => ($preview ? 'Q0' : $item['guid']),
						'commented'            => $item['commented'],
						'received'             => $item['received'],
						'created_date'         => $item['created'],
						'uriid'                => $item['uri-id'],
						'network'              => $item['network'],
						'network_name'         => ContactSelector::networkToName($item['author-network'], $item['author-link'], $item['network']),
						'network_icon'         => ContactSelector::networkToIcon($item['network'], $item['author-link']),
						'linktitle'            => $this->l10n->t('View %s\'s profile @ %s', $profile_name, $item['author-link']),
						'profile_url'          => $profile_link,
						'item_photo_menu_html' => $this->item->photoMenu($item, $formSecurityToken),
						'name'                 => $profile_name,
						'sparkle'              => $sparkle,
						'lock'                 => false,
						'thumb'                => $this->baseURL->remove(Contact::getAvatarUrlForUrl($item['author-link'], $item['uid'], Proxy::SIZE_THUMB)),
						'title'                => $title,
						'body_html'            => $body_html,
						'tags'                 => $tags['tags'],
						'hashtags'             => $tags['hashtags'],
						'mentions'             => $tags['mentions'],
						'implicit_mentions'    => $tags['implicit_mentions'],
						'txt_cats'             => $this->l10n->t('Categories:'),
						'txt_folders'          => $this->l10n->t('Filed under:'),
						'has_cats'             => ((count($categories)) ? 'true' : ''),
						'has_folders'          => ((count($folders)) ? 'true' : ''),
						'categories'           => $categories,
						'folders'              => $folders,
						'text'                 => strip_tags($body_html),
						'localtime'            => DateTimeFormat::local($item['created'], 'r'),
						'utc'                  => DateTimeFormat::utc($item['created'], 'c'),
						'ago'                  => (($item['app']) ? $this->l10n->t('%s from %s', Temporal::getRelativeDate($item['created']), $item['app']) : Temporal::getRelativeDate($item['created'])),
						'location_html'        => $location_html,
						'indent'               => '',
						'owner_name'           => '',
						'owner_url'            => '',
						'owner_photo'          => $this->baseURL->remove(Contact::getAvatarUrlForUrl($item['owner-link'], $item['uid'], Proxy::SIZE_THUMB)),
						'plink'                => ItemModel::getPlink($item),
						'edpost'               => false,
						'isstarred'            => 'unstarred',
						'star'                 => false,
						'drop'                 => $drop,
						'vote'                 => $likebuttons,
						'like_html'            => '',
						'dislike_html '        => '',
						'comment_html'         => '',
						'conv'                 => ($preview ? '' : ['href' => 'display/' . $item['guid'], 'title' => $this->l10n->t('View in context')]),
						'previewing'           => $previewing,
						'wait'                 => $this->l10n->t('Please wait'),
						'thread_level'         => 1,
					];

					$arr = ['item' => $item, 'output' => $tmp_item];
					Hook::callAll('display_item', $arr);

					$threads[$threadsid]['id']      = $item['id'];
					$threads[$threadsid]['network'] = $item['network'];
					$threads[$threadsid]['items']   = [$arr['output']];
				}
			} else {
				// Normal View
				$page_template = Renderer::getMarkupTemplate("threaded_conversation.tpl");

				$conv = new Thread($mode, $preview, $writable);

				/*
				* get all the topmost parents
				* this shouldn't be needed, as we should have only them in our array
				* But for now, this array respects the old style, just in case
				*/
				foreach ($items as $item) {
					if (in_array($item['author-id'], $blocklist)) {
						continue;
					}

					// Can we put this after the visibility check?
					$this->builtinActivityPuller($item, $conv_responses);

					// Only add what is visible
					if ($item['network'] === Protocol::MAIL && local_user() != $item['uid']) {
						continue;
					}

					if (!$this->item->visibleActivity($item)) {
						continue;
					}

					/// @todo Check if this call is needed or not
					$arr = ['item' => $item];
					Hook::callAll('display_item', $arr);

					$item['pagedrop'] = $page_dropping;

					if ($item['gravity'] == GRAVITY_PARENT) {
						$item_object = new PostObject($item);
						$conv->addParent($item_object);
					}
				}

				$threads = $conv->getTemplateData($conv_responses, $formSecurityToken);
				if (!$threads) {
					$this->logger->info('[ERROR] conversation : Failed to get template data.');
					$threads = [];
				}
			}
		}

		$o = Renderer::replaceMacros($page_template, [
			'$baseurl'     => $this->baseURL->get($ssl_state),
			'$return_path' => $this->args->getQueryString(),
			'$live_update' => $live_update_div,
			'$remove'      => $this->l10n->t('remove'),
			'$mode'        => $mode,
			'$update'      => $update,
			'$threads'     => $threads,
			'$dropping'    => ($page_dropping ? $this->l10n->t('Delete Selected Items') : false),
		]);

		$this->profiler->stopRecording();
		return $o;
	}

	private function getBlocklist()
	{
		if (!local_user()) {
			return [];
		}

		$str_blocked = str_replace(["\n", "\r"], ",", $this->pConfig->get(local_user(), 'system', 'blocked'));
		if (empty($str_blocked)) {
			return [];
		}

		$blocklist = [];

		foreach (explode(',', $str_blocked) as $entry) {
			$cid = Contact::getIdForURL(trim($entry), 0, false);
			if (!empty($cid)) {
				$blocklist[] = $cid;
			}
		}

		return $blocklist;
	}

	/**
	 * Adds some information (Causer, post reason, direction) to the fetched post row.
	 *
	 * @param array   $row      Post row
	 * @param array   $activity Contact data of the resharer
	 *
	 * @return array items with parents and comments
	 */
	private function addRowInformation(array $row, array $activity)
	{
		$this->profiler->startRecording('rendering');

		if ($row['uid'] == 0) {
			$row['writable'] = in_array($row['network'], Protocol::FEDERATED);
		}

		if (!empty($activity)) {
			if (($row['gravity'] == GRAVITY_PARENT)) {
				$row['post-reason'] = ItemModel::PR_ANNOUNCEMENT;

				$row     = array_merge($row, $activity);
				$contact = Contact::getById($activity['causer-id'], ['url', 'name', 'thumb']);

				$row['causer-link']   = $contact['url'];
				$row['causer-avatar'] = $contact['thumb'];
				$row['causer-name']   = $contact['name'];
			} elseif (($row['gravity'] == GRAVITY_ACTIVITY) && ($row['verb'] == Activity::ANNOUNCE) &&
				($row['author-id'] == $activity['causer-id'])) {
				return $row;
			}
		}

		switch ($row['post-reason']) {
			case ItemModel::PR_TO:
				$row['direction'] = ['direction' => 7, 'title' => $this->l10n->t('You had been addressed (%s).', 'to')];
				break;
			case ItemModel::PR_CC:
				$row['direction'] = ['direction' => 7, 'title' => $this->l10n->t('You had been addressed (%s).', 'cc')];
				break;
			case ItemModel::PR_BTO:
				$row['direction'] = ['direction' => 7, 'title' => $this->l10n->t('You had been addressed (%s).', 'bto')];
				break;
			case ItemModel::PR_BCC:
				$row['direction'] = ['direction' => 7, 'title' => $this->l10n->t('You had been addressed (%s).', 'bcc')];
				break;
			case ItemModel::PR_FOLLOWER:
				$row['direction'] = ['direction' => 6, 'title' => $this->l10n->t('You are following %s.', $row['author-name'])];
				break;
			case ItemModel::PR_TAG:
				$row['direction'] = ['direction' => 4, 'title' => $this->l10n->t('Tagged')];
				break;
			case ItemModel::PR_ANNOUNCEMENT:
				if (!empty($row['causer-id']) && $this->pConfig->get(local_user(), 'system', 'display_resharer')) {
					$row['owner-id']     = $row['causer-id'];
					$row['owner-link']   = $row['causer-link'];
					$row['owner-avatar'] = $row['causer-avatar'];
					$row['owner-name']   = $row['causer-name'];
				}

				if (($row['gravity'] == GRAVITY_PARENT) && !empty($row['causer-id'])) {
					$causer = ['uid' => 0, 'id' => $row['causer-id'], 'network' => $row['causer-network'], 'url' => $row['causer-link']];

					$row['reshared'] = $this->l10n->t('%s reshared this.', '<a href="'. htmlentities(Contact::magicLinkByContact($causer)) .'">' . htmlentities($row['causer-name']) . '</a>');
				}
				$row['direction'] = ['direction' => 3, 'title' => (empty($row['causer-id']) ? $this->l10n->t('Reshared') : $this->l10n->t('Reshared by %s <%s>', $row['causer-name'], $row['causer-link']))];
				break;
			case ItemModel::PR_COMMENT:
				$row['direction'] = ['direction' => 5, 'title' => $this->l10n->t('%s is participating in this thread.', $row['author-name'])];
				break;
			case ItemModel::PR_STORED:
				$row['direction'] = ['direction' => 8, 'title' => $this->l10n->t('Stored')];
				break;
			case ItemModel::PR_GLOBAL:
				$row['direction'] = ['direction' => 9, 'title' => $this->l10n->t('Global')];
				break;
			case ItemModel::PR_RELAY:
				$row['direction'] = ['direction' => 10, 'title' => (empty($row['causer-id']) ? $this->l10n->t('Relayed') : $this->l10n->t('Relayed by %s <%s>', $row['causer-name'], $row['causer-link']))];
				break;
			case ItemModel::PR_FETCHED:
				$row['direction'] = ['direction' => 2, 'title' => (empty($row['causer-id']) ? $this->l10n->t('Fetched') : $this->l10n->t('Fetched because of %s <%s>', $row['causer-name'], $row['causer-link']))];
				break;
		}

		$this->profiler->stopRecording();
		return $row;
	}

	/**
	 * Add comments to top level entries that had been fetched before
	 *
	 * The system will fetch the comments for the local user whenever possible.
	 * This behaviour is currently needed to allow commenting on Friendica posts.
	 *
	 * @param array $parents Parent items
	 *
	 * @param       $block_authors
	 * @param       $order
	 * @param       $uid
	 * @return array items with parents and comments
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private function addChildren(array $parents, $block_authors, $order, $uid)
	{
		$this->profiler->startRecording('rendering');
		if (count($parents) > 1) {
			$max_comments = $this->config->get('system', 'max_comments', 100);
		} else {
			$max_comments = $this->config->get('system', 'max_display_comments', 1000);
		}

		$params = ['order' => ['uri-id' => true, 'uid' => true]];

		$activities      = [];
		$uriids          = [];
		$commentcounter  = [];
		$activitycounter = [];

		foreach ($parents as $parent) {
			if (!empty($parent['thr-parent-id']) && !empty($parent['gravity']) && ($parent['gravity'] == GRAVITY_ACTIVITY)) {
				$uriid = $parent['thr-parent-id'];
				if (!empty($parent['author-id'])) {
					$activities[$uriid] = ['causer-id' => $parent['author-id']];
					foreach (['commented', 'received', 'created'] as $orderfields) {
						if (!empty($parent[$orderfields])) {
							$activities[$uriid][$orderfields] = $parent[$orderfields];
						}
					}
				}
			} else {
				$uriid = $parent['uri-id'];
			}
			$uriids[] = $uriid;

			$commentcounter[$uriid]  = 0;
			$activitycounter[$uriid] = 0;
		}

		$condition = ['parent-uri-id' => $uriids];
		if ($block_authors) {
			$condition['author-hidden'] = false;
		}

		$condition = DBA::mergeConditions($condition,
			["`uid` IN (0, ?) AND (`vid` != ? OR `vid` IS NULL)", $uid, Verb::getID(Activity::FOLLOW)]);

		$thread_items = Post::selectForUser(local_user(), array_merge(ItemModel::DISPLAY_FIELDLIST, ['pinned', 'contact-uid', 'gravity', 'post-type', 'post-reason']), $condition, $params);

		$items = [];

		while ($row = Post::fetch($thread_items)) {
			if (!empty($items[$row['uri-id']]) && ($row['uid'] == 0)) {
				continue;
			}

			if ($max_comments > 0) {
				if (($row['gravity'] == GRAVITY_COMMENT) && (++$commentcounter[$row['parent-uri-id']] > $max_comments)) {
					continue;
				}
				if (($row['gravity'] == GRAVITY_ACTIVITY) && (++$activitycounter[$row['parent-uri-id']] > $max_comments)) {
					continue;
				}
			}
			$items[$row['uri-id']] = $this->addRowInformation($row, $activities[$row['uri-id']] ?? []);
		}

		DBA::close($thread_items);

		$items = $this->convSort($items, $order);

		$this->profiler->stopRecording();
		return $items;
	}

	/**
	 * Plucks the children of the given parent from a given item list.
	 *
	 * @param array $item_list
	 * @param array $parent
	 * @param bool  $recursive
	 * @return array
	 */
	private function getItemChildren(array &$item_list, array $parent, $recursive = true)
	{
		$this->profiler->startRecording('rendering');
		$children = [];
		foreach ($item_list as $i => $item) {
			if ($item['gravity'] != GRAVITY_PARENT) {
				if ($recursive) {
					// Fallback to parent-uri if thr-parent is not set
					$thr_parent = $item['thr-parent-id'];
					if ($thr_parent == '') {
						$thr_parent = $item['parent-uri-id'];
					}

					if ($thr_parent == $parent['uri-id']) {
						$item['children'] = $this->getItemChildren($item_list, $item);

						$children[] = $item;
						unset($item_list[$i]);
					}
				} elseif ($item['parent-uri-id'] == $parent['uri-id']) {
					$children[] = $item;
					unset($item_list[$i]);
				}
			}
		}
		$this->profiler->stopRecording();
		return $children;
	}

	/**
	 * Recursively sorts a tree-like item array
	 *
	 * @param array $items
	 * @return array
	 */
	private function sortItemChildren(array $items)
	{
		$this->profiler->startRecording('rendering');
		$result = $items;
		usort($result, [$this, 'sortThrReceivedRev']);
		foreach ($result as $k => $i) {
			if (isset($result[$k]['children'])) {
				$result[$k]['children'] = $this->sortItemChildren($result[$k]['children']);
			}
		}
		$this->profiler->stopRecording();
		return $result;
	}

	/**
	 * Recursively add all children items at the top level of a list
	 *
	 * @param array $children List of items to append
	 * @param array $item_list
	 */
	private function addChildrenToList(array $children, array &$item_list)
	{
		foreach ($children as $child) {
			$item_list[] = $child;
			if (isset($child['children'])) {
				$this->addChildrenToList($child['children'], $item_list);
			}
		}
	}

	/**
	 * Selectively flattens a tree-like item structure to prevent threading stairs
	 *
	 * This recursive function takes the item tree structure created by conv_sort() and
	 * flatten the extraneous depth levels when people reply sequentially, removing the
	 * stairs effect in threaded conversations limiting the available content width.
	 *
	 * The basic principle is the following: if a post item has only one reply and is
	 * the last reply of its parent, then the reply is moved to the parent.
	 *
	 * This process is rendered somewhat more complicated because items can be either
	 * replies or likes, and these don't factor at all in the reply count/last reply.
	 *
	 * @param array $parent A tree-like array of items
	 * @return array
	 */
	private function smartFlattenConversation(array $parent)
	{
		$this->profiler->startRecording('rendering');
		if (!isset($parent['children']) || count($parent['children']) == 0) {
			$this->profiler->stopRecording();
			return $parent;
		}

		// We use a for loop to ensure we process the newly-moved items
		for ($i = 0; $i < count($parent['children']); $i++) {
			$child = $parent['children'][$i];

			if (isset($child['children']) && count($child['children'])) {
				// This helps counting only the regular posts
				$count_post_closure = function ($var) {
					$this->profiler->stopRecording();
					return $var['verb'] === Activity::POST;
				};

				$child_post_count = count(array_filter($child['children'], $count_post_closure));

				$remaining_post_count = count(array_filter(array_slice($parent['children'], $i), $count_post_closure));

				// If there's only one child's children post and this is the last child post
				if ($child_post_count == 1 && $remaining_post_count == 1) {

					// Searches the post item in the children
					$j = 0;
					while ($child['children'][$j]['verb'] !== Activity::POST && $j < count($child['children'])) {
						$j ++;
					}

					$moved_item = $child['children'][$j];
					unset($parent['children'][$i]['children'][$j]);
					$parent['children'][] = $moved_item;
				} else {
					$parent['children'][$i] = $this->smartFlattenConversation($child);
				}
			}
		}

		$this->profiler->stopRecording();
		return $parent;
	}

	/**
	 * Expands a flat list of items into corresponding tree-like conversation structures.
	 *
	 * sort the top-level posts either on "received" or "commented", and finally
	 * append all the items at the top level (???)
	 *
	 * @param array  $item_list A list of items belonging to one or more conversations
	 * @param string $order     Either on "received" or "commented"
	 * @return array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private function convSort(array $item_list, $order)
	{
		$this->profiler->startRecording('rendering');
		$parents = [];

		if (!(is_array($item_list) && count($item_list))) {
			$this->profiler->stopRecording();
			return $parents;
		}

		$blocklist = $this->getBlocklist();

		$item_array = [];

		// Dedupes the item list on the uri to prevent infinite loops
		foreach ($item_list as $item) {
			if (in_array($item['author-id'], $blocklist)) {
				continue;
			}

			$item_array[$item['uri-id']] = $item;
		}

		// Extract the top level items
		foreach ($item_array as $item) {
			if ($item['gravity'] == GRAVITY_PARENT) {
				$parents[] = $item;
			}
		}

		if (stristr($order, 'pinned_received')) {
			usort($parents, [$this, 'sortThrPinnedReceived']);
		} elseif (stristr($order, 'received')) {
			usort($parents, [$this, 'sortThrReceived']);
		} elseif (stristr($order, 'commented')) {
			usort($parents, [$this, 'sortThrCommented']);
		}

		/*
		* Plucks children from the item_array, second pass collects eventual orphan
		* items and add them as children of their top-level post.
		*/
		foreach ($parents as $i => $parent) {
			$parents[$i]['children'] = array_merge($this->getItemChildren($item_array, $parent, true),
				$this->getItemChildren($item_array, $parent, false));
		}

		foreach ($parents as $i => $parent) {
			$parents[$i]['children'] = $this->sortItemChildren($parents[$i]['children']);
		}

		if (!$this->pConfig->get(local_user(), 'system', 'no_smart_threading', 0)) {
			foreach ($parents as $i => $parent) {
				$parents[$i] = $this->smartFlattenConversation($parent);
			}
		}

		/// @TODO: Stop recusrsively adding all children back to the top level (!!!)
		/// However, this apparently ensures responses (likes, attendance) display (?!)
		foreach ($parents as $parent) {
			if (count($parent['children'])) {
				$this->addChildrenToList($parent['children'], $parents);
			}
		}

		$this->profiler->stopRecording();
		return $parents;
	}

	/**
	 * usort() callback to sort item arrays by pinned and the received key
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	private function sortThrPinnedReceived(array $a, array $b)
	{
		if ($b['pinned'] && !$a['pinned']) {
			return 1;
		} elseif (!$b['pinned'] && $a['pinned']) {
			return -1;
		}

		return strcmp($b['received'], $a['received']);
	}

	/**
	 * usort() callback to sort item arrays by the received key
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	private function sortThrReceived(array $a, array $b)
	{
		return strcmp($b['received'], $a['received']);
	}

	/**
	 * usort() callback to reverse sort item arrays by the received key
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	private function sortThrReceivedRev(array $a, array $b)
	{
		return strcmp($a['received'], $b['received']);
	}

	/**
	 * usort() callback to sort item arrays by the commented key
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	private function sortThrCommented(array $a, array $b)
	{
		return strcmp($b['commented'], $a['commented']);
	}
}
