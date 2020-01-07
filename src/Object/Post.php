<?php
/**
 * @file src/Object/Post.php
 */
namespace Friendica\Object;

use Friendica\Content\ContactSelector;
use Friendica\Content\Feature;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\PConfig;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\Registry\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Term;
use Friendica\Model\User;
use Friendica\Protocol\Activity;
use Friendica\Registry\App;
use Friendica\Registry\Content;
use Friendica\Registry\Protocol as P;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Util\Strings;
use Friendica\Util\Temporal;

/**
 * An item
 */
class Post
{
	private $data = [];
	private $template = null;
	private $available_templates = [
		'wall' => 'wall_thread.tpl',
		'wall2wall' => 'wallwall_thread.tpl'
	];
	private $comment_box_template = 'comment_item.tpl';
	private $toplevel = false;
	private $writable = false;
	/**
	 * @var Post[]
	 */
	private $children = [];
	private $parent = null;

	/**
	 * @var Thread
	 */
	private $thread = null;
	private $redirect_url = null;
	private $owner_url = '';
	private $owner_photo = '';
	private $owner_name = '';
	private $wall_to_wall = false;
	private $threaded = false;
	private $visiting = false;

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

		if (!empty(Session::getUserIDForVisitorContactID($this->getDataValue('contact-id')))) {
			$this->visiting = true;
		}

		$this->writable = $this->getDataValue('writable') || $this->getDataValue('self');
		$author = ['uid' => 0, 'id' => $this->getDataValue('author-id'),
			'network' => $this->getDataValue('author-network'),
			'url' => $this->getDataValue('author-link')];
		$this->redirect_url = Contact::magicLinkByContact($author);
		if (!$this->isToplevel()) {
			$this->threaded = true;
		}

		// Prepare the children
		if (!empty($data['children'])) {
			foreach ($data['children'] as $item) {
				// Only add will be displayed
				if ($item['network'] === Protocol::MAIL && local_user() != $item['uid']) {
					continue;
				} elseif (!visible_activity($item)) {
					continue;
				}

				// You can always comment on Diaspora and OStatus items
				if (in_array($item['network'], [Protocol::OSTATUS, Protocol::DIASPORA]) && (local_user() == $item['uid'])) {
					$item['writable'] = true;
				}

				$item['pagedrop'] = $data['pagedrop'];
				$child = new Post($item);
				$this->addChild($child);
			}
		}
	}

	/**
	 * Get data in a form usable by a conversation template
	 *
	 * @param array   $conv_responses conversation responses
	 * @param integer $thread_level   default = 1
	 *
	 * @return mixed The data requested on success
	 *               false on failure
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function getTemplateData(array $conv_responses, $thread_level = 1)
	{
		$a = DI::app();

		$item = $this->getData();
		$edited = false;
		// If the time between "created" and "edited" differs we add
		// a notice that the post was edited.
		// Note: In some networks reshared items seem to have (sometimes) a difference
		// between creation time and edit time of a second. Thats why we add the notice
		// only if the difference is more than 1 second.
		if (strtotime($item['edited']) - strtotime($item['created']) > 1) {
			$edited = [
				'label'    => L10n::t('This entry was edited'),
				'date'     => DateTimeFormat::local($item['edited'], 'r'),
				'relative' => Temporal::getRelativeDate($item['edited'])
			];
		}
		$sparkle = '';
		$buttons = '';
		$dropping = false;
		$pinned = '';
		$pin = false;
		$star = false;
		$ignore = false;
		$ispinned = "unpinned";
		$isstarred = "unstarred";
		$indent = '';
		$shiny = '';
		$osparkle = '';
		$total_children = $this->countDescendants();

		$conv = $this->getThread();

		$lock = ((($item['private'] == 1) || (($item['uid'] == local_user()) && (strlen($item['allow_cid']) || strlen($item['allow_gid'])
			|| strlen($item['deny_cid']) || strlen($item['deny_gid']))))
			? L10n::t('Private Message')
			: false);

		$shareable = in_array($conv->getProfileOwner(), [0, local_user()]) && $item['private'] != 1;

		$edpost = false;

		if (local_user()) {
			if (Strings::compareLink($a->contact['url'], $item['author-link'])) {
				if ($item["event-id"] != 0) {
					$edpost = ["events/event/" . $item['event-id'], L10n::t("Edit")];
				} else {
					$edpost = ["editpost/" . $item['id'], L10n::t("Edit")];
				}
			}
			$dropping = in_array($item['uid'], [0, local_user()]);
		}

		// Editing on items of not subscribed users isn't currently possible
		// There are some issues on editing that prevent this.
		// But also it is an issue of the supported protocols that doesn't allow editing at all.
		if ($item['uid'] == 0) {
			$edpost = false;
		}

		if (($this->getDataValue('uid') == local_user()) || $this->isVisiting()) {
			$dropping = true;
		}

		$origin = $item['origin'];

		if (!$origin) {
			/// @todo This shouldn't be done as query here, but better during the data creation.
			// it is now done here, since during the RC phase we shouldn't make to intense changes.
			$parent = Item::selectFirst(['origin'], ['id' => $item['parent']]);
			if (DBA::isResult($parent)) {
				$origin = $parent['origin'];
			}
		} elseif ($item['pinned']) {
			$pinned = L10n::t('pinned item');
		}

		if ($origin && ($item['id'] != $item['parent']) && ($item['network'] == Protocol::ACTIVITYPUB)) {
			// ActivityPub doesn't allow removal of remote comments
			$delete = L10n::t('Delete locally');
		} else {
			// Showing the one or the other text, depending upon if we can only hide it or really delete it.
			$delete = $origin ? L10n::t('Delete globally') : L10n::t('Remove locally');
		}

		$drop = [
			'dropping' => $dropping,
			'pagedrop' => $item['pagedrop'],
			'select'   => L10n::t('Select'),
			'delete'   => $delete,
		];

		if (!local_user()) {
			$drop = false;
		}

		$filer = (($conv->getProfileOwner() == local_user() && ($item['uid'] != 0)) ? L10n::t("save to folder") : false);

		$profile_name = $item['author-name'];
		if (!empty($item['author-link']) && empty($item['author-name'])) {
			$profile_name = $item['author-link'];
		}

		$author = ['uid' => 0, 'id' => $item['author-id'],
			'network' => $item['author-network'], 'url' => $item['author-link']];

		if (Session::isAuthenticated()) {
			$profile_link = Contact::magicLinkByContact($author);
		} else {
			$profile_link = $item['author-link'];
		}

		if (strpos($profile_link, 'redir/') === 0) {
			$sparkle = ' sparkle';
		}

		$locate = ['location' => $item['location'], 'coord' => $item['coord'], 'html' => ''];
		Hook::callAll('render_location', $locate);
		$location = ((strlen($locate['html'])) ? $locate['html'] : render_location_dummy($locate));

		// process action responses - e.g. like/dislike/attend/agree/whatever
		$response_verbs = ['like', 'dislike', 'announce'];

		$isevent = false;
		$attend = [];
		if ($item['object-type'] === Activity\ObjectType::EVENT) {
			$response_verbs[] = 'attendyes';
			$response_verbs[] = 'attendno';
			$response_verbs[] = 'attendmaybe';
			if ($conv->isWritable()) {
				$isevent = true;
				$attend = [L10n::t('I will attend'), L10n::t('I will not attend'), L10n::t('I might attend')];
			}
		}

		$responses = get_responses($conv_responses, $response_verbs, $item, $this);

		foreach ($response_verbs as $value => $verbs) {
			$responses[$verbs]['output'] = !empty($conv_responses[$verbs][$item['uri']]) ? format_like($conv_responses[$verbs][$item['uri']], $conv_responses[$verbs][$item['uri'] . '-l'], $verbs, $item['uri']) : '';
		}

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
			if(local_user()) {
				$thread = Item::selectFirstThreadForUser(local_user(), ['ignored'], ['iid' => $item['id']]);
				if (DBA::isResult($thread)) {
					$ignore = [
						'do'        => L10n::t("ignore thread"),
						'undo'      => L10n::t("unignore thread"),
						'toggle'    => L10n::t("toggle ignore status"),
						'classdo'   => $thread['ignored'] ? "hidden" : "",
						'classundo' => $thread['ignored'] ? "" : "hidden",
						'ignored'   => L10n::t('ignored'),
					];
				}

				if ($conv->getProfileOwner() == local_user() && ($item['uid'] != 0)) {
					if ($origin) {
						$ispinned = ($item['pinned'] ? 'pinned' : 'unpinned');

						$pin = [
							'do'        => L10n::t('pin'),
							'undo'      => L10n::t('unpin'),
							'toggle'    => L10n::t('toggle pin status'),
							'classdo'   => $item['pinned'] ? 'hidden' : '',
							'classundo' => $item['pinned'] ? '' : 'hidden',
							'pinned'   => L10n::t('pinned'),
						];
					}

					$isstarred = (($item['starred']) ? "starred" : "unstarred");

					$star = [
						'do'        => L10n::t("add star"),
						'undo'      => L10n::t("remove star"),
						'toggle'    => L10n::t("toggle star status"),
						'classdo'   => $item['starred'] ? "hidden" : "",
						'classundo' => $item['starred'] ? "" : "hidden",
						'starred'   => L10n::t('starred'),
					];

					$tagger = [
						'add'   => L10n::t("add tag"),
						'class' => "",
					];
				}
			}
		} else {
			$indent = 'comment';
		}

		if ($conv->isWritable()) {
			$buttons = [
				'like'    => [L10n::t("I like this \x28toggle\x29"), L10n::t("like")],
				'dislike' => [L10n::t("I don't like this \x28toggle\x29"), L10n::t("dislike")],
			];
			if ($shareable) {
				$buttons['share'] = [L10n::t('Share this'), L10n::t('share')];
			}
		}

		$comment = $this->getCommentBox($indent);

		if (strcmp(DateTimeFormat::utc($item['created']), DateTimeFormat::utc('now - 12 hours')) > 0) {
			$shiny = 'shiny';
		}

		localize_item($item);

		$body = Item::prepareBody($item, true);

		list($categories, $folders) = Content::item()->determineCategoriesTerms($item);

		$body_e       = $body;
		$text_e       = strip_tags($body);
		$name_e       = $profile_name;

		if (!empty($item['content-warning']) && PConfig::get(local_user(), 'system', 'disable_cw', false)) {
			$title_e = ucfirst($item['content-warning']);
		} else {
			$title_e = $item['title'];
		}

		$location_e   = $location;
		$owner_name_e = $this->getOwnerName();

		// Disable features that aren't available in several networks
		if (!in_array($item["network"], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA]) && isset($buttons["dislike"])) {
			unset($buttons["dislike"]);
			$isevent = false;
			$tagger = '';
		}

		if (($item["network"] == Protocol::FEED) && isset($buttons["like"])) {
			unset($buttons["like"]);
		}

		if (($item["network"] == Protocol::MAIL) && isset($buttons["like"])) {
			unset($buttons["like"]);
		}

		$tags = Term::populateTagsFromItem($item);

		$ago = Temporal::getRelativeDate($item['created']);
		$ago_received = Temporal::getRelativeDate($item['received']);
		if (Config::get('system', 'show_received') && (abs(strtotime($item['created']) - strtotime($item['received'])) > Config::get('system', 'show_received_seconds')) && ($ago != $ago_received)) {
			$ago = L10n::t('%s (Received %s)', $ago, $ago_received);
		}

		$tmp_item = [
			'template'        => $this->getTemplate(),
			'type'            => implode("", array_slice(explode("/", $item['verb']), -1)),
			'suppress_tags'   => Config::get('system', 'suppress_tags'),
			'tags'            => $tags['tags'],
			'hashtags'        => $tags['hashtags'],
			'mentions'        => $tags['mentions'],
			'implicit_mentions' => $tags['implicit_mentions'],
			'txt_cats'        => L10n::t('Categories:'),
			'txt_folders'     => L10n::t('Filed under:'),
			'has_cats'        => ((count($categories)) ? 'true' : ''),
			'has_folders'     => ((count($folders)) ? 'true' : ''),
			'categories'      => $categories,
			'folders'         => $folders,
			'body'            => $body_e,
			'text'            => $text_e,
			'id'              => $this->getId(),
			'guid'            => urlencode($item['guid']),
			'isevent'         => $isevent,
			'attend'          => $attend,
			'linktitle'       => L10n::t('View %s\'s profile @ %s', $profile_name, $item['author-link']),
			'olinktitle'      => L10n::t('View %s\'s profile @ %s', $this->getOwnerName(), $item['owner-link']),
			'to'              => L10n::t('to'),
			'via'             => L10n::t('via'),
			'wall'            => L10n::t('Wall-to-Wall'),
			'vwall'           => L10n::t('via Wall-To-Wall:'),
			'profile_url'     => $profile_link,
			'item_photo_menu' => item_photo_menu($item),
			'name'            => $name_e,
			'thumb'           => App::baseUrl()->remove(ProxyUtils::proxifyUrl($item['author-avatar'], false, ProxyUtils::SIZE_THUMB)),
			'osparkle'        => $osparkle,
			'sparkle'         => $sparkle,
			'title'           => $title_e,
			'localtime'       => DateTimeFormat::local($item['created'], 'r'),
			'ago'             => $item['app'] ? L10n::t('%s from %s', $ago, $item['app']) : $ago,
			'app'             => $item['app'],
			'created'         => $ago,
			'lock'            => $lock,
			'location'        => $location_e,
			'indent'          => $indent,
			'shiny'           => $shiny,
			'owner_self'      => $item['author-link'] == Session::get('my_url'),
			'owner_url'       => $this->getOwnerUrl(),
			'owner_photo'     => App::baseUrl()->remove(ProxyUtils::proxifyUrl($item['owner-avatar'], false, ProxyUtils::SIZE_THUMB)),
			'owner_name'      => $owner_name_e,
			'plink'           => Item::getPlink($item),
			'edpost'          => $edpost,
			'ispinned'        => $ispinned,
			'pin'             => $pin,
			'pinned'          => $pinned,
			'isstarred'       => $isstarred,
			'star'            => $star,
			'ignore'          => $ignore,
			'tagger'          => $tagger,
			'filer'           => $filer,
			'drop'            => $drop,
			'vote'            => $buttons,
			'like'            => $responses['like']['output'],
			'dislike'         => $responses['dislike']['output'],
			'responses'       => $responses,
			'switchcomment'   => L10n::t('Comment'),
			'reply_label'     => L10n::t('Reply to %s', $name_e),
			'comment'         => $comment,
			'previewing'      => $conv->isPreview() ? ' preview ' : '',
			'wait'            => L10n::t('Please wait'),
			'thread_level'    => $thread_level,
			'edited'          => $edited,
			'network'         => $item["network"],
			'network_name'    => ContactSelector::networkToName($item['author-network'], $item['author-link'], $item['network']),
			'network_icon'    => ContactSelector::networkToIcon($item['network'], $item['author-link']),
			'received'        => $item['received'],
			'commented'       => $item['commented'],
			'created_date'    => $item['created'],
			'return'          => (App::args()->getCommand()) ? bin2hex(App::args()->getCommand()) : '',
			'delivery'        => [
				'queue_count'       => $item['delivery_queue_count'],
				'queue_done'        => $item['delivery_queue_done'] + $item['delivery_queue_failed'], /// @todo Possibly display it separately in the future
				'notifier_pending'  => L10n::t('Notifier task is pending'),
				'delivery_pending'  => L10n::t('Delivery to remote servers is pending'),
				'delivery_underway' => L10n::t('Delivery to remote servers is underway'),
				'delivery_almost'   => L10n::t('Delivery to remote servers is mostly done'),
				'delivery_done'     => L10n::t('Delivery to remote servers is done'),
			],
		];

		$arr = ['item' => $item, 'output' => $tmp_item];
		Hook::callAll('display_item', $arr);

		$result = $arr['output'];

		$result['children'] = [];
		$children = $this->getChildren();
		$nb_children = count($children);
		if ($nb_children > 0) {
			foreach ($children as $child) {
				$result['children'][] = $child->getTemplateData($conv_responses, $thread_level + 1);
			}

			// Collapse
			if (($nb_children > 2) || ($thread_level > 1)) {
				$result['children'][0]['comment_firstcollapsed'] = true;
				$result['children'][0]['num_comments'] = L10n::tt('%d comment', '%d comments', $total_children);
				$result['children'][0]['show_text'] = L10n::t('Show more');
				$result['children'][0]['hide_text'] = L10n::t('Show fewer');
				if ($thread_level > 1) {
					$result['children'][$nb_children - 1]['comment_lastcollapsed'] = true;
				} else {
					$result['children'][$nb_children - 3]['comment_lastcollapsed'] = true;
				}
			}
		}

		if ($this->isToplevel()) {
			$result['total_comments_num'] = "$total_children";
			$result['total_comments_text'] = L10n::tt('comment', 'comments', $total_children);
		}

		$result['private'] = $item['private'];
		$result['toplevel'] = ($this->isToplevel() ? 'toplevel_item' : '');

		if ($this->isThreaded()) {
			$result['flatten'] = false;
			$result['threaded'] = true;
		} else {
			$result['flatten'] = true;
			$result['threaded'] = false;
		}

		return $result;
	}

	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->getDataValue('id');
	}

	/**
	 * @return boolean
	 */
	public function isThreaded()
	{
		return $this->threaded;
	}

	/**
	 * Add a child item
	 *
	 * @param Post $item The child item to add
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function addChild(Post $item)
	{
		$item_id = $item->getId();
		if (!$item_id) {
			Logger::log('[ERROR] Post::addChild : Item has no ID!!', Logger::DEBUG);
			return false;
		} elseif ($this->getChild($item->getId())) {
			Logger::log('[WARN] Post::addChild : Item already exists (' . $item->getId() . ').', Logger::DEBUG);
			return false;
		}

		$activity = P::activity();

		/*
		 * Only add what will be displayed
		 */
		if ($item->getDataValue('network') === Protocol::MAIL && local_user() != $item->getDataValue('uid')) {
			return false;
		} elseif ($activity->match($item->getDataValue('verb'), Activity::LIKE) ||
		          $activity->match($item->getDataValue('verb'), Activity::DISLIKE)) {
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
	 *
	 * @return mixed
	 */
	public function getChild($id)
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
	public function getChildren()
	{
		return $this->children;
	}

	/**
	 * Set our parent
	 *
	 * @param Post $item The item to set as parent
	 *
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
	 *
	 * @return boolean Success or failure
	 * @throws \Exception
	 */
	public function removeChild(Post $item)
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
		Logger::log('[WARN] Item::removeChild : Item is not a child (' . $id . ').', Logger::DEBUG);
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
	 * @param Thread $thread
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
	 * @return Thread
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
	public function getData()
	{
		return $this->data;
	}

	/**
	 * Get a data value
	 *
	 * @param string $name key
	 *
	 * @return mixed value on success
	 *               false on failure
	 */
	public function getDataValue($name)
	{
		if (!isset($this->data[$name])) {
			// Logger::log('[ERROR] Item::getDataValue : Item has no value name "'. $name .'".', Logger::DEBUG);
			return false;
		}

		return $this->data[$name];
	}

	/**
	 * Set template
	 *
	 * @param string $name template name
	 * @return bool
	 * @throws \Exception
	 */
	private function setTemplate($name)
	{
		if (empty($this->available_templates[$name])) {
			Logger::log('[ERROR] Item::setTemplate : Template not available ("' . $name . '").', Logger::DEBUG);
			return false;
		}

		$this->template = $this->available_templates[$name];

		return true;
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
	private function isToplevel()
	{
		return $this->toplevel;
	}

	/**
	 * Check if this is writable
	 *
	 * @return boolean
	 */
	private function isWritable()
	{
		$conv = $this->getThread();

		if ($conv) {
			// This will allow us to comment on wall-to-wall items owned by our friends
			// and community forums even if somebody else wrote the post.
			// bug #517 - this fixes for conversation owner
			if ($conv->getMode() == 'profile' && $conv->getProfileOwner() == local_user()) {
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
	private function countDescendants()
	{
		$children = $this->getChildren();
		$total = count($children);
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
	private function getCommentBoxTemplate()
	{
		return $this->comment_box_template;
	}

	/**
	 * Get default text for the comment box
	 *
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private function getDefaultText()
	{
		$a = DI::app();

		if (!local_user()) {
			return '';
		}

		$owner = User::getOwnerDataById($a->user['uid']);

		if (!Feature::isEnabled(local_user(), 'explicit_mentions')) {
			return '';
		}

		$item = Item::selectFirst(['author-addr'], ['id' => $this->getId()]);
		if (!DBA::isResult($item) || empty($item['author-addr'])) {
			// Should not happen
			return '';
		}

		if ($item['author-addr'] != $owner['addr']) {
			$text = '@' . $item['author-addr'] . ' ';
		} else {
			$text = '';
		}

		$terms = Term::tagArrayFromItemId($this->getId(), [Term::MENTION, Term::IMPLICIT_MENTION]);
		foreach ($terms as $term) {
			$profile = Contact::getDetailsByURL($term['url']);
			if (!empty($profile['addr']) && ((($profile['contact-type'] ?? '') ?: Contact::TYPE_UNKNOWN) != Contact::TYPE_COMMUNITY) &&
				($profile['addr'] != $owner['addr']) && !strstr($text, $profile['addr'])) {
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
	 * @return mixed The comment box string (empty if no comment box)
	 *               false on failure
	 * @throws \Exception
	 */
	private function getCommentBox($indent)
	{
		$a = DI::app();

		$comment_box = '';
		$conv = $this->getThread();
		$ww = '';
		if (($conv->getMode() === 'network') && $this->isWallToWall()) {
			$ww = 'ww';
		}

		if ($conv->isWritable() && $this->isWritable()) {
			$qcomment = null;

			/*
			 * Hmmm, code depending on the presence of a particular addon?
			 * This should be better if done by a hook
			 */
			if (Addon::isEnabled('qcomment')) {
				$qc = ((local_user()) ? PConfig::get(local_user(), 'qcomment', 'words') : null);
				$qcomment = (($qc) ? explode("\n", $qc) : null);
			}

			// Fetch the user id from the parent when the owner user is empty
			$uid = $conv->getProfileOwner();
			$parent_uid = $this->getDataValue('uid');

			$default_text = $this->getDefaultText();

			if (!is_null($parent_uid) && ($uid != $parent_uid)) {
				$uid = $parent_uid;
			}

			$template = Renderer::getMarkupTemplate($this->getCommentBoxTemplate());
			$comment_box = Renderer::replaceMacros($template, [
				'$return_path' => App::args()->getQueryString(),
				'$threaded'    => $this->isThreaded(),
				'$jsreload'    => '',
				'$wall'        => ($conv->getMode() === 'profile'),
				'$id'          => $this->getId(),
				'$parent'      => $this->getId(),
				'$qcomment'    => $qcomment,
				'$default'     => $default_text,
				'$profile_uid' => $uid,
				'$mylink'      => App::baseUrl()->remove($a->contact['url']),
				'$mytitle'     => L10n::t('This is you'),
				'$myphoto'     => App::baseUrl()->remove($a->contact['thumb']),
				'$comment'     => L10n::t('Comment'),
				'$submit'      => L10n::t('Submit'),
				'$edbold'      => L10n::t('Bold'),
				'$editalic'    => L10n::t('Italic'),
				'$eduline'     => L10n::t('Underline'),
				'$edquote'     => L10n::t('Quote'),
				'$edcode'      => L10n::t('Code'),
				'$edimg'       => L10n::t('Image'),
				'$edurl'       => L10n::t('Link'),
				'$edattach'    => L10n::t('Link or Media'),
				'$prompttext'  => L10n::t('Please enter a image/video/audio/webpage URL:'),
				'$preview'     => L10n::t('Preview'),
				'$indent'      => $indent,
				'$sourceapp'   => L10n::t($a->sourcename),
				'$ww'          => $conv->getMode() === 'network' ? $ww : '',
				'$rand_num'    => Crypto::randomDigits(12)
			]);
		}

		return $comment_box;
	}

	/**
	 * @return string
	 */
	private function getRedirectUrl()
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
		$a = DI::app();
		$conv = $this->getThread();
		$this->wall_to_wall = false;

		if ($this->isToplevel()) {
			if ($conv->getMode() !== 'profile') {
				if ($this->getDataValue('wall') && !$this->getDataValue('self')) {
					// On the network page, I am the owner. On the display page it will be the profile owner.
					// This will have been stored in $a->page_contact by our calling page.
					// Put this person as the wall owner of the wall-to-wall notice.

					$this->owner_url = Contact::magicLink($a->page_contact['url']);
					$this->owner_photo = $a->page_contact['thumb'];
					$this->owner_name = $a->page_contact['name'];
					$this->wall_to_wall = true;
				} elseif ($this->getDataValue('owner-link')) {
					$owner_linkmatch = (($this->getDataValue('owner-link')) && Strings::compareLink($this->getDataValue('owner-link'), $this->getDataValue('author-link')));
					$alias_linkmatch = (($this->getDataValue('alias')) && Strings::compareLink($this->getDataValue('alias'), $this->getDataValue('author-link')));
					$owner_namematch = (($this->getDataValue('owner-name')) && $this->getDataValue('owner-name') == $this->getDataValue('author-name'));

					if (!$owner_linkmatch && !$alias_linkmatch && !$owner_namematch) {
						// The author url doesn't match the owner (typically the contact)
						// and also doesn't match the contact alias.
						// The name match is a hack to catch several weird cases where URLs are
						// all over the park. It can be tricked, but this prevents you from
						// seeing "Bob Smith to Bob Smith via Wall-to-wall" and you know darn
						// well that it's the same Bob Smith.
						// But it could be somebody else with the same name. It just isn't highly likely.


						$this->owner_photo = $this->getDataValue('owner-avatar');
						$this->owner_name = $this->getDataValue('owner-name');
						$this->wall_to_wall = true;

						$owner = ['uid' => 0, 'id' => $this->getDataValue('owner-id'),
							'network' => $this->getDataValue('owner-network'),
							'url' => $this->getDataValue('owner-link')];
						$this->owner_url = Contact::magicLinkByContact($owner);
					}
				}
			}
		}

		if (!$this->wall_to_wall) {
			$this->setTemplate('wall');
			$this->owner_url = '';
			$this->owner_photo = '';
			$this->owner_name = '';
		}
	}

	/**
	 * @return boolean
	 */
	private function isWallToWall()
	{
		return $this->wall_to_wall;
	}

	/**
	 * @return string
	 */
	private function getOwnerUrl()
	{
		return $this->owner_url;
	}

	/**
	 * @return string
	 */
	private function getOwnerName()
	{
		return $this->owner_name;
	}

	/**
	 * @return boolean
	 */
	private function isVisiting()
	{
		return $this->visiting;
	}
}
