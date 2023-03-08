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
use Friendica\Core\Session\Capability\IHandleUserSessions;
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
use Friendica\Util\Strings;
use Friendica\Util\Temporal;
use Psr\Log\LoggerInterface;

class Conversation
{
	const MODE_COMMUNITY     = 'community';
	const MODE_CONTACTS      = 'contacts';
	const MODE_CONTACT_POSTS = 'contact-posts';
	const MODE_DISPLAY       = 'display';
	const MODE_FILED         = 'filed';
	const MODE_NETWORK       = 'network';
	const MODE_NOTES         = 'notes';
	const MODE_SEARCH        = 'search';
	const MODE_PROFILE       = 'profile';

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
	/** @var IHandleUserSessions */
	private $session;

	public function __construct(LoggerInterface $logger, Profiler $profiler, Activity $activity, L10n $l10n, Item $item, Arguments $args, BaseURL $baseURL, IManageConfigValues $config, IManagePersonalConfigValues $pConfig, App\Page $page, App\Mode $mode, App $app, IHandleUserSessions $session)
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
		$this->session  = $session;
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
		$thread_parent = $activity['thr-parent-row'] ?? [];

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

			if (!empty($activity['verb']) && $this->activity->match($activity['verb'], $verb) && ($activity['gravity'] != ItemModel::GRAVITY_PARENT)) {
				$author = [
					'uid'     => 0,
					'id'      => $activity['author-id'],
					'network' => $activity['author-network'],
					'url'     => $activity['author-link']
				];
				$url = Contact::magicLinkByContact($author);
				if (strpos($url, 'contact/redir/') === 0) {
					$sparkle = ' class="sparkle" ';
				}

				$link = '<a href="' . $url . '"' . $sparkle . '>' . htmlentities($activity['author-name']) . '</a>';

				if (empty($activity['thr-parent-id'])) {
					$activity['thr-parent-id'] = $activity['parent-uri-id'];
				}

				// Skip when the causer of the parent is the same as the author of the announce
				if (($verb == Activity::ANNOUNCE) && !empty($thread_parent['causer-id']) && ($thread_parent['causer-id'] == $activity['author-id'])) {
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

				if ($this->session->getPublicContactId() == $activity['author-id']) {
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
	public function formatActivity(array $links, string $verb, int $id): string
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

			$spanatts = "class=\"btn btn-link fakelink\" onclick=\"openClose('{$verb}list-$id');\"";

			$explikers = '';
			switch ($verb) {
				case 'like':
					$phrase    = $this->l10n->t('<button type="button" %1$s>%2$d people</button> like this', $spanatts, $total);
					$explikers = $this->l10n->t('%s like this.', $likers);
					break;
				case 'dislike':
					$phrase    = $this->l10n->t('<button type="button" %1$s>%2$d people</button> don\'t like this', $spanatts, $total);
					$explikers = $this->l10n->t('%s don\'t like this.', $likers);
					break;
				case 'attendyes':
					$phrase    = $this->l10n->t('<button type="button" %1$s>%2$d people</button> attend', $spanatts, $total);
					$explikers = $this->l10n->t('%s attend.', $likers);
					break;
				case 'attendno':
					$phrase    = $this->l10n->t('<button type="button" %1$s>%2$d people</button> don\'t attend', $spanatts, $total);
					$explikers = $this->l10n->t('%s don\'t attend.', $likers);
					break;
				case 'attendmaybe':
					$phrase    = $this->l10n->t('<button type="button" %1$s>%2$d people</button> attend maybe', $spanatts, $total);
					$explikers = $this->l10n->t('%s attend maybe.', $likers);
					break;
				case 'announce':
					$phrase    = $this->l10n->t('<button type="button" %1$s>%2$d people</button> reshared this', $spanatts, $total);
					$explikers = $this->l10n->t('%s reshared this.', $likers);
					break;
			}

			$expanded .= "\t" . '<p class="wall-item-' . $verb . '-expanded" id="' . $verb . 'list-' . $id . '" style="display: none;" >' . $explikers . '</p>';
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

	public function statusEditor(array $x = [], int $notes_cid = 0, bool $popup = false): string
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
		$x['profile_uid']      = $x['profile_uid']      ?? $this->session->getLocalUserId();


		$geotag = !empty($x['allow_location']) ? Renderer::replaceMacros(Renderer::getMarkupTemplate('jot_geotag.tpl'), []) : '';

		$tpl = Renderer::getMarkupTemplate('jot-header.tpl');
		$this->page['htmlhead'] .= Renderer::replaceMacros($tpl, [
			'$newpost'   => 'true',
			'$baseurl'   => $this->baseURL,
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

		if ($this->config->get('system', 'set_creation_date')) {
			$created_at = Temporal::getDateTimeField(
				new \DateTime(DBA::NULL_DATETIME),
				new \DateTime('now'),
				null,
				$this->l10n->t('Created at'),
				'created_at'
			);
		} else {
			$created_at = '';
		}

		$tpl = Renderer::getMarkupTemplate('jot.tpl');

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
			'$placeholdercategory' => Feature::isEnabled($this->session->getLocalUserId(), 'categories') ? $this->l10n->t("Categories \x28comma-separated list\x29") : '',
			'$scheduled_at'        => Temporal::getDateTimeField(
				new \DateTime(),
				new \DateTime('now + 6 months'),
				null,
				$this->l10n->t('Scheduled at'),
				'scheduled_at'
			),
			'$created_at'   => $created_at,
			'$wait'         => $this->l10n->t('Please wait'),
			'$permset'      => $this->l10n->t('Permission settings'),
			'$shortpermset' => $this->l10n->t('Permissions'),
			'$wall'         => $notes_cid ? 0 : 1,
			'$posttype'     => $notes_cid ? ItemModel::PT_PERSONAL_NOTE : ItemModel::PT_ARTICLE,
			'$content'      => $x['content'] ?? '',
			'$post_id'      => $x['post_id'] ?? '',
			'$baseurl'      => $this->baseURL,
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

			'$compose_link_title'  => $this->l10n->t('Open Compose page'),
			'$always_open_compose' => $this->pConfig->get($this->session->getLocalUserId(), 'frio', 'always_open_compose', false),
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
	 * @param string $mode
	 * @param        $update @TODO Which type?
	 * @param bool   $preview
	 * @param string $order
	 * @param int    $uid
	 * @return string
	 * @throws ImagickException
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function create(array $items, string $mode, $update, bool $preview = false, string $order = 'commented', int $uid = 0): string
	{
		$this->profiler->startRecording('rendering');

		$this->page->registerFooterScript(Theme::getPathForFile('asset/typeahead.js/dist/typeahead.bundle.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.js'));
		$this->page->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.css'));
		$this->page->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput-typeahead.css'));

		$live_update_div = '';

		$blocklist = $this->getBlocklist();

		$previewing = (($preview) ? ' preview ' : '');

		if ($mode === self::MODE_NETWORK) {
			$items = $this->addChildren($items, false, $order, $uid, $mode);
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
		} elseif ($mode === self::MODE_PROFILE) {
			$items = $this->addChildren($items, false, $order, $uid, $mode);

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
		} elseif ($mode === self::MODE_NOTES) {
			$items = $this->addChildren($items, false, $order, $this->session->getLocalUserId(), $mode);

			if (!$update) {
				$live_update_div = '<div id="live-notes"></div>' . "\r\n"
					. "<script> var profile_uid = " . $this->session->getLocalUserId()
					. "; var netargs = '?f='; </script>\r\n";
			}
		} elseif ($mode === self::MODE_DISPLAY) {
			$items = $this->addChildren($items, false, $order, $uid, $mode);

			if (!$update) {
				$live_update_div = '<div id="live-display"></div>' . "\r\n"
					. "<script> var profile_uid = " . ($this->session->getLocalUserId() ?: 0) . ";"
					. "</script>";
			}
		} elseif ($mode === self::MODE_COMMUNITY) {
			$items = $this->addChildren($items, true, $order, $uid, $mode);

			if (!$update) {
				$live_update_div = '<div id="live-community"></div>' . "\r\n"
					. "<script> var profile_uid = -1; var netargs = '" . substr($this->args->getCommand(), 10)
					. '?f='
					. (!empty($_GET['no_sharer']) ? '&no_sharer=' . rawurlencode($_GET['no_sharer']) : '')
					. (!empty($_GET['accounttype']) ? '&accounttype=' . rawurlencode($_GET['accounttype']) : '')
					. "'; </script>\r\n";
			}
		} elseif ($mode === self::MODE_CONTACTS) {
			$items = $this->addChildren($items, false, $order, $uid, $mode);

			if (!$update) {
				$live_update_div = '<div id="live-contact"></div>' . "\r\n"
					. "<script> var profile_uid = -1; var netargs = '" . substr($this->args->getCommand(), 8)
					."?f='; </script>\r\n";
			}
		} elseif ($mode === self::MODE_SEARCH) {
			$live_update_div = '<div id="live-search"></div>' . "\r\n";
		}

		$page_dropping = $this->session->getLocalUserId() && $this->session->getLocalUserId() == $uid && $mode != self::MODE_SEARCH;

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

		if ($this->pConfig->get($this->session->getLocalUserId(), 'system', 'hide_dislike')) {
			unset($conv_responses['dislike']);
		}

		// array with html for each thread (parent+comments)
		$threads   = [];
		$threadsid = -1;

		$page_template     = Renderer::getMarkupTemplate("conversation.tpl");
		$formSecurityToken = BaseModule::getFormSecurityToken('contact_action');

		if (!empty($items)) {
			if (in_array($mode, [self::MODE_COMMUNITY, self::MODE_CONTACTS, self::MODE_PROFILE])) {
				$writable = true;
			} else {
				$writable = $items[0]['writable'] || ($items[0]['uid'] == 0) && in_array($items[0]['network'], Protocol::FEDERATED);
			}

			if (!$this->session->getLocalUserId()) {
				$writable = false;
			}

			if (in_array($mode, [self::MODE_FILED, self::MODE_SEARCH, self::MODE_CONTACT_POSTS])) {

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

					if (!$this->item->isVisibleActivity($item)) {
						continue;
					}

					if (in_array($item['author-id'], $blocklist)) {
						continue;
					}

					$threadsid++;

					// prevent private email from leaking.
					if ($item['network'] === Protocol::MAIL && $this->session->getLocalUserId() != $item['uid']) {
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
					if (strpos($profile_link, 'contact/redir/') === 0) {
						$sparkle = ' sparkle';
					}

					$locate = ['location' => $item['location'], 'coord' => $item['coord'], 'html' => ''];
					Hook::callAll('render_location', $locate);
					$location_html = $locate['html'] ?: Strings::escapeHtml($locate['location'] ?: $locate['coord'] ?: '');

					$this->item->localize($item);
					if ($mode === self::MODE_FILED) {
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

					if ($this->pConfig->get($this->session->getLocalUserId(), 'system', 'hide_dislike')) {
						unset($likebuttons['dislike']);
					}

					$body_html = ItemModel::prepareBody($item, true, $preview);

					[$categories, $folders] = $this->item->determineCategoriesTerms($item, $this->session->getLocalUserId());

					if (!empty($item['title'])) {
						$title = $item['title'];
					} elseif (!empty($item['content-warning']) && $this->pConfig->get($this->session->getLocalUserId(), 'system', 'disable_cw', false)) {
						$title = ucfirst($item['content-warning']);
					} else {
						$title = '';
					}

					if (!empty($item['featured'])) {
						$pinned = $this->l10n->t('Pinned item');
					} else {
						$pinned = '';
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
						'network_name'         => ContactSelector::networkToName($item['author-network'], $item['author-link'], $item['network'], $item['author-gsid']),
						'network_icon'         => ContactSelector::networkToIcon($item['network'], $item['author-link'], $item['author-gsid']),
						'linktitle'            => $this->l10n->t('View %s\'s profile @ %s', $profile_name, $item['author-link']),
						'profile_url'          => $profile_link,
						'item_photo_menu_html' => $this->item->photoMenu($item, $formSecurityToken),
						'name'                 => $profile_name,
						'sparkle'              => $sparkle,
						'lock'                 => false,
						'thumb'                => $this->baseURL->remove($this->item->getAuthorAvatar($item)),
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
						'owner_photo'          => $this->baseURL->remove($this->item->getOwnerAvatar($item)),
						'plink'                => ItemModel::getPlink($item),
						'edpost'               => false,
						'pinned'               => $pinned,
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
					if ($item['network'] === Protocol::MAIL && $this->session->getLocalUserId() != $item['uid']) {
						continue;
					}

					if (!$this->item->isVisibleActivity($item)) {
						continue;
					}

					/// @todo Check if this call is needed or not
					$arr = ['item' => $item];
					Hook::callAll('display_item', $arr);

					$item['pagedrop'] = $page_dropping;

					if ($item['gravity'] == ItemModel::GRAVITY_PARENT) {
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
			'$baseurl'     => $this->baseURL,
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

	private function getBlocklist(): array
	{
		if (!$this->session->getLocalUserId()) {
			return [];
		}

		$str_blocked = str_replace(["\n", "\r"], ",", $this->pConfig->get($this->session->getLocalUserId(), 'system', 'blocked') ?? '');
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
	 * @param array   $row        Post row
	 * @param array   $activity   Contact data of the resharer
	 * @param array   $thr_parent Thread parent row
	 *
	 * @return array items with parents and comments
	 */
	private function addRowInformation(array $row, array $activity, array $thr_parent): array
	{
		$this->profiler->startRecording('rendering');

		if (!$row['writable']) {
			$row['writable'] = in_array($row['network'], Protocol::FEDERATED);
		}

		if (!empty($activity)) {
			if (($row['gravity'] == ItemModel::GRAVITY_PARENT)) {
				$row['post-reason'] = ItemModel::PR_ANNOUNCEMENT;

				$row     = array_merge($row, $activity);
				$contact = Contact::getById($activity['causer-id'], ['url', 'name', 'thumb']);

				$row['causer-link']   = $contact['url'];
				$row['causer-avatar'] = $contact['thumb'];
				$row['causer-name']   = $contact['name'];
			} elseif (($row['gravity'] == ItemModel::GRAVITY_ACTIVITY) && ($row['verb'] == Activity::ANNOUNCE) &&
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
				$row['direction'] = ['direction' => 6, 'title' => $this->l10n->t('You are following %s.', $row['causer-name'] ?: $row['author-name'])];
				break;
			case ItemModel::PR_TAG:
				$row['direction'] = ['direction' => 4, 'title' => $this->l10n->t('You subscribed to one or more tags in this post.')];
				break;
			case ItemModel::PR_ANNOUNCEMENT:
				if (!empty($row['causer-id']) && $this->pConfig->get($this->session->getLocalUserId(), 'system', 'display_resharer')) {
					$row['owner-id']     = $row['causer-id'];
					$row['owner-link']   = $row['causer-link'];
					$row['owner-avatar'] = $row['causer-avatar'];
					$row['owner-name']   = $row['causer-name'];
				}

				if (in_array($row['gravity'], [ItemModel::GRAVITY_PARENT, ItemModel::GRAVITY_COMMENT]) && !empty($row['causer-id'])) {
					$causer = ['uid' => 0, 'id' => $row['causer-id'], 'network' => $row['causer-network'], 'url' => $row['causer-link']];

					$row['reshared'] = $this->l10n->t('%s reshared this.', '<a href="'. htmlentities(Contact::magicLinkByContact($causer)) .'">' . htmlentities($row['causer-name']) . '</a>');
				}
				$row['direction'] = ['direction' => 3, 'title' => (empty($row['causer-id']) ? $this->l10n->t('Reshared') : $this->l10n->t('Reshared by %s <%s>', $row['causer-name'], $row['causer-link']))];
				break;
			case ItemModel::PR_COMMENT:
				$row['direction'] = ['direction' => 5, 'title' => $this->l10n->t('%s is participating in this thread.', $row['author-name'])];
				break;
			case ItemModel::PR_STORED:
				$row['direction'] = ['direction' => 8, 'title' => $this->l10n->t('Stored for general reasons')];
				break;
			case ItemModel::PR_GLOBAL:
				$row['direction'] = ['direction' => 9, 'title' => $this->l10n->t('Global post')];
				break;
			case ItemModel::PR_RELAY:
				$row['direction'] = ['direction' => 10, 'title' => (empty($row['causer-id']) ? $this->l10n->t('Sent via an relay server') : $this->l10n->t('Sent via the relay server %s <%s>', $row['causer-name'], $row['causer-link']))];
				break;
			case ItemModel::PR_FETCHED:
				$row['direction'] = ['direction' => 2, 'title' => (empty($row['causer-id']) ? $this->l10n->t('Fetched') : $this->l10n->t('Fetched because of %s <%s>', $row['causer-name'], $row['causer-link']))];
				break;
			case ItemModel::PR_COMPLETION:
				$row['direction'] = ['direction' => 2, 'title' => $this->l10n->t('Stored because of a child post to complete this thread.')];
				break;
			case ItemModel::PR_DIRECT:
				$row['direction'] = ['direction' => 6, 'title' => $this->l10n->t('Local delivery')];
				break;
			case ItemModel::PR_ACTIVITY:
				$row['direction'] = ['direction' => 2, 'title' => $this->l10n->t('Stored because of your activity (like, comment, star, ...)')];
				break;
			case ItemModel::PR_DISTRIBUTE:
				$row['direction'] = ['direction' => 6, 'title' => $this->l10n->t('Distributed')];
				break;
			case ItemModel::PR_PUSHED:
				$row['direction'] = ['direction' => 1, 'title' => $this->l10n->t('Pushed to us')];
				break;
		}

		$row['thr-parent-row'] = $thr_parent;

		$this->profiler->stopRecording();
		return $row;
	}

	/**
	 * Add comments to top level entries that had been fetched before
	 *
	 * The system will fetch the comments for the local user whenever possible.
	 * This behaviour is currently needed to allow commenting on Friendica posts.
	 *
	 * @param array  $parents       Parent items
	 * @param bool   $block_authors
	 * @param bool   $order
	 * @param int    $uid
	 * @param string $mode
	 * @return array items with parents and comments
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private function addChildren(array $parents, bool $block_authors, string $order, int $uid, string $mode): array
	{
		$this->profiler->startRecording('rendering');
		if (count($parents) > 1) {
			$max_comments = $this->config->get('system', 'max_comments', 100);
		} else {
			$max_comments = $this->config->get('system', 'max_display_comments', 1000);
		}

		$activities      = [];
		$uriids          = [];
		$commentcounter  = [];
		$activitycounter = [];

		foreach ($parents as $parent) {
			if (!empty($parent['thr-parent-id']) && !empty($parent['gravity']) && ($parent['gravity'] == ItemModel::GRAVITY_ACTIVITY)) {
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

		if ($this->config->get('system', 'emoji_activities')) {
			$emojis = $this->getEmojis($uriids);
			$condition = DBA::mergeConditions($condition, ["(`gravity` != ? OR `origin`)", ItemModel::GRAVITY_ACTIVITY]);
		}

		$condition = DBA::mergeConditions($condition,
			["`uid` IN (0, ?) AND (NOT `vid` IN (?, ?, ?) OR `vid` IS NULL)", $uid, Verb::getID(Activity::FOLLOW), Verb::getID(Activity::VIEW), Verb::getID(Activity::READ)]);

		$condition = DBA::mergeConditions($condition,
			["`visible` AND NOT `deleted` AND NOT `author-blocked` AND NOT `owner-blocked`
			AND ((NOT `contact-pending` AND (`contact-rel` IN (?, ?))) OR `self` OR `contact-uid` = ?)",
			Contact::SHARING, Contact::FRIEND, 0]);

		$thread_parents = Post::select(['uri-id', 'causer-id'], $condition, ['order' => ['uri-id' => false, 'uid']]);

		$thr_parent = [];

		while ($row = Post::fetch($thread_parents)) {
			$thr_parent[$row['uri-id']] = $row;
		}
		DBA::close($thread_parents);

		$params = ['order' => ['uri-id' => true, 'uid' => true]];

		$thread_items = Post::select(array_merge(ItemModel::DISPLAY_FIELDLIST, ['featured', 'contact-uid', 'gravity', 'post-type', 'post-reason']), $condition, $params);

		$items         = [];
		$quote_uri_ids = [];
		$authors       = [];

		while ($row = Post::fetch($thread_items)) {
			if (!empty($items[$row['uri-id']]) && ($row['uid'] == 0)) {
				continue;
			}

			if (($mode != self::MODE_CONTACTS) && !$row['origin']) {
				$row['featured'] = false;
			}

			if ($max_comments > 0) {
				if (($row['gravity'] == ItemModel::GRAVITY_COMMENT) && (++$commentcounter[$row['parent-uri-id']] > $max_comments)) {
					continue;
				}
				if (($row['gravity'] == ItemModel::GRAVITY_ACTIVITY) && (++$activitycounter[$row['parent-uri-id']] > $max_comments)) {
					continue;
				}
			}

			$authors[] = $row['author-id'];
			$authors[] = $row['owner-id'];

			if (in_array($row['gravity'], [ItemModel::GRAVITY_PARENT, ItemModel::GRAVITY_COMMENT])) {
				$quote_uri_ids[$row['uri-id']] = [
					'uri-id'        => $row['uri-id'],
					'uri'           => $row['uri'],
					'parent-uri-id' => $row['parent-uri-id'],
					'parent-uri'    => $row['parent-uri'],
				];
			}

			$items[$row['uri-id']] = $this->addRowInformation($row, $activities[$row['uri-id']] ?? [], $thr_parent[$row['thr-parent-id']] ?? []);
		}

		DBA::close($thread_items);

		$quotes = Post::select(array_merge(ItemModel::DISPLAY_FIELDLIST, ['featured', 'contact-uid', 'gravity', 'post-type', 'post-reason']), ['quote-uri-id' => array_column($quote_uri_ids, 'uri-id'), 'body' => '', 'uid' => 0]);
		while ($quote = Post::fetch($quotes)) {
			$row = $quote;

			$row['uid']           = $uid;
			$row['verb']          = $row['body'] = $row['raw-body'] = Activity::ANNOUNCE;
			$row['gravity']       = ItemModel::GRAVITY_ACTIVITY;
			$row['object-type']   = Activity\ObjectType::NOTE;
			$row['parent-uri']    = $quote_uri_ids[$quote['quote-uri-id']]['parent-uri'];
			$row['parent-uri-id'] = $quote_uri_ids[$quote['quote-uri-id']]['parent-uri-id'];
			$row['thr-parent']    = $quote_uri_ids[$quote['quote-uri-id']]['uri'];
			$row['thr-parent-id'] = $quote_uri_ids[$quote['quote-uri-id']]['uri-id'];

			$authors[] = $row['author-id'];
			$authors[] = $row['owner-id'];

			$items[$row['uri-id']] = $this->addRowInformation($row, [], []);
		}
		DBA::close($quotes);

		$authors = array_unique($authors);

		$blocks    = [];
		$ignores   = [];
		$collapses = [];
		if (!empty($authors)) {
			$usercontacts = DBA::select('user-contact', ['cid', 'blocked', 'ignored', 'collapsed'], ['uid' => $uid, 'cid' => $authors]);
			while ($usercontact = DBA::fetch($usercontacts)) {
				if ($usercontact['blocked']) {
					$blocks[] = $usercontact['cid'];
				}
				if ($usercontact['ignored']) {
					$ignores[] = $usercontact['cid'];
				}
				if ($usercontact['collapsed']) {
					$collapses[] = $usercontact['cid'];
				}
			}
			DBA::close($usercontacts);
		}

		foreach ($items as $key => $row) {
			$items[$key]['emojis'] = $emojis[$key] ?? [];

			$always_display = in_array($mode, [self::MODE_CONTACTS, self::MODE_CONTACT_POSTS]);

			$items[$key]['user-blocked-author']   = !$always_display && in_array($row['author-id'], $blocks);
			$items[$key]['user-ignored-author']   = !$always_display && in_array($row['author-id'], $ignores);
			$items[$key]['user-blocked-owner']    = !$always_display && in_array($row['owner-id'], $blocks);
			$items[$key]['user-ignored-owner']    = !$always_display && in_array($row['owner-id'], $ignores);
			$items[$key]['user-collapsed-author'] = !$always_display && in_array($row['author-id'], $collapses);
			$items[$key]['user-collapsed-owner']  = !$always_display && in_array($row['owner-id'], $collapses);

			if (in_array($mode, [self::MODE_COMMUNITY, self::MODE_NETWORK]) &&
				(in_array($row['author-id'], $blocks) || in_array($row['owner-id'], $blocks) || in_array($row['author-id'], $ignores) || in_array($row['owner-id'], $ignores))) {
				unset($items[$key]);
			}
		}

		$items = $this->convSort($items, $order);

		$this->profiler->stopRecording();
		return $items;
	}

	/**
	 * Fetch emoji reaction from the conversation
	 *
	 * @param array $uriids
	 * @return array
	 */
	private function getEmojis(array $uriids): array
	{
		$activity_emoji = [
			Activity::LIKE        => '👍',
			Activity::DISLIKE     => '👎',
			Activity::ATTEND      => '✔️',
			Activity::ATTENDMAYBE => '❓',
			Activity::ATTENDNO    => '❌',
			Activity::ANNOUNCE    => '♻',
			Activity::VIEW        => '📺',
		];

		$index_list = array_values($activity_emoji);
		$verbs      = array_merge(array_keys($activity_emoji), [Activity::EMOJIREACT]);

		$condition = DBA::mergeConditions(['parent-uri-id' => $uriids, 'gravity' => ItemModel::GRAVITY_ACTIVITY, 'verb' => $verbs], ["NOT `deleted`"]);
		$separator = chr(255) . chr(255) . chr(255);

		$sql = "SELECT `thr-parent-id`, `body`, `verb`, COUNT(*) AS `total`, GROUP_CONCAT(REPLACE(`author-name`, '" . $separator . "', ' ') SEPARATOR '". $separator ."' LIMIT 50) AS `title` FROM `post-view` WHERE " . array_shift($condition) . " GROUP BY `thr-parent-id`, `verb`, `body`";

		$emojis = [];

		$rows = DBA::p($sql, $condition);
		while ($row = DBA::fetch($rows)) {
			$row['verb'] = $row['body'] ? Activity::EMOJIREACT : $row['verb'];
			$emoji       = $row['body'] ?: $activity_emoji[$row['verb']];
			if (!isset($index_list[$emoji])) {
				$index_list[] = $emoji;
			}
			$index = array_search($emoji, $index_list);

			$emojis[$row['thr-parent-id']][$index]['emoji'] = $emoji;
			$emojis[$row['thr-parent-id']][$index]['verb']  = $row['verb'];
			$emojis[$row['thr-parent-id']][$index]['total'] = ($emojis[$row['thr-parent-id']][$index]['total'] ?? 0) + $row['total'];
			$emojis[$row['thr-parent-id']][$index]['title'] = array_unique(array_merge($emojis[$row['thr-parent-id']][$index]['title'] ?? [], explode($separator, $row['title'])));
		}
		DBA::close($rows);

		return $emojis;
	}

	/**
	 * Plucks the children of the given parent from a given item list.
	 *
	 * @param array $item_list
	 * @param array $parent
	 * @param bool  $recursive
	 * @return array
	 */
	private function getItemChildren(array &$item_list, array $parent, bool $recursive = true): array
	{
		$this->profiler->startRecording('rendering');
		$children = [];
		foreach ($item_list as $i => $item) {
			if ($item['gravity'] != ItemModel::GRAVITY_PARENT) {
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
	private function sortItemChildren(array $items): array
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
	private function smartFlattenConversation(array $parent): array
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
	private function convSort(array $item_list, string $order): array
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
			if ($item['gravity'] == ItemModel::GRAVITY_PARENT) {
				$parents[] = $item;
			}
		}

		if (stristr($order, 'pinned_received')) {
			usort($parents, [$this, 'sortThrFeaturedReceived']);
		} elseif (stristr($order, 'pinned_commented')) {
			usort($parents, [$this, 'sortThrFeaturedCommented']);
		} elseif (stristr($order, 'received')) {
			usort($parents, [$this, 'sortThrReceived']);
		} elseif (stristr($order, 'commented')) {
			usort($parents, [$this, 'sortThrCommented']);
		} elseif (stristr($order, 'created')) {
			usort($parents, [$this, 'sortThrCreated']);
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

		if (!$this->pConfig->get($this->session->getLocalUserId(), 'system', 'no_smart_threading', 0)) {
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
	 * usort() callback to sort item arrays by featured and the received key
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	private function sortThrFeaturedReceived(array $a, array $b): int
	{
		if ($b['featured'] && !$a['featured']) {
			return 1;
		} elseif (!$b['featured'] && $a['featured']) {
			return -1;
		}

		return strcmp($b['received'], $a['received']);
	}

	/**
	 * usort() callback to sort item arrays by featured and the received key
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	private function sortThrFeaturedCommented(array $a, array $b): int
	{
		if ($b['featured'] && !$a['featured']) {
			return 1;
		} elseif (!$b['featured'] && $a['featured']) {
			return -1;
		}

		return strcmp($b['commented'], $a['commented']);
	}

	/**
	 * usort() callback to sort item arrays by the received key
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	private function sortThrReceived(array $a, array $b): int
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
	private function sortThrReceivedRev(array $a, array $b): int
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
	private function sortThrCommented(array $a, array $b): int
	{
		return strcmp($b['commented'], $a['commented']);
	}

	/**
	 * usort() callback to sort item arrays by the created key
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	private function sortThrCreated(array $a, array $b): int
	{
		return strcmp($b['created'], $a['created']);
	}
}
