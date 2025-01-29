<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object;

use Friendica\Content\ContactSelector;
use Friendica\Content\Feature;
use Friendica\Core\Addon;
use Friendica\Core\Hook;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\Item;
use Friendica\Model\Post as PostModel;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Protocol\Activity;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;
use Friendica\Util\Temporal;
use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;

/**
 * An item
 */
class Post
{
	private $data                = [];
	private $template            = null;
	private $available_templates = [
		'wall'      => 'wall_thread.tpl',
		'wall2wall' => 'wallwall_thread.tpl'
	];
	private $comment_box_template = 'comment_item.tpl';
	private $toplevel             = false;
	private $writable             = false;
	/**
	 * @var Post[]
	 */
	private $children = [];
	private $parent   = null;

	/**
	 * @var Thread
	 */
	private $thread       = null;
	private $redirect_url = null;
	private $owner_url    = '';
	private $owner_name   = '';
	private $wall_to_wall = false;
	private $threaded     = false;
	private $visiting     = false;

	/**
	 * Constructor
	 *
	 * @param array $data data array
	 * @throws \Exception
	 */
	public function __construct(array $data)
	{
		$this->data = $data;
		$this->setTemplate('wall');
		$this->toplevel = $this->getId() == $this->getDataValue('parent');

		if (!empty(DI::userSession()->getUserIDForVisitorContactID($this->getDataValue('contact-id')))) {
			$this->visiting = true;
		}

		$this->writable = $this->getDataValue('writable') || $this->getDataValue('self');
		$author         = [
			'uid'     => 0,
			'id'      => $this->getDataValue('author-id'),
			'network' => $this->getDataValue('author-network'),
			'url'     => $this->getDataValue('author-link'),
			'alias'   => $this->getDataValue('author-alias')
		];
		$this->redirect_url = Contact::magicLinkByContact($author);
		if (!$this->isToplevel()) {
			$this->threaded = true;
		}

		// Prepare the children
		if (!empty($data['children'])) {
			foreach ($data['children'] as $item) {
				// Only add will be displayed
				if ($item['network'] === Protocol::MAIL && DI::userSession()->getLocalUserId() != $item['uid']) {
					continue;
				} elseif (!DI::contentItem()->isVisibleActivity($item)) {
					continue;
				}

				// You can always comment on Diaspora items
				if (in_array($item['network'], [Protocol::DIASPORA]) && (DI::userSession()->getLocalUserId() == $item['uid'])) {
					$item['writable'] = true;
				}

				$item['pagedrop'] = $data['pagedrop'];
				$child            = new Post($item);
				$this->addChild($child);
			}
		}
	}

	/**
	 * Fetch the privacy of the post
	 *
	 * @param array $item Item record
	 * @return string Item privacy message
	 * @throws InvalidArgumentException If $item['private'] is unknown
	 */
	private function fetchPrivacy(array $item): string
	{
		switch ($item['private']) {
			case Item::PRIVATE:
				$output = DI::l10n()->t('Private Message');
				break;

			case Item::PUBLIC:
				$output = DI::l10n()->t('Public Message');
				break;

			case Item::UNLISTED:
				$output = DI::l10n()->t('Unlisted Message');
				break;

			default:
				throw new InvalidArgumentException('Item privacy ' . $item['privacy'] . ' is unsupported');
		}

		return $output;
	}

	/**
	 * Get data in a form usable by a conversation template
	 *
	 * @param array   $conv_responses    conversation responses
	 * @param string  $formSecurityToken A security Token to avoid CSF attacks
	 * @param integer $thread_level      default = 1
	 * @param array   $thread_parent     Array of parent guid and parent author names
	 *
	 * @return mixed The data requested on success, false on failure
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function getTemplateData(array $conv_responses, string $formSecurityToken, int $thread_level = 1, array $thread_parent = [])
	{
		$item   = $this->getData();
		$edited = false;

		/*
		 * If the time between "created" and "edited" differs we add
		 * a notice that the post was edited.
		 * Note: In some networks reshared items seem to have (sometimes) a difference
		 * between creation time and edit time of a second. Thats why we add the notice
		 * only if the difference is more than 1 second.
		 */
		if (strtotime($item['edited']) - strtotime($item['created']) > 1) {
			$edited = [
				'label'    => DI::l10n()->t('This entry was edited'),
				'date'     => DateTimeFormat::local($item['edited'], 'r'),
				'relative' => Temporal::getRelativeDate($item['edited']),
			];
		}
		$sparkle = '';
		$buttons = [
			'like'     => null,
			'dislike'  => null,
			'share'    => null,
			'announce' => null,
		];
		$dropping       = false;
		$pinned         = '';
		$pin            = false;
		$star           = false;
		$ignore_thread  = false;
		$ispinned       = 'unpinned';
		$isstarred      = 'unstarred';
		$indent         = '';
		$shiny          = '';
		$osparkle       = '';
		$total_children = $item['counts'] ?? $this->countDescendants();

		$conv = $this->getThread();

		$privacy   = $this->fetchPrivacy($item);
		$lock      = ($item['private'] == Item::PRIVATE) ? $privacy : false;
		$connector = !in_array($item['network'], Protocol::NATIVE_SUPPORT) && ($item['protocol'] != Conversation::PARCEL_JETSTREAM) ? DI::l10n()->t('Connector Message') : false;

		$shareable    = in_array($conv->getProfileOwner(), [0, DI::userSession()->getLocalUserId()]) && $item['private'] != Item::PRIVATE;
		$announceable = $shareable && in_array($item['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::TWITTER, Protocol::TUMBLR, Protocol::BLUESKY]);
		$commentable  = ($item['network'] != Protocol::TUMBLR);
		$likeable     = true;

		// On Diaspora only toplevel posts can be reshared
		if ($announceable && ($item['network'] == Protocol::DIASPORA) && ($item['gravity'] != Item::GRAVITY_PARENT)) {
			$announceable = false;
		}

		if ($item['restrictions'] & Item::CANT_REPLY) {
			$commentable = false;
		}

		if ($item['restrictions'] & Item::CANT_LIKE) {
			$likeable = false;
		}

		if ($item['restrictions'] & Item::CANT_ANNOUNCE) {
			$announceable = false;
		}

		$edpost = false;

		if (DI::userSession()->getLocalUserId()) {
			if (Strings::compareLink(DI::session()->get('my_url'), $item['author-link'])) {
				if ($item['event-id'] != 0) {
					$edpost = ['calendar/event/edit/' . $item['event-id'], DI::l10n()->t('Edit')];
				} else {
					$edpost = [sprintf('post/%s/edit', $item['id']), DI::l10n()->t('Edit')];
				}
			}
			$dropping = in_array($item['uid'], [0, DI::userSession()->getLocalUserId()]);
		}

		// Editing on items of not subscribed users isn't currently possible
		// There are some issues on editing that prevent this.
		// But also it is an issue of the supported protocols that doesn't allow editing at all.
		if ($item['uid'] == 0) {
			$edpost = false;
		}

		if (($this->getDataValue('uid') == DI::userSession()->getLocalUserId()) || $this->isVisiting()) {
			$dropping = true;
		}

		$origin = $item['origin'] || $item['parent-origin'];

		if (!empty($item['featured'])) {
			$pinned = DI::l10n()->t('Pinned item');
		}

		$drop         = false;
		$block        = false;
		$ignore       = false;
		$collapse     = false;
		$report       = false;
		$ignoreServer = false;
		if (DI::userSession()->getLocalUserId()) {
			$drop = [
				'dropping' => $dropping,
				'pagedrop' => $item['pagedrop'],
				'select'   => DI::l10n()->t('Select'),
				'label'    => $origin ? DI::l10n()->t('Delete globally') : DI::l10n()->t('Remove locally'),
			];
		}

		if (!$item['self'] && DI::userSession()->getLocalUserId()) {
			$block = [
				'blocking'  => true,
				'label'     => DI::l10n()->t('Block %s', $item['author-name']),
				'author_id' => $item['author-id'],
			];
			$ignore = [
				'ignoring'  => true,
				'label'     => DI::l10n()->t('Ignore %s', $item['author-name']),
				'author_id' => $item['author-id'],
			];
			$collapse = [
				'collapsing' => true,
				'label'      => DI::l10n()->t('Collapse %s', $item['author-name']),
				'author_id'  => $item['author-id'],
			];
			$report = [
				'label' => DI::l10n()->t('Report post'),
				'href'  => 'moderation/report/create?' . http_build_query(['cid' => $item['author-id'], 'uri-ids' => [$item['uri-id']]]),
			];
			$authorBaseUri = new Uri($item['author-baseurl'] ?? '');
			if ($authorBaseUri->getHost() && !DI::baseUrl()->isLocalUrl($authorBaseUri)) {
				$ignoreServer = [
					'label' => DI::l10n()->t("Ignore %s server", $authorBaseUri->getHost()),
				];
			}
		}

		$filer = DI::userSession()->getLocalUserId() ? DI::l10n()->t('Save to folder') : false;

		$profile_name = $item['author-name'];
		if (!empty($item['author-link']) && empty($item['author-name'])) {
			$profile_name = $item['author-link'];
		}

		if (DI::userSession()->isAuthenticated()) {
			$author = [
				'uid'     => 0,
				'id'      => $item['author-id'],
				'network' => $item['author-network'],
				'url'     => $item['author-link'],
				'alias'   => $item['author-alias'],
			];
			$profile_link = Contact::magicLinkByContact($author);
		} else {
			$profile_link = $item['author-link'];
		}

		if (strpos($profile_link, 'contact/redir/') === 0) {
			$sparkle = ' sparkle';
		}

		$locate = ['location' => $item['location'], 'coord' => $item['coord'], 'html' => ''];
		Hook::callAll('render_location', $locate);
		$location_html = $locate['html'] ?: Strings::escapeHtml($locate['location'] ?: $locate['coord'] ?: '');

		// process action responses - e.g. like/dislike/attend/agree/whatever
		$response_verbs = ['like', 'dislike', 'announce', 'comment'];

		$isevent = false;
		$attend  = [];
		if ($item['object-type'] === Activity\ObjectType::EVENT) {
			$response_verbs[] = 'attendyes';
			$response_verbs[] = 'attendno';
			$response_verbs[] = 'attendmaybe';

			if ($conv->isWritable()) {
				$isevent = true;
				$attend  = [DI::l10n()->t('I will attend'), DI::l10n()->t('I will not attend'), DI::l10n()->t('I might attend')];
			}
		}

		$emojis = $this->getEmojis($item);

		$verbs = [
			'like'        => Activity::LIKE,
			'dislike'     => Activity::DISLIKE,
			'announce'    => Activity::ANNOUNCE,
			'comment'     => Activity::POST,
			'attendyes'   => Activity::ATTEND,
			'attendno'    => Activity::ATTENDNO,
			'attendmaybe' => Activity::ATTENDMAYBE,
		];
		$reactions = $emojis;
		$responses = [];
		foreach ($response_verbs as $value => $verb) {
			$responses[$verb] = [
				'self'   => $conv_responses[$verb][$item['uri-id']]['self'] ?? 0,
				'output' => !empty($conv_responses[$verb][$item['uri-id']]) ? DI::conversation()->formatActivity($conv_responses[$verb][$item['uri-id']]['links'], $verb, $item['uri-id'], $verbs[$verb], $emojis) : '',
				'total'  => $emojis[$verbs[$verb]]['total'] ?? '',
				'title'  => $emojis[$verbs[$verb]]['title'] ?? '',
			];
			unset($reactions[$verbs[$verb]]);
		}

		unset($emojis[Activity::POST]);

		/*
		 * We should avoid doing this all the time, but it depends on the conversation mode
		 * And the conv mode may change when we change the conv, or it changes its mode
		 * Maybe we should establish a way to be notified about conversation changes
		 */
		$this->checkWallToWall();

		if ($this->isWallToWall() && ($this->getOwnerUrl() == $this->getRedirectUrl())) {
			$osparkle = ' sparkle';
		}

		$tagger = '';

		if ($this->isToplevel()) {
			if (DI::userSession()->getLocalUserId()) {
				$ignored_thread = PostModel\ThreadUser::getIgnored($item['uri-id'], DI::userSession()->getLocalUserId());
				if ($item['mention'] || $ignored_thread) {
					$ignore_thread = [
						'do'        => DI::l10n()->t('Ignore thread'),
						'undo'      => DI::l10n()->t('Unignore thread'),
						'toggle'    => DI::l10n()->t('Toggle ignore status'),
						'classdo'   => $ignored_thread ? 'hidden' : '',
						'classundo' => $ignored_thread ? '' : 'hidden',
						'ignored'   => DI::l10n()->t('Ignored'),
					];
				}

				$isstarred = (($item['starred']) ? 'starred' : 'unstarred');

				$star = [
					'do'        => DI::l10n()->t('Add star'),
					'undo'      => DI::l10n()->t('Remove star'),
					'toggle'    => DI::l10n()->t('Toggle star status'),
					'classdo'   => $item['starred'] ? 'hidden' : '',
					'classundo' => $item['starred'] ? '' : 'hidden',
					'starred'   => DI::l10n()->t('Starred'),
				];

				if ($conv->getProfileOwner() == DI::userSession()->getLocalUserId() && ($item['uid'] != 0)) {
					if ($origin && in_array($item['private'], [Item::PUBLIC, Item::UNLISTED])) {
						$ispinned = ($item['featured'] ? 'pinned' : 'unpinned');

						$pin = [
							'do'        => DI::l10n()->t('Pin'),
							'undo'      => DI::l10n()->t('Unpin'),
							'toggle'    => DI::l10n()->t('Toggle pin status'),
							'classdo'   => $item['featured'] ? 'hidden' : '',
							'classundo' => $item['featured'] ? '' : 'hidden',
							'pinned'    => DI::l10n()->t('Pinned'),
						];
					}

					$tagger = [
						'add'   => DI::l10n()->t('Add tag'),
						'class' => '',
					];
				}
			}
		} else {
			$indent = 'comment';
		}

		if ($conv->isWritable()) {
			if ($likeable) {
				$buttons['like']    = [DI::l10n()->t("I like this \x28toggle\x29"), DI::l10n()->t('Like')];
				$buttons['dislike'] = [DI::l10n()->t("I don't like this \x28toggle\x29"), DI::l10n()->t('Dislike')];
			}
			if ($shareable) {
				$buttons['share'] = [DI::l10n()->t('Quote share this'), DI::l10n()->t('Quote Share')];
			}
			if ($announceable) {
				$buttons['announce']   = [DI::l10n()->t('Reshare this'), DI::l10n()->t('Reshare')];
				$buttons['unannounce'] = [DI::l10n()->t('Cancel your Reshare'), DI::l10n()->t('Unshare')];
			}
		}

		if ($commentable) {
			$comment_html = $this->getCommentBox($indent);
		} else {
			$comment_html = '';
		}

		if (strcmp(DateTimeFormat::utc($item['created']), DateTimeFormat::utc('now - 12 hours')) > 0) {
			$shiny = 'shiny';
		}

		DI::contentItem()->localize($item);

		$body_html = Item::prepareBody($item, true);

		list($categories, $folders) = DI::contentItem()->determineCategoriesTerms($item, DI::userSession()->getLocalUserId());

		$hide_dislike = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'hide_dislike');
		if ($hide_dislike) {
			$buttons['dislike'] = false;
		}

		// Disable features that aren't available in several networks
		if (!in_array($item['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA])) {
			if ($buttons['dislike']) {
				$buttons['dislike'] = false;
			}

			$isevent = false;
			$tagger  = '';
		}

		if ($buttons['like'] && in_array($item['network'], [Protocol::FEED, Protocol::MAIL])) {
			$buttons['like'] = false;
		}

		$tags = Tag::populateFromItem($item);

		$ago          = Temporal::getRelativeDate($item['created']);
		$ago_received = Temporal::getRelativeDate($item['received']);
		if (DI::config()->get('system', 'show_received') && (abs(strtotime($item['created']) - strtotime($item['received'])) > DI::config()->get('system', 'show_received_seconds')) && ($ago != $ago_received)) {
			$ago = DI::l10n()->t('%s (Received %s)', $ago, $ago_received);
		}

		// Fetching of Diaspora posts doesn't always work. There are issues with reshares and possibly comments
		if (!DI::userSession()->getLocalUserId() && ($item['network'] != Protocol::DIASPORA) && !empty(DI::session()->get('remote_comment'))) {
			$remote_comment = [
				DI::l10n()->t('Comment this item on your system'), DI::l10n()->t('Remote comment'),
				str_replace('{uri}', urlencode($item['uri']), DI::session()->get('remote_comment'))
			];

			// Ensure to either display the remote comment or the local activities
			$buttons      = [];
			$comment_html = '';
		} else {
			$remote_comment = '';
		}

		$direction = [];
		if (!empty($item['direction'])) {
			$direction = $item['direction'];
		}

		$languages = [];
		$language  = '';
		if (!empty($item['language'])) {
			$languages = DI::l10n()->t('Languages');
			$language  = array_key_first(json_decode($item['language'], true));
		}

		if (in_array($item['private'], [Item::PUBLIC, Item::UNLISTED]) && in_array($item['network'], Protocol::FEDERATED)) {
			$browsershare = [DI::l10n()->t('Share via ...'), DI::l10n()->t('Share via external services')];
		} else {
			$browsershare = null;
		}

		$parent_guid     = $thread_parent[$item['thr-parent-id']]['guid'] ?? '';
		$parent_username = $thread_parent[$item['thr-parent-id']]['name'] ?? '';
		$parent_unknown  = $parent_username ? '' : DI::l10n()->t('Unknown parent');

		$tmp_item = [
			'parentguid'             => $parent_guid,
			'inreplyto'              => DI::l10n()->t('in reply to %s', $parent_username),
			'isunknown'              => $parent_unknown,
			'isunknown_label'        => DI::l10n()->t('Parent is probably private or not federated.'),
			'template'               => $this->getTemplate(),
			'type'                   => implode('', array_slice(explode('/', $item['verb']), -1)),
			'comment_firstcollapsed' => false,
			'comment_lastcollapsed'  => false,
			'suppress_tags'          => DI::config()->get('system', 'suppress_tags'),
			'tags'                   => $tags['tags'],
			'hashtags'               => $tags['hashtags'],
			'mentions'               => $tags['mentions'],
			'implicit_mentions'      => $tags['implicit_mentions'],
			'txt_cats'               => DI::l10n()->t('Categories:'),
			'txt_folders'            => DI::l10n()->t('Filed under:'),
			'has_cats'               => ((count($categories)) ? 'true' : ''),
			'has_folders'            => ((count($folders)) ? 'true' : ''),
			'categories'             => $categories,
			'folders'                => $folders,
			'body_html'              => $body_html,
			'text'                   => strip_tags($body_html),
			'id'                     => $this->getId(),
			'guid'                   => urlencode($item['guid']),
			'isevent'                => $isevent,
			'attend'                 => $attend,
			'linktitle'              => DI::l10n()->t('View %s\'s profile @ %s', $profile_name, $item['author-link']),
			'olinktitle'             => DI::l10n()->t('View %s\'s profile @ %s', $this->getOwnerName(), $item['owner-link']),
			'to'                     => DI::l10n()->t('to'),
			'via'                    => DI::l10n()->t('via'),
			'wall'                   => DI::l10n()->t('Wall-to-Wall'),
			'vwall'                  => DI::l10n()->t('via Wall-To-Wall:'),
			'profile_url'            => $profile_link,
			'name'                   => $profile_name,
			'item_photo_menu_html'   => DI::contentItem()->photoMenu($item, $formSecurityToken),
			'thumb'                  => DI::baseUrl()->remove(DI::contentItem()->getAuthorAvatar($item)),
			'osparkle'               => $osparkle,
			'sparkle'                => $sparkle,
			'title'                  => $item['title'],
			'summary'                => $item['content-warning'],
			'localtime'              => DateTimeFormat::local($item['created'], 'r'),
			'ago'                    => $item['app'] ? DI::l10n()->t('%s from %s', $ago, $item['app']) : $ago,
			'app'                    => $item['app'],
			'created'                => $ago,
			'lock'                   => $lock,
			'private'                => $item['private'],
			'privacy'                => $privacy,
			'connector'              => $connector,
			'location_html'          => $location_html,
			'indent'                 => $indent,
			'shiny'                  => $shiny,
			'owner_self'             => $item['author-link'] == DI::session()->get('my_url'),
			'owner_url'              => $this->getOwnerUrl(),
			'owner_photo'            => DI::baseUrl()->remove(DI::contentItem()->getOwnerAvatar($item)),
			'owner_name'             => $this->getOwnerName(),
			'plink'                  => Item::getPlink($item),
			'browsershare'           => $browsershare,
			'edpost'                 => $edpost,
			'ispinned'               => $ispinned,
			'pin'                    => $pin,
			'pinned'                 => $pinned,
			'isstarred'              => $isstarred,
			'star'                   => $star,
			'ignore'                 => $ignore_thread,
			'tagger'                 => $tagger,
			'filer'                  => $filer,
			'language'               => $languages,
			'lang'                   => $language,
			'searchtext'             => DI::l10n()->t('Search Text'),
			'drop'                   => $drop,
			'block'                  => $block,
			'ignore_author'          => $ignore,
			'collapse'               => $collapse,
			'report'                 => $report,
			'ignore_server'          => $ignoreServer,
			'vote'                   => $buttons,
			'like_html'              => $responses['like']['output'],
			'dislike_html'           => $responses['dislike']['output'],
			'hide_dislike'           => $hide_dislike,
			'emojis'                 => $emojis,
			'quoteshares'            => $this->getQuoteShares($item['quoteshares']),
			'reactions'              => $reactions,
			'responses'              => $responses,
			'legacy_activities'      => DI::config()->get('system', 'legacy_activities'),
			'switchcomment'          => DI::l10n()->t('Comment'),
			'reply_label'            => DI::l10n()->t('Reply to %s', $profile_name),
			'comment_html'           => $comment_html,
			'remote_comment'         => $remote_comment,
			'menu'                   => DI::l10n()->t('More'),
			'previewing'             => $conv->isPreview() ? ' preview ' : '',
			'wait'                   => DI::l10n()->t('Please wait'),
			'thread_level'           => $thread_level,
			'edited'                 => $edited,
			'author_gsid'            => $item['author-gsid'],
			'network'                => $item['network'],
			'network_name'           => ContactSelector::networkToName($item['author-network'], $item['network'], $item['author-gsid']),
			'network_svg'            => ContactSelector::networkToSVG($item['network'], $item['author-gsid'], '', DI::userSession()->getLocalUserId()),
			'received'               => $item['received'],
			'commented'              => $item['commented'],
			'created_date'           => $item['created'],
			'uriid'                  => $item['uri-id'],
			'return'                 => (DI::args()->getCommand()) ? bin2hex(DI::args()->getCommand()) : '',
			'direction'              => $direction,
			'reshared'               => $item['reshared'] ?? '',
			'delivery'               => [
				'queue_count'       => $item['delivery_queue_count'],
				'queue_done'        => $item['delivery_queue_done'] + $item['delivery_queue_failed'], /// @todo Possibly display it separately in the future
				'notifier_pending'  => DI::l10n()->t('Notifier task is pending'),
				'delivery_pending'  => DI::l10n()->t('Delivery to remote servers is pending'),
				'delivery_underway' => DI::l10n()->t('Delivery to remote servers is underway'),
				'delivery_almost'   => DI::l10n()->t('Delivery to remote servers is mostly done'),
				'delivery_done'     => DI::l10n()->t('Delivery to remote servers is done'),
			],
		];

		$arr = ['item' => $item, 'output' => $tmp_item];
		Hook::callAll('display_item', $arr);

		$result = $arr['output'];

		$result['children'] = [];
		$children           = $this->getChildren();
		$nb_children        = count($children);
		if ($nb_children > 0) {
			$thread_parent[$item['uri-id']] = ['guid' => $item['guid'], 'name' => $item['author-name']];
			foreach ($children as $child) {
				$thread_parent[$child->getDataValue('uri-id')] = ['guid' => $child->getDataValue('guid'), 'name' => $child->getDataValue('author-name')];
				$result['children'][]                          = $child->getTemplateData($conv_responses, $formSecurityToken, $thread_level + 1, $thread_parent);
			}

			// Collapse
			if (($nb_children > 2) || ($thread_level > 1)) {
				$result['children'][0]['comment_firstcollapsed'] = true;
				$result['children'][0]['num_comments']           = DI::l10n()->tt('%d comment', '%d comments', $total_children);
				$result['children'][0]['show_text']              = DI::l10n()->t('Show more');
				$result['children'][0]['hide_text']              = DI::l10n()->t('Show fewer');
				if ($thread_level > 1) {
					$result['children'][$nb_children - 1]['comment_lastcollapsed'] = true;
				} else {
					$result['children'][$nb_children - 3]['comment_lastcollapsed'] = true;
				}
			}
		}

		// Copy values/set defaults
		$result['total_comments_num'] = $this->isToplevel() ? $total_children : 0;
		$result['private']            = $item['private'];
		$result['toplevel']           = ($this->isToplevel() ? 'toplevel_item' : '');
		$result['flatten']            = !$this->isThreaded();
		$result['threaded']           = $this->isThreaded();

		return $result;
	}

	/**
	 * Fetch emojis
	 *
	 * @param array $item
	 * @return array
	 */
	private function getEmojis(array $item): array
	{
		if (empty($item['emojis'])) {
			return [];
		}

		$emojis = [];
		foreach ($item['emojis'] as $index => $element) {
			$key    = $element['verb'];
			$actors = implode(', ', $element['title']);
			switch ($element['verb']) {
				case Activity::ANNOUNCE:
					$title = DI::l10n()->t('Reshared by: %s', $actors);
					$icon  = ['fa' => 'fa-retweet', 'icon' => 'icon-retweet'];
					break;

				case Activity::VIEW:
					$title = DI::l10n()->t('Viewed by: %s', $actors);
					$icon  = ['fa' => 'fa-eye', 'icon' => 'icon-eye-open'];
					break;

				case Activity::READ:
					$title = DI::l10n()->t('Read by: %s', $actors);
					$icon  = ['fa' => 'fa-book', 'icon' => 'icon-book'];
					break;

				case Activity::LIKE:
					$title = DI::l10n()->t('Liked by: %s', $actors);
					$icon  = ['fa' => 'fa-thumbs-up', 'icon' => 'icon-thumbs-up'];
					break;

				case Activity::DISLIKE:
					$title = DI::l10n()->t('Disliked by: %s', $actors);
					$icon  = ['fa' => 'fa-thumbs-down', 'icon' => 'icon-thumbs-down'];
					break;

				case Activity::ATTEND:
					$title = DI::l10n()->t('Attended by: %s', $actors);
					$icon  = ['fa' => 'fa-check', 'icon' => 'icon-ok'];
					break;

				case Activity::ATTENDMAYBE:
					$title = DI::l10n()->t('Maybe attended by: %s', $actors);
					$icon  = ['fa' => 'fa-question', 'icon' => 'icon-question'];
					break;

				case Activity::ATTENDNO:
					$title = DI::l10n()->t('Not attended by: %s', $actors);
					$icon  = ['fa' => 'fa-times', 'icon' => 'icon-remove'];
					break;

				case Activity::POST:
					$title = DI::l10n()->t('Commented by: %s', $actors);
					$icon  = ['fa' => 'fa-commenting', 'icon' => 'icon-commenting'];
					break;

				default:
					$title = DI::l10n()->t('Reacted with %s by: %s', $element['emoji'], $actors);
					$icon  = [];
					$key   = $element['emoji'];
					break;
			}
			$emojis[$key] = ['emoji' => $element['emoji'], 'total' => $element['total'], 'title' => $title, 'icon' => $icon];
		}

		return $emojis;
	}

	/**
	 * Fetch quote shares
	 *
	 * @param array $quoteshares
	 * @return array
	 */
	private function getQuoteShares($quoteshares)
	{
		if (empty($quoteshares)) {
			return [];
		}

		return ['total' => $quoteshares['total'], 'title' => DI::l10n()->t('Quote shared by: %s', implode(', ', $quoteshares['title']))];
	}

	/**
	 * @return integer
	 */
	public function getId(): int
	{
		return $this->getDataValue('id');
	}

	/**
	 * @return boolean
	 */
	public function isThreaded(): bool
	{
		return $this->threaded;
	}

	/**
	 * Add a child post
	 *
	 * @param Post $item The child post to add
	 *
	 * @return Post|bool Last Post object or bool on any error
	 * @throws \Exception
	 */
	public function addChild(Post $item)
	{
		if (!$item->getId()) {
			DI::logger()->error('Post object has no id', ['post' => $item]);
			return false;
		} elseif ($this->getChild($item->getId())) {
			DI::logger()->warning('Post object already exists', ['post' => $item]);
			return false;
		}

		/*
		 * Only add what will be displayed
		 */
		if ($item->getDataValue('network') === Protocol::MAIL && DI::userSession()->getLocalUserId() != $item->getDataValue('uid')) {
			DI::logger()->warning('Post object does not belong to local user', ['post' => $item, 'local_user' => DI::userSession()->getLocalUserId()]);
			return false;
		} elseif (
			DI::activity()->match($item->getDataValue('verb'), Activity::LIKE) ||
			DI::activity()->match($item->getDataValue('verb'), Activity::DISLIKE)
		) {
			DI::logger()->warning('Post objects is a like/dislike', ['post' => $item]);
			return false;
		}

		$item->setParent($this);
		$this->children[] = $item;

		return end($this->children);
	}

	/**
	 * Get a child by its ID
	 *
	 * @param integer $id The child id
	 * @return Thread|null Thread or NULL if not found
	 */
	public function getChild(int $id)
	{
		foreach ($this->getChildren() as $child) {
			if ($child->getId() == $id) {
				return $child;
			}
		}

		return null;
	}

	/**
	 * Get all our children
	 *
	 * @return Post[]
	 */
	public function getChildren(): array
	{
		return $this->children;
	}

	/**
	 * Set our parent
	 *
	 * @param Post $item The item to set as parent
	 * @return void
	 */
	protected function setParent(Post $item)
	{
		$parent = $this->getParent();
		if ($parent) {
			$parent->removeChild($this);
		}

		$this->parent = $item;
		$this->setThread($item->getThread());
	}

	/**
	 * Remove our parent
	 *
	 * @return void
	 */
	protected function removeParent()
	{
		$this->parent = null;
		$this->thread = null;
	}

	/**
	 * Remove a child
	 *
	 * @param Post $item The child to be removed
	 * @return boolean Success or failure
	 * @throws \Exception
	 */
	public function removeChild(Post $item): bool
	{
		$id = $item->getId();
		foreach ($this->getChildren() as $key => $child) {
			if ($child->getId() == $id) {
				$child->removeParent();
				unset($this->children[$key]);
				// Reindex the array, in order to make sure there won't be any trouble on loops using count()
				$this->children = array_values($this->children);
				return true;
			}
		}

		DI::logger()->info('[WARN] Item::removeChild : Item is not a child (' . $id . ').');
		return false;
	}

	/**
	 * Get parent item
	 *
	 * @return object
	 */
	protected function getParent()
	{
		return $this->parent;
	}

	/**
	 * Set conversation thread
	 *
	 * @param Thread|null $thread
	 *
	 * @return void
	 */
	public function setThread(Thread $thread = null)
	{
		$this->thread = $thread;

		// Set it on our children too
		foreach ($this->getChildren() as $child) {
			$child->setThread($thread);
		}
	}

	/**
	 * Get conversation
	 *
	 * @return Thread|null
	 */
	public function getThread()
	{
		return $this->thread;
	}

	/**
	 * Get raw data
	 *
	 * We shouldn't need this
	 *
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}

	/**
	 * Get a data value
	 *
	 * @param string $name key
	 *
	 * @return mixed value on success, false on failure
	 */
	public function getDataValue(string $name)
	{
		if (!isset($this->data[$name])) {
			// DI::logger()->info('[ERROR] Item::getDataValue : Item has no value name "'. $name .'".');
			return false;
		}

		return $this->data[$name];
	}

	/**
	 * Set template by name
	 *
	 * @param string $name Template name
	 *
	 * @return void
	 * @throws InvalidArgumentException
	 */
	private function setTemplate(string $name)
	{
		if (empty($this->available_templates[$name])) {
			// Throw exception
			throw new InvalidArgumentException('[ERROR] Item::setTemplate : Template not available ("' . $name . '").');
		}

		$this->template = $this->available_templates[$name];
	}

	/**
	 * Get template
	 *
	 * @return object
	 */
	private function getTemplate()
	{
		return $this->template;
	}

	/**
	 * Check if this is a toplevel post
	 *
	 * @return boolean
	 */
	private function isToplevel(): bool
	{
		return $this->toplevel;
	}

	/**
	 * Check if this is writable
	 *
	 * @return boolean
	 */
	private function isWritable(): bool
	{
		$conv = $this->getThread();

		if ($conv) {
			// This will allow us to comment on wall-to-wall items owned by our friends
			// and community groups even if somebody else wrote the post.
			// bug #517 - this fixes for conversation owner
			if ($conv->getMode() == 'profile' && $conv->getProfileOwner() == DI::userSession()->getLocalUserId()) {
				return true;
			}

			// this fixes for visitors
			return ($this->writable || ($this->isVisiting() && $conv->getMode() == 'profile'));
		}
		return $this->writable;
	}

	/**
	 * Count the total of our descendants
	 *
	 * @return integer
	 */
	private function countDescendants(): int
	{
		$children = $this->getChildren();
		$total    = count($children);
		if ($total > 0) {
			foreach ($children as $child) {
				$total += $child->countDescendants();
			}
		}

		return $total;
	}

	/**
	 * Get the template for the comment box
	 *
	 * @return string
	 */
	private function getCommentBoxTemplate(): string
	{
		return $this->comment_box_template;
	}

	/**
	 * Get default text for the comment box
	 *
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private function getDefaultText(): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			return '';
		}

		$owner = User::getOwnerDataById(DI::userSession()->getLocalUserId());
		$item  = $this->getData();

		if (!empty($item['content-warning']) && Feature::isEnabled(DI::userSession()->getLocalUserId(), Feature::ADD_ABSTRACT)) {
			$text = '[abstract=' . Protocol::ACTIVITYPUB . ']' . $item['content-warning'] . "[/abstract]\n";
		} else {
			$text = '';
		}

		if (!Feature::isEnabled(DI::userSession()->getLocalUserId(), Feature::EXPLICIT_MENTIONS)) {
			return $text;
		}

		if (($item['author-addr'] != $owner['addr']) && (($item['gravity'] != Item::GRAVITY_PARENT) || !in_array($item['network'], [Protocol::DIASPORA]))) {
			$text .= '@' . $item['author-addr'] . ' ';
		}

		$terms = Tag::getByURIId($item['uri-id'], [Tag::MENTION, Tag::IMPLICIT_MENTION, Tag::EXCLUSIVE_MENTION]);
		foreach ($terms as $term) {
			if (!$term['url']) {
				DI::logger()->warning('Mention term with no URL', ['term' => $term]);
				continue;
			}

			$profile = Contact::getByURL($term['url'], false, ['addr', 'contact-type']);
			if (
				!empty($profile['addr']) && (($profile['contact-type'] ?? Contact::TYPE_UNKNOWN) != Contact::TYPE_COMMUNITY) &&
				($profile['addr'] != $owner['addr']) && !strstr($text, $profile['addr'])
			) {
				$text .= '@' . $profile['addr'] . ' ';
			}
		}

		return $text;
	}

	/**
	 * Get the comment box
	 *
	 * @param string $indent Indent value
	 *
	 * @return mixed The comment box string (empty if no comment box), false on failure
	 * @throws \Exception
	 * @todo return false is nowhere in this method?
	 */
	private function getCommentBox(string $indent)
	{
		$comment_box = '';
		$conv        = $this->getThread();

		if ($conv->isWritable() && $this->isWritable()) {
			/*
			 * Hmmm, code depending on the presence of a particular addon?
			 * This should be better if done by a hook
			 */
			$qcomment = null;
			if (Addon::isEnabled('qcomment')) {
				$words    = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'qcomment', 'words');
				$qcomment = $words ? explode("\n", $words) : [];
			}

			// Fetch the user id from the parent when the owner user is empty
			$uid        = $conv->getProfileOwner();
			$parent_uid = $this->getDataValue('uid');

			$owner = User::getOwnerDataById(DI::userSession()->getLocalUserId());

			$default_text = $this->getDefaultText();

			if (!is_null($parent_uid) && ($uid != $parent_uid)) {
				$uid = $parent_uid;
			}

			$template    = Renderer::getMarkupTemplate($this->getCommentBoxTemplate());
			$comment_box = Renderer::replaceMacros($template, [
				'$return_path' => DI::args()->getQueryString(),
				'$threaded'    => $this->isThreaded(),
				'$jsreload'    => '',
				'$wall'        => ($conv->getMode() === 'profile'),
				'$id'          => $this->getId(),
				'$parent'      => $this->getId(),
				'$qcomment'    => $qcomment,
				'$default'     => $default_text,
				'$profile_uid' => $uid,
				'$mylink'      => DI::baseUrl()->remove($owner['url'] ?? ''),
				'$mytitle'     => DI::l10n()->t('This is you'),
				'$myphoto'     => DI::baseUrl()->remove($owner['thumb'] ?? ''),
				'$comment'     => DI::l10n()->t('Comment'),
				'$submit'      => DI::l10n()->t('Submit'),
				'$loading'     => DI::l10n()->t('Loading...'),
				'$edbold'      => DI::l10n()->t('Bold'),
				'$editalic'    => DI::l10n()->t('Italic'),
				'$eduline'     => DI::l10n()->t('Underline'),
				'$contentwarn' => DI::l10n()->t('Content Warning'),
				'$edquote'     => DI::l10n()->t('Quote'),
				'$edemojis'    => DI::l10n()->t('Add emojis'),
				'$edcode'      => DI::l10n()->t('Code'),
				'$edimg'       => DI::l10n()->t('Image'),
				'$edurl'       => DI::l10n()->t('Link'),
				'$edattach'    => DI::l10n()->t('Link or Media'),
				'$prompttext'  => DI::l10n()->t('Please enter a image/video/audio/webpage URL:'),
				'$preview'     => DI::l10n()->t('Preview'),
				'$indent'      => $indent,
				'$rand_num'    => Crypto::randomDigits(12)
			]);
		}

		return $comment_box;
	}

	/**
	 * @return string
	 */
	private function getRedirectUrl(): string
	{
		return $this->redirect_url;
	}

	/**
	 * Check if we are a wall to wall item and set the relevant properties
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function checkWallToWall()
	{
		$conv               = $this->getThread();
		$this->wall_to_wall = false;

		if ($this->isToplevel()) {
			if ($conv->getMode() !== 'profile') {
				if ($this->getDataValue('owner-link')) {
					$owner_linkmatch = (($this->getDataValue('owner-link')) && Strings::compareLink($this->getDataValue('owner-link'), $this->getDataValue('author-link')));
					$alias_linkmatch = (($this->getDataValue('alias')) && Strings::compareLink($this->getDataValue('alias'), $this->getDataValue('author-link')));
					$owner_namematch = (($this->getDataValue('owner-name')) && $this->getDataValue('owner-name') == $this->getDataValue('author-name'));

					if (!$owner_linkmatch && !$alias_linkmatch && !$owner_namematch) {
						/*
						 * The author url doesn't match the owner (typically the contact)
						 * and also doesn't match the contact alias.
						 * The name match is a hack to catch several weird cases where URLs are
						 * all over the park. It can be tricked, but this prevents you from
						 * seeing "Bob Smith to Bob Smith via Wall-to-wall" and you know darn
						 * well that it's the same Bob Smith.
						 * But it could be somebody else with the same name. It just isn't highly likely.
						 */
						$this->owner_name   = $this->getDataValue('owner-name');
						$this->wall_to_wall = true;

						$owner = [
							'uid'     => 0,
							'id'      => $this->getDataValue('owner-id'),
							'network' => $this->getDataValue('owner-network'),
							'url'     => $this->getDataValue('owner-link'),
							'alias'   => $this->getDataValue('owner-alias'),
						];
						$this->owner_url = Contact::magicLinkByContact($owner);
					}
				}
			}
		}

		if (!$this->wall_to_wall) {
			$this->setTemplate('wall');
			$this->owner_url  = '';
			$this->owner_name = '';
		}
	}

	/**
	 * @return boolean
	 */
	private function isWallToWall(): bool
	{
		return $this->wall_to_wall;
	}

	/**
	 * @return string
	 */
	private function getOwnerUrl(): string
	{
		return $this->owner_url;
	}

	/**
	 * @return string
	 */
	private function getOwnerName(): string
	{
		return $this->owner_name;
	}

	/**
	 * @return boolean
	 */
	private function isVisiting(): bool
	{
		return $this->visiting;
	}
}
