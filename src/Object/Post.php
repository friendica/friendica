<?php
/**
 * @file src/Object/Post.php
 */
namespace Friendica\Object;

use Friendica\BaseObject;
use Friendica\Content\ContactSelector;
use Friendica\Content\Feature;
use Friendica\Core\Addon;
use Friendica\Core\PConfig;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use dba;

require_once 'include/dba.php';
require_once 'include/text.php';
require_once 'boot.php';
require_once 'include/conversation.php';

/**
 * An item
 */
class Post extends BaseObject
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
	private $children = [];
	private $parent = null;
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
	 */
	public function __construct(array $data)
	{
		$a = self::getApp();

		$this->data = $data;
		$this->setTemplate('wall');
		$this->toplevel = $this->getId() == $this->getDataValue('parent');

		if (x($_SESSION, 'remote') && is_array($_SESSION['remote'])) {
			foreach ($_SESSION['remote'] as $visitor) {
				if ($visitor['cid'] == $this->getDataValue('contact-id')) {
					$this->visiting = true;
					break;
				}
			}
		}

		$this->writable = $this->getDataValue('writable') || $this->getDataValue('self');
		$this->redirect_url = 'redir/' . $this->getDataValue('cid');

		if (!$this->isToplevel()) {
			$this->threaded = true;
		}

		// Prepare the children
		if (count($data['children'])) {
			foreach ($data['children'] as $item) {
				// Only add will be displayed
				if ($item['network'] === NETWORK_MAIL && local_user() != $item['uid']) {
					continue;
				} elseif (!visible_activity($item)) {
					continue;
				}

				// You can always comment on Diaspora items
				if (($item['network'] == NETWORK_DIASPORA) && (local_user() == $item['uid'])) {
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
	 * @param object  $conv_responses conversation responses
	 * @param integer $thread_level   default = 1
	 *
	 * @return mixed The data requested on success
	 *               false on failure
	 */
	public function getTemplateData($conv_responses, $thread_level = 1)
	{
		require_once "mod/proxy.php";

		$result = [];

		$a = self::getApp();

		$item = $this->getData();
		$edited = false;
		// If the time between "created" and "edited" differs we add
		// a notice that the post was edited.
		// Note: In some networks reshared items seem to have (sometimes) a difference
		// between creation time and edit time of a second. Thats why we add the notice
		// only if the difference is more than 1 second.
		if (strtotime($item['edited']) - strtotime($item['created']) > 1) {
			$edited = [
				'label'    => t('This entry was edited'),
				'date'     => datetime_convert('UTC', date_default_timezone_get(), $item['edited'], 'r'),
				'relative' => relative_date($item['edited'])
			];
		}
		$commentww = '';
		$sparkle = '';
		$buttons = '';
		$dropping = false;
		$star = false;
		$ignore = false;
		$isstarred = "unstarred";
		$indent = '';
		$shiny = '';
		$osparkle = '';
		$total_children = $this->countDescendants();

		$conv = $this->getThread();

		$lock = ((($item['private'] == 1) || (($item['uid'] == local_user()) && (strlen($item['allow_cid']) || strlen($item['allow_gid'])
			|| strlen($item['deny_cid']) || strlen($item['deny_gid']))))
			? t('Private Message')
			: false);
		$shareable = in_array($conv->getProfileOwner(), [0, local_user()]) && $item['private'] != 1;

		if (local_user() && link_compare($a->contact['url'], $item['author-link'])) {
			if ($item["event-id"] != 0) {
				$edpost = ["events/event/" . $item['event-id'], t("Edit")];
			} else {
				$edpost = ["editpost/" . $item['id'], t("Edit")];
			}
			$dropping = in_array($item['uid'], [0, local_user()]);
		} else {
			$edpost = false;
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

		$drop = [
			'dropping' => $dropping,
			'pagedrop' => ((Feature::isEnabled($conv->getProfileOwner(), 'multi_delete')) ? $item['pagedrop'] : ''),
			'select'   => t('Select'),
			'delete'   => t('Delete'),
		];

		$filer = (($conv->getProfileOwner() == local_user()) ? t("save to folder") : false);

		$diff_author = !link_compare($item['url'], $item['author-link']);
		$profile_name = htmlentities(((strlen($item['author-name'])) && $diff_author) ? $item['author-name'] : $item['name']);
		if ($item['author-link'] && (!$item['author-name'])) {
			$profile_name = $item['author-link'];
		}

		$sp = false;
		$profile_link = best_link_url($item, $sp);
		if ($profile_link === 'mailbox') {
			$profile_link = '';
		}

		if ($sp) {
			$sparkle = ' sparkle';
		} else {
			$profile_link = Profile::zrl($profile_link);
		}

		if (!isset($item['author-thumb']) || ($item['author-thumb'] == "")) {
			$author_contact = Contact::getDetailsByURL($item['author-link'], $conv->getProfileOwner());
			if ($author_contact["thumb"]) {
				$item['author-thumb'] = $author_contact["thumb"];
			} else {
				$item['author-thumb'] = $item['author-avatar'];
			}
		}

		if (!isset($item['owner-thumb']) || ($item['owner-thumb'] == "")) {
			$owner_contact = Contact::getDetailsByURL($item['owner-link'], $conv->getProfileOwner());
			if ($owner_contact["thumb"]) {
				$item['owner-thumb'] = $owner_contact["thumb"];
			} else {
				$item['owner-thumb'] = $item['owner-avatar'];
			}
		}

		$locate = ['location' => $item['location'], 'coord' => $item['coord'], 'html' => ''];
		Addon::callHooks('render_location', $locate);
		$location = ((strlen($locate['html'])) ? $locate['html'] : render_location_dummy($locate));

		// process action responses - e.g. like/dislike/attend/agree/whatever
		$response_verbs = ['like', 'dislike'];

		$isevent = false;
		$attend = [];
		if ($item['object-type'] === ACTIVITY_OBJ_EVENT) {
			$response_verbs[] = 'attendyes';
			$response_verbs[] = 'attendno';
			$response_verbs[] = 'attendmaybe';
			if ($conv->isWritable()) {
				$isevent = true;
				$attend = [t('I will attend'), t('I will not attend'), t('I might attend')];
			}
		}

		$responses = get_responses($conv_responses, $response_verbs, $this, $item);

		foreach ($response_verbs as $value => $verbs) {
			$responses[$verbs]['output'] = x($conv_responses[$verbs], $item['uri']) ? format_like($conv_responses[$verbs][$item['uri']], $conv_responses[$verbs][$item['uri'] . '-l'], $verbs, $item['uri']) : '';
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
			if ($conv->getProfileOwner() == local_user()) {
				$isstarred = (($item['starred']) ? "starred" : "unstarred");

				$star = [
					'do'        => t("add star"),
					'undo'      => t("remove star"),
					'toggle'    => t("toggle star status"),
					'classdo'   => $item['starred'] ? "hidden" : "",
					'classundo' => $item['starred'] ? "" : "hidden",
					'starred'   => t('starred'),
				];
				$thread = dba::selectFirst('thread', ['ignored'], ['uid' => $item['uid'], 'iid' => $item['id']]);
				if (DBM::is_result($thread)) {
					$ignore = [
						'do'        => t("ignore thread"),
						'undo'      => t("unignore thread"),
						'toggle'    => t("toggle ignore status"),
						'classdo'   => $thread['ignored'] ? "hidden" : "",
						'classundo' => $thread['ignored'] ? "" : "hidden",
						'ignored'   => t('ignored'),
					];
				}

				if (Feature::isEnabled($conv->getProfileOwner(), 'commtag')) {
					$tagger = [
						'add'   => t("add tag"),
						'class' => "",
					];
				}
			}
		} else {
			$indent = 'comment';
		}

		if ($conv->isWritable()) {
			$buttons = [
				'like'    => [t("I like this \x28toggle\x29"), t("like")],
				'dislike' => Feature::isEnabled($conv->getProfileOwner(), 'dislike') ? [t("I don't like this \x28toggle\x29"), t("dislike")] : '',
			];
			if ($shareable) {
				$buttons['share'] = [t('Share this'), t('share')];
			}

			// If a contact isn't writable, we cannot send a like or dislike to it
			if (!$item['writable']) {
				unset($buttons["like"]);
				unset($buttons["dislike"]);
			}
		}

		$comment = $this->getCommentBox($indent);

		if (strcmp(datetime_convert('UTC', 'UTC', $item['created']), datetime_convert('UTC', 'UTC', 'now - 12 hours')) > 0) {
			$shiny = 'shiny';
		}

		localize_item($item);

		$body = prepare_body($item, true);

		list($categories, $folders) = get_cats_and_terms($item);

		$body_e       = $body;
		$text_e       = strip_tags($body);
		$name_e       = $profile_name;
		$title_e      = $item['title'];
		$location_e   = $location;
		$owner_name_e = $this->getOwnerName();

		// Disable features that aren't available in several networks
		/// @todo Add NETWORK_DIASPORA when it will pass this information
		if (!in_array($item["item_network"], [NETWORK_DFRN]) && isset($buttons["dislike"])) {
			unset($buttons["dislike"]);
			$isevent = false;
			$tagger = '';
		}

		if (($item["item_network"] == NETWORK_FEED) && isset($buttons["like"])) {
			unset($buttons["like"]);
		}

		if (($item["item_network"] == NETWORK_MAIL) && isset($buttons["like"])) {
			unset($buttons["like"]);
		}

		$tmp_item = [
			'template'        => $this->getTemplate(),
			'type'            => implode("", array_slice(explode("/", $item['verb']), -1)),
			'tags'            => $item['tags'],
			'hashtags'        => $item['hashtags'],
			'mentions'        => $item['mentions'],
			'txt_cats'        => t('Categories:'),
			'txt_folders'     => t('Filed under:'),
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
			'linktitle'       => t('View %s\'s profile @ %s', $profile_name, defaults($item, 'author-link', $item['url'])),
			'olinktitle'      => t('View %s\'s profile @ %s', htmlentities($this->getOwnerName()), defaults($item, 'owner-link', $item['url'])),
			'to'              => t('to'),
			'via'             => t('via'),
			'wall'            => t('Wall-to-Wall'),
			'vwall'           => t('via Wall-To-Wall:'),
			'profile_url'     => $profile_link,
			'item_photo_menu' => item_photo_menu($item),
			'name'            => $name_e,
			'thumb'           => $a->remove_baseurl(proxy_url($item['author-thumb'], false, PROXY_SIZE_THUMB)),
			'osparkle'        => $osparkle,
			'sparkle'         => $sparkle,
			'title'           => $title_e,
			'localtime'       => datetime_convert('UTC', date_default_timezone_get(), $item['created'], 'r'),
			'ago'             => $item['app'] ? t('%s from %s', relative_date($item['created']), $item['app']) : relative_date($item['created']),
			'app'             => $item['app'],
			'created'         => relative_date($item['created']),
			'lock'            => $lock,
			'location'        => $location_e,
			'indent'          => $indent,
			'shiny'           => $shiny,
			'owner_url'       => $this->getOwnerUrl(),
			'owner_photo'     => $a->remove_baseurl(proxy_url($item['owner-thumb'], false, PROXY_SIZE_THUMB)),
			'owner_name'      => htmlentities($owner_name_e),
			'plink'           => get_plink($item),
			'edpost'          => Feature::isEnabled($conv->getProfileOwner(), 'edit_posts') ? $edpost : '',
			'isstarred'       => $isstarred,
			'star'            => Feature::isEnabled($conv->getProfileOwner(), 'star_posts') ? $star : '',
			'ignore'          => Feature::isEnabled($conv->getProfileOwner(), 'ignore_posts') ? $ignore : '',
			'tagger'          => $tagger,
			'filer'           => Feature::isEnabled($conv->getProfileOwner(), 'filing') ? $filer : '',
			'drop'            => $drop,
			'vote'            => $buttons,
			'like'            => $responses['like']['output'],
			'dislike'         => $responses['dislike']['output'],
			'responses'       => $responses,
			'switchcomment'   => t('Comment'),
			'comment'         => $comment,
			'previewing'      => $conv->isPreview() ? ' preview ' : '',
			'wait'            => t('Please wait'),
			'thread_level'    => $thread_level,
			'edited'          => $edited,
			'network'         => $item["item_network"],
			'network_name'    => ContactSelector::networkToName($item['item_network'], $profile_link),
			'received'        => $item['received'],
			'commented'       => $item['commented'],
			'created_date'    => $item['created'],
		];

		$arr = ['item' => $item, 'output' => $tmp_item];
		Addon::callHooks('display_item', $arr);

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
				$result['children'][0]['num_comments'] = tt('%d comment', '%d comments', $total_children);
				$result['children'][0]['hidden_comments_num'] = $total_children;
				$result['children'][0]['hidden_comments_text'] = tt('comment', 'comments', $total_children);
				$result['children'][0]['hide_text'] = t('show more');
				if ($thread_level > 1) {
					$result['children'][$nb_children - 1]['comment_lastcollapsed'] = true;
				} else {
					$result['children'][$nb_children - 3]['comment_lastcollapsed'] = true;
				}
			}
		}

		if ($this->isToplevel()) {
			$result['total_comments_num'] = "$total_children";
			$result['total_comments_text'] = tt('comment', 'comments', $total_children);
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
	 * @param object $item The child item to add
	 *
	 * @return mixed
	 */
	public function addChild(Post $item)
	{
		$item_id = $item->getId();
		if (!$item_id) {
			logger('[ERROR] Post::addChild : Item has no ID!!', LOGGER_DEBUG);
			return false;
		} elseif ($this->getChild($item->getId())) {
			logger('[WARN] Post::addChild : Item already exists (' . $item->getId() . ').', LOGGER_DEBUG);
			return false;
		}
		/*
		 * Only add what will be displayed
		 */
		if ($item->getDataValue('network') === NETWORK_MAIL && local_user() != $item->getDataValue('uid')) {
			return false;
		} elseif (activity_match($item->getDataValue('verb'), ACTIVITY_LIKE) || activity_match($item->getDataValue('verb'), ACTIVITY_DISLIKE)) {
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
	 * @return object
	 */
	public function getChildren()
	{
		return $this->children;
	}

	/**
	 * Set our parent
	 *
	 * @param object $item The item to set as parent
	 *
	 * @return void
	 */
	protected function setParent($item)
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
	 * @param object $item The child to be removed
	 *
	 * @return boolean Success or failure
	 */
	public function removeChild($item)
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
		logger('[WARN] Item::removeChild : Item is not a child (' . $id . ').', LOGGER_DEBUG);
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
	 * Set conversation
	 *
	 * @param object $conv The conversation
	 *
	 * @return void
	 */
	public function setThread($conv)
	{
		$previous_mode = ($this->thread ? $this->thread->getMode() : '');

		$this->thread = $conv;

		// Set it on our children too
		foreach ($this->getChildren() as $child) {
			$child->setThread($conv);
		}
	}

	/**
	 * Get conversation
	 *
	 * @return object
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
	 * @param object $name key
	 *
	 * @return mixed value on success
	 *               false on failure
	 */
	public function getDataValue($name)
	{
		if (!isset($this->data[$name])) {
			// logger('[ERROR] Item::getDataValue : Item has no value name "'. $name .'".', LOGGER_DEBUG);
			return false;
		}

		return $this->data[$name];
	}

	/**
	 * Set template
	 *
	 * @param object $name template name
	 *
	 * @return void
	 */
	private function setTemplate($name)
	{
		if (!x($this->available_templates, $name)) {
			logger('[ERROR] Item::setTemplate : Template not available ("' . $name . '").', LOGGER_DEBUG);
			return false;
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
	 * Get the comment box
	 *
	 * @param string $indent Indent value
	 *
	 * @return mixed The comment box string (empty if no comment box)
	 *               false on failure
	 */
	private function getCommentBox($indent)
	{
		$a = self::getApp();

		$comment_box = '';
		$conv = $this->getThread();
		$ww = '';
		if (($conv->getMode() === 'network') && $this->isWallToWall()) {
			$ww = 'ww';
		}

		if ($conv->isWritable() && $this->isWritable()) {
			$qc = $qcomment = null;

			/*
			 * Hmmm, code depending on the presence of a particular plugin?
			 * This should be better if done by a hook
			 */
			if (in_array('qcomment', $a->plugins)) {
				$qc = ((local_user()) ? PConfig::get(local_user(), 'qcomment', 'words') : null);
				$qcomment = (($qc) ? explode("\n", $qc) : null);
			}

			// Fetch the user id from the parent when the owner user is empty
			$uid = $conv->getProfileOwner();
			$parent_uid = $this->getDataValue('uid');

			if (!is_null($parent_uid) && ($uid != $parent_uid)) {
				$uid = $parent_uid;
			}

			$template = get_markup_template($this->getCommentBoxTemplate());
			$comment_box = replace_macros($template, [
				'$return_path' => $a->query_string,
				'$threaded'    => $this->isThreaded(),
				'$jsreload'    => '',
				'$type'        => $conv->getMode() === 'profile' ? 'wall-comment' : 'net-comment',
				'$id'          => $this->getId(),
				'$parent'      => $this->getId(),
				'$qcomment'    => $qcomment,
				'$profile_uid' => $uid,
				'$mylink'      => $a->remove_baseurl($a->contact['url']),
				'$mytitle'     => t('This is you'),
				'$myphoto'     => $a->remove_baseurl($a->contact['thumb']),
				'$comment'     => t('Comment'),
				'$submit'      => t('Submit'),
				'$edbold'      => t('Bold'),
				'$editalic'    => t('Italic'),
				'$eduline'     => t('Underline'),
				'$edquote'     => t('Quote'),
				'$edcode'      => t('Code'),
				'$edimg'       => t('Image'),
				'$edurl'       => t('Link'),
				'$edvideo'     => t('Video'),
				'$preview'     => ((Feature::isEnabled($conv->getProfileOwner(), 'preview')) ? t('Preview') : ''),
				'$indent'      => $indent,
				'$sourceapp'   => t($a->sourcename),
				'$ww'          => $conv->getMode() === 'network' ? $ww : '',
				'$rand_num'    => random_digits(12)
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
	 */
	protected function checkWallToWall()
	{
		$a = self::getApp();
		$conv = $this->getThread();
		$this->wall_to_wall = false;

		if ($this->isToplevel()) {
			if ($conv->getMode() !== 'profile') {
				if ($this->getDataValue('wall') && !$this->getDataValue('self')) {
					// On the network page, I am the owner. On the display page it will be the profile owner.
					// This will have been stored in $a->page_contact by our calling page.
					// Put this person as the wall owner of the wall-to-wall notice.

					$this->owner_url = Profile::zrl($a->page_contact['url']);
					$this->owner_photo = $a->page_contact['thumb'];
					$this->owner_name = $a->page_contact['name'];
					$this->wall_to_wall = true;
				} elseif ($this->getDataValue('owner-link')) {
					$owner_linkmatch = (($this->getDataValue('owner-link')) && link_compare($this->getDataValue('owner-link'), $this->getDataValue('author-link')));
					$alias_linkmatch = (($this->getDataValue('alias')) && link_compare($this->getDataValue('alias'), $this->getDataValue('author-link')));
					$owner_namematch = (($this->getDataValue('owner-name')) && $this->getDataValue('owner-name') == $this->getDataValue('author-name'));

					if ((!$owner_linkmatch) && (!$alias_linkmatch) && (!$owner_namematch)) {
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
						// If it is our contact, use a friendly redirect link
						if ($this->getDataValue('network') === NETWORK_DFRN
							&& link_compare($this->getDataValue('owner-link'), $this->getDataValue('url'))
						) {
							$this->owner_url = $this->getRedirectUrl();
						} else {
							$this->owner_url = Profile::zrl($this->getDataValue('owner-link'));
						}
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
