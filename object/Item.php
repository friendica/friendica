<?php
if(class_exists('Item'))
	return;

require_once('object/BaseObject.php');
require_once('include/text.php');
require_once('include/diaspora.php');
require_once('boot.php');

/**
 * An item
 */
class Item extends BaseObject {
	private $data = array();
	private $template = null;
	private $available_templates = array(
		'wall' => 'wall_thread.tpl',
		'wall2wall' => 'wallwall_thread.tpl'
	);
	private $comment_box_template = 'comment_item.tpl';
	private $toplevel = false;
	private $writable = false;
	private $children = array();
	private $parent = null;
	private $conversation = null;
	private $redirect_url = null;
	private $owner_url = '';
	private $owner_photo = '';
	private $owner_name = '';
	private $wall_to_wall = false;
	private $threaded = false;
	private $visiting = false;

	public function __construct($data) {
		$a = $this->get_app();

		$this->data = $data;
		$this->set_template('wall');
		$this->toplevel = ($this->get_id() == $this->get_data_value('parent'));

		if(is_array($_SESSION['remote'])) {
			foreach($_SESSION['remote'] as $visitor) {
				if($visitor['cid'] == $this->get_data_value('contact-id')) {
					$this->visiting = true;
					break;
				}
			}
		}

		$this->writable = ($this->get_data_value('writable') || $this->get_data_value('self'));

		$ssl_state = ((local_user()) ? true : false);
		$this->redirect_url = 'redir/' . $this->get_data_value('cid') ;

		if(get_config('system','thread_allow') && $a->theme_thread_allow && !$this->is_toplevel())
			$this->threaded = true;

		// Prepare the children
		if(count($data['children'])) {
			foreach($data['children'] as $item) {
				/*
				 * Only add will be displayed
				 */
				if($item['network'] === NETWORK_MAIL && local_user() != $item['uid']) {
					continue;
				}
				if(! visible_activity($item)) {
					continue;
				}
				$item['pagedrop'] = $data['pagedrop'];
				$child = new Item($item);
				$this->add_child($child);
			}
		}
	}

	/**
	 * Get data in a form usable by a conversation template
	 *
	 * Returns:
	 *      _ The data requested on success
	 *      _ false on failure
	 */
	public function get_template_data($conv_responses, $thread_level=1) {
		require_once("mod/proxy.php");

		$result = array();

		$a = $this->get_app();

		$item = $this->get_data();
		$edited = false;
		if (strcmp($item['created'], $item['edited'])<>0) {
		      $edited = array(
			  'label' => t('This entry was edited'),
			  'date' => datetime_convert('UTC', date_default_timezone_get(), $item['edited'], 'r'),
			  'relative' => relative_date($item['edited'])
		      );
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
		$total_children = $this->count_descendants();

		$conv = $this->get_conversation();


		$lock = ((($item['private'] == 1) || (($item['uid'] == local_user()) && (strlen($item['allow_cid']) || strlen($item['allow_gid']) 
			|| strlen($item['deny_cid']) || strlen($item['deny_gid']))))
			? t('Private Message')
			: false);
		$shareable = ((($conv->get_profile_owner() == local_user()) && ($item['private'] != 1)) ? true : false);
		if(local_user() && link_compare($a->contact['url'],$item['author-link'])) {
			if ($item["event-id"] != 0)
				$edpost = array("events/event/".$item['event-id'], t("Edit"));
			else
				$edpost = array("editpost/".$item['id'], t("Edit"));
		} else
			$edpost = false;
		if(($this->get_data_value('uid') == local_user()) || $this->is_visiting())
			$dropping = true;

		$drop = array(
			'dropping' => $dropping,
			'pagedrop' => ((feature_enabled($conv->get_profile_owner(),'multi_delete')) ? $item['pagedrop'] : ''),
			'select' => t('Select'),
			'delete' => t('Delete'),
		);

		$filer = (($conv->get_profile_owner() == local_user()) ? t("save to folder") : false);

		$diff_author    = ((link_compare($item['url'],$item['author-link'])) ? false : true);
		$profile_name   = htmlentities(((strlen($item['author-name']))   && $diff_author) ? $item['author-name']   : $item['name']);
		if($item['author-link'] && (! $item['author-name']))
			$profile_name = $item['author-link'];

		$sp = false;
		$profile_link = best_link_url($item,$sp);
		if($profile_link === 'mailbox')
			$profile_link = '';
		if($sp)
			$sparkle = ' sparkle';
		else
			$profile_link = zrl($profile_link);

		if (!isset($item['author-thumb']) OR ($item['author-thumb'] == "")) {
			$author_contact = get_contact_details_by_url($item['author-link'], $conv->get_profile_owner());
			if ($author_contact["thumb"])
				$item['author-thumb'] = $author_contact["thumb"];
			else
				$item['author-thumb'] = $item['author-avatar'];
		}

		if (!isset($item['owner-thumb']) OR ($item['owner-thumb'] == "")) {
			$owner_contact = get_contact_details_by_url($item['owner-link'], $conv->get_profile_owner());
			if ($owner_contact["thumb"])
				$item['owner-thumb'] = $owner_contact["thumb"];
			else
				$item['owner-thumb'] = $item['owner-avatar'];
		}

		$locate = array('location' => $item['location'], 'coord' => $item['coord'], 'html' => '');
		call_hooks('render_location',$locate);
		$location = ((strlen($locate['html'])) ? $locate['html'] : render_location_dummy($locate));

		$searchpath = "search?tag=";
		$tags=array();
		$hashtags = array();
		$mentions = array();


		/*foreach(explode(',',$item['tag']) as $tag){
			$tag = trim($tag);
			if ($tag!="") {
				$t = bbcode($tag);
				$tags[] = $t;
				if($t[0] == '#')
					$hashtags[] = $t;
				elseif($t[0] == '@')
					$mentions[] = $t;
			}
		}*/

		// process action responses - e.g. like/dislike/attend/agree/whatever
		$response_verbs = array('like');
		if(feature_enabled($conv->get_profile_owner(),'dislike'))
			$response_verbs[] = 'dislike';
		if($item['object-type'] === ACTIVITY_OBJ_EVENT) {
			$response_verbs[] = 'attendyes';
			$response_verbs[] = 'attendno';
			$response_verbs[] = 'attendmaybe';
			if($conv->is_writable()) {
				$isevent = true;
				$attend = array( t('I will attend'), t('I will not attend'), t('I might attend'));
			}
		}

		$responses = get_responses($conv_responses,$response_verbs,$this,$item);

		foreach ($response_verbs as $value=>$verbs) {
			$responses[$verbs][output]  = ((x($conv_responses[$verbs],$item['uri'])) ? format_like($conv_responses[$verbs][$item['uri']],$conv_responses[$verbs][$item['uri'] . '-l'],$verbs,$item['uri']) : '');

		}

		/*
		 * We should avoid doing this all the time, but it depends on the conversation mode
		 * And the conv mode may change when we change the conv, or it changes its mode
		 * Maybe we should establish a way to be notified about conversation changes
		 */
		$this->check_wall_to_wall();

		if($this->is_wall_to_wall() && ($this->get_owner_url() == $this->get_redirect_url()))
			$osparkle = ' sparkle';

		if($this->is_toplevel()) {
			if($conv->get_profile_owner() == local_user()) {
				$isstarred = (($item['starred']) ? "starred" : "unstarred");

				$star = array(
					'do' => t("add star"),
					'undo' => t("remove star"),
					'toggle' => t("toggle star status"),
					'classdo' => (($item['starred']) ? "hidden" : ""),
					'classundo' => (($item['starred']) ? "" : "hidden"),
					'starred' =>  t('starred'),
				);
				$r = q("SELECT `ignored` FROM `thread` WHERE `uid` = %d AND `iid` = %d LIMIT 1",
					intval($item['uid']),
					intval($item['id'])
				);
				if (count($r)) {
					$ignore = array(
						'do' => t("ignore thread"),
						'undo' => t("unignore thread"),
						'toggle' => t("toggle ignore status"),
						'classdo' => (($r[0]['ignored']) ? "hidden" : ""),
						'classundo' => (($r[0]['ignored']) ? "" : "hidden"),
						'ignored' =>  t('ignored'),
					);
				}

				$tagger = '';
				if(feature_enabled($conv->get_profile_owner(),'commtag')) {
					$tagger = array(
						'add' => t("add tag"),
						'class' => "",
					);
				}
			}
		} else {
			$indent = 'comment';
		}

		if($conv->is_writable()) {
			$buttons = array(
				'like' => array( t("I like this \x28toggle\x29"), t("like")),
				'dislike' => ((feature_enabled($conv->get_profile_owner(),'dislike')) ? array( t("I don't like this \x28toggle\x29"), t("dislike")) : ''),
			);
			if ($shareable) $buttons['share'] = array( t('Share this'), t('share'));
		}

		$comment = $this->get_comment_box($indent);

		if(strcmp(datetime_convert('UTC','UTC',$item['created']),datetime_convert('UTC','UTC','now - 12 hours')) > 0){
			$shiny = 'shiny';
		}

		localize_item($item);

		if ($item["postopts"] and !get_config("system", "suppress_language")) {
			//$langdata = explode(";", $item["postopts"]);
			//$langstr = substr($langdata[0], 5)." (".round($langdata[1]*100, 1)."%)";
			$langstr = "";
			if (substr($item["postopts"], 0, 5) == "lang=") {
				$postopts = substr($item["postopts"], 5);

				$languages = explode(":", $postopts);

				if (sizeof($languages) == 1) {
					$languages = array();
					$languages[] = $postopts;
				}

				foreach ($languages as $language) {
					$langdata = explode(";", $language);
					if ($langstr != "")
						$langstr .= ", ";

					//$langstr .= $langdata[0]." (".round($langdata[1]*100, 1)."%)";
					$langstr .= round($langdata[1]*100, 1)."% ".$langdata[0];
				}
			}
		}

		$body = prepare_body($item,true);

		list($categories, $folders) = get_cats_and_terms($item);

		if($a->theme['template_engine'] === 'internal') {
			$body_e = template_escape($body);
			$text_e = strip_tags(template_escape($body));
			$name_e = template_escape($profile_name);
			$title_e = template_escape($item['title']);
			$location_e = template_escape($location);
			$owner_name_e = template_escape($this->get_owner_name());
		}
		else {
			$body_e = $body;
			$text_e = strip_tags($body);
			$name_e = $profile_name;
			$title_e = $item['title'];
			$location_e = $location;
			$owner_name_e = $this->get_owner_name();
		}

		// Disable features that aren't available in several networks
		if (($item["item_network"] != NETWORK_DFRN) AND isset($buttons["dislike"])) {
			unset($buttons["dislike"],$isevent);
			$tagger = '';
		}

		if (($item["item_network"] == NETWORK_FEED) AND isset($buttons["like"]))
			unset($buttons["like"]);

		if (($item["item_network"] == NETWORK_MAIL) AND isset($buttons["like"]))
			unset($buttons["like"]);

		// Diaspora isn't able to do likes on comments - but red does
		if (($item["item_network"] == NETWORK_DIASPORA) AND ($indent == 'comment') AND
			!diaspora::is_redmatrix($item["owner-link"]) AND isset($buttons["like"]))
			unset($buttons["like"]);

		// Diaspora doesn't has multithreaded comments
		if (($item["item_network"] == NETWORK_DIASPORA) AND ($indent == 'comment'))
			unset($comment);

		// Facebook can like comments - but it isn't programmed in the connector yet.
		if (($item["item_network"] == NETWORK_FACEBOOK) AND ($indent == 'comment') AND isset($buttons["like"]))
			unset($buttons["like"]);

		$tmp_item = array(
			'template' => $this->get_template(),

			'type' => implode("",array_slice(explode("/",$item['verb']),-1)),
			'tags' => $item['tags'],
			'hashtags' => $item['hashtags'],
			'mentions' => $item['mentions'],
			'txt_cats' => t('Categories:'),
			'txt_folders' => t('Filed under:'),
			'has_cats' => ((count($categories)) ? 'true' : ''),
			'has_folders' => ((count($folders)) ? 'true' : ''),
			'categories' => $categories,
			'folders' => $folders,
			'body' => $body_e,
			'text' => $text_e,
			'id' => $this->get_id(),
			'guid' => urlencode($item['guid']),
			'isevent' => $isevent,
			'attend' => $attend,
			'linktitle' => sprintf( t('View %s\'s profile @ %s'), $profile_name, ((strlen($item['author-link'])) ? $item['author-link'] : $item['url'])),
			'olinktitle' => sprintf( t('View %s\'s profile @ %s'), htmlentities($this->get_owner_name()), ((strlen($item['owner-link'])) ? $item['owner-link'] : $item['url'])),
			'to' => t('to'),
			'via' => t('via'),
			'wall' => t('Wall-to-Wall'),
			'vwall' => t('via Wall-To-Wall:'),
			'profile_url' => $profile_link,
			'item_photo_menu' => item_photo_menu($item),
			'name' => $name_e,
			'thumb' => $a->remove_baseurl(proxy_url($item['author-thumb'], false, PROXY_SIZE_THUMB)),
			'osparkle' => $osparkle,
			'sparkle' => $sparkle,
			'title' => $title_e,
			'localtime' => datetime_convert('UTC', date_default_timezone_get(), $item['created'], 'r'),
			'ago' => (($item['app']) ? sprintf( t('%s from %s'),relative_date($item['created']),$item['app']) : relative_date($item['created'])),
			'app' => $item['app'],
			'created' => relative_date($item['created']),
			'lock' => $lock,
			'location' => $location_e,
			'indent' => $indent,
			'shiny' => $shiny,
			'owner_url' => $this->get_owner_url(),
			'owner_photo' => $a->remove_baseurl(proxy_url($item['owner-thumb'], false, PROXY_SIZE_THUMB)),
			'owner_name' => htmlentities($owner_name_e),
			'plink' => get_plink($item),
			'edpost'    => ((feature_enabled($conv->get_profile_owner(),'edit_posts')) ? $edpost : ''),
			'isstarred' => $isstarred,
			'star'      => ((feature_enabled($conv->get_profile_owner(),'star_posts')) ? $star : ''),
			'ignore'      => ((feature_enabled($conv->get_profile_owner(),'ignore_posts')) ? $ignore : ''),
			'tagger'	=> $tagger,
			'filer'     => ((feature_enabled($conv->get_profile_owner(),'filing')) ? $filer : ''),
			'drop' => $drop,
			'vote' => $buttons,
			'like' => $responses['like']['output'],
			'dislike'   => $responses['dislike']['output'],
			'responses' => $responses,
			'switchcomment' => t('Comment'),
			'comment' => $comment,
			'previewing' => ($conv->is_preview() ? ' preview ' : ''),
			'wait' => t('Please wait'),
			'thread_level' => $thread_level,
			'postopts' => $langstr,
			'edited' => $edited,
			'network' => $item["item_network"],
			'network_name' => network_to_name($item['item_network'], $profile_link),
		);

		$arr = array('item' => $item, 'output' => $tmp_item);
		call_hooks('display_item', $arr);

		$result = $arr['output'];

		$result['children'] = array();
		$children = $this->get_children();
		$nb_children = count($children);
		if($nb_children > 0) {
			foreach($children as $child) {
				$result['children'][] = $child->get_template_data($conv_responses, $thread_level + 1);
			}
			// Collapse
			if(($nb_children > 2) || ($thread_level > 1)) {
				$result['children'][0]['comment_firstcollapsed'] = true;
				$result['children'][0]['num_comments'] = sprintf( tt('%d comment','%d comments',$total_children),$total_children );
				$result['children'][0]['hidden_comments_num'] = $total_children;
				$result['children'][0]['hidden_comments_text'] = tt('comment', 'comments', $total_children);
				$result['children'][0]['hide_text'] = t('show more');
				if($thread_level > 1) {
					$result['children'][$nb_children - 1]['comment_lastcollapsed'] = true;
				}
				else {
					$result['children'][$nb_children - 3]['comment_lastcollapsed'] = true;
				}
			}
		}

	if ($this->is_toplevel()) {
	    $result['total_comments_num'] = "$total_children";
	    $result['total_comments_text'] = tt('comment', 'comments', $total_children);
	}

		$result['private'] = $item['private'];
		$result['toplevel'] = ($this->is_toplevel() ? 'toplevel_item' : '');

		if($this->is_threaded()) {
			$result['flatten'] = false;
			$result['threaded'] = true;
		}
		else {
			$result['flatten'] = true;
			$result['threaded'] = false;
		}

		return $result;
	}

	public function get_id() {
		return $this->get_data_value('id');
	}

	public function is_threaded() {
		return $this->threaded;
	}

	/**
	 * Add a child item
	 */
	public function add_child($item) {
		$item_id = $item->get_id();
		if(!$item_id) {
			logger('[ERROR] Item::add_child : Item has no ID!!', LOGGER_DEBUG);
			return false;
		}
		if($this->get_child($item->get_id())) {
			logger('[WARN] Item::add_child : Item already exists ('. $item->get_id() .').', LOGGER_DEBUG);
			return false;
		}
		/*
		 * Only add what will be displayed
		 */
		if($item->get_data_value('network') === NETWORK_MAIL && local_user() != $item->get_data_value('uid')) {
			return false;
		}
		if(activity_match($item->get_data_value('verb'),ACTIVITY_LIKE) || activity_match($item->get_data_value('verb'),ACTIVITY_DISLIKE)) {
			return false;
		}

		$item->set_parent($this);
		$this->children[] = $item;
		return end($this->children);
	}

	/**
	 * Get a child by its ID
	 */
	public function get_child($id) {
		foreach($this->get_children() as $child) {
			if($child->get_id() == $id)
				return $child;
		}
		return null;
	}

	/**
	 * Get all ou children
	 */
	public function get_children() {
		return $this->children;
	}

	/**
	 * Set our parent
	 */
	protected function set_parent($item) {
		$parent = $this->get_parent();
		if($parent) {
			$parent->remove_child($this);
		}
		$this->parent = $item;
		$this->set_conversation($item->get_conversation());
	}

	/**
	 * Remove our parent
	 */
	protected function remove_parent() {
		$this->parent = null;
		$this->conversation = null;
	}

	/**
	 * Remove a child
	 */
	public function remove_child($item) {
		$id = $item->get_id();
		foreach($this->get_children() as $key => $child) {
			if($child->get_id() == $id) {
				$child->remove_parent();
				unset($this->children[$key]);
				// Reindex the array, in order to make sure there won't be any trouble on loops using count()
				$this->children = array_values($this->children);
				return true;
			}
		}
		logger('[WARN] Item::remove_child : Item is not a child ('. $id .').', LOGGER_DEBUG);
		return false;
	}

	/**
	 * Get parent item
	 */
	protected function get_parent() {
		return $this->parent;
	}

	/**
	 * set conversation
	 */
	public function set_conversation($conv) {
		$previous_mode = ($this->conversation ? $this->conversation->get_mode() : '');

		$this->conversation = $conv;

		// Set it on our children too
		foreach($this->get_children() as $child)
			$child->set_conversation($conv);
	}

	/**
	 * get conversation
	 */
	public function get_conversation() {
		return $this->conversation;
	}

	/**
	 * Get raw data
	 *
	 * We shouldn't need this
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Get a data value
	 *
	 * Returns:
	 *      _ value on success
	 *      _ false on failure
	 */
	public function get_data_value($name) {
		if(!isset($this->data[$name])) {
//			logger('[ERROR] Item::get_data_value : Item has no value name "'. $name .'".', LOGGER_DEBUG);
			return false;
		}

		return $this->data[$name];
	}

	/**
	 * Set template
	 */
	private function set_template($name) {
		$a = get_app();

		if(!x($this->available_templates, $name)) {
			logger('[ERROR] Item::set_template : Template not available ("'. $name .'").', LOGGER_DEBUG);
			return false;
		}

		$this->template = $this->available_templates[$name];
	}

	/**
	 * Get template
	 */
	private function get_template() {
		return $this->template;
	}

	/**
	 * Check if this is a toplevel post
	 */
	private function is_toplevel() {
		return $this->toplevel;
	}

	/**
	 * Check if this is writable
	 */
	private function is_writable() {
		$conv = $this->get_conversation();

		if($conv) {
			// This will allow us to comment on wall-to-wall items owned by our friends
			// and community forums even if somebody else wrote the post.

			// bug #517 - this fixes for conversation owner
			if($conv->get_mode() == 'profile' && $conv->get_profile_owner() == local_user())
				return true; 

			// this fixes for visitors
			return ($this->writable || ($this->is_visiting() && $conv->get_mode() == 'profile'));
		}
		return $this->writable;
	}

	/**
	 * Count the total of our descendants
	 */
	private function count_descendants() {
		$children = $this->get_children();
		$total = count($children);
		if($total > 0) {
			foreach($children as $child) {
				$total += $child->count_descendants();
			}
		}
		return $total;
	}

	/**
	 * Get the template for the comment box
	 */
	private function get_comment_box_template() {
		return $this->comment_box_template;
	}

	/**
	 * Get the comment box
	 *
	 * Returns:
	 *      _ The comment box string (empty if no comment box)
	 *      _ false on failure
	 */
	private function get_comment_box($indent) {
		$a = $this->get_app();
		if(!$this->is_toplevel() && !(get_config('system','thread_allow') && $a->theme_thread_allow)) {
			return '';
		}

		$comment_box = '';
		$conv = $this->get_conversation();
		$template = get_markup_template($this->get_comment_box_template());
		$ww = '';
		if( ($conv->get_mode() === 'network') && $this->is_wall_to_wall() )
			$ww = 'ww';

		if($conv->is_writable() && $this->is_writable()) {
			$qc = $qcomment =  null;

			/*
			 * Hmmm, code depending on the presence of a particular plugin?
			 * This should be better if done by a hook
			 */
			if(in_array('qcomment',$a->plugins)) {
				$qc = ((local_user()) ? get_pconfig(local_user(),'qcomment','words') : null);
				$qcomment = (($qc) ? explode("\n",$qc) : null);
			}
			$comment_box = replace_macros($template,array(
				'$return_path' => $a->query_string,
				'$threaded' => $this->is_threaded(),
//				'$jsreload' => (($conv->get_mode() === 'display') ? $_SESSION['return_url'] : ''),
				'$jsreload' => '',
				'$type' => (($conv->get_mode() === 'profile') ? 'wall-comment' : 'net-comment'),
				'$id' => $this->get_id(),
				'$parent' => $this->get_id(),
				'$qcomment' => $qcomment,
				'$profile_uid' =>  $conv->get_profile_owner(),
				'$mylink' => $a->remove_baseurl($a->contact['url']),
				'$mytitle' => t('This is you'),
				'$myphoto' => $a->remove_baseurl($a->contact['thumb']),
				'$comment' => t('Comment'),
				'$submit' => t('Submit'),
				'$edbold' => t('Bold'),
				'$editalic' => t('Italic'),
				'$eduline' => t('Underline'),
				'$edquote' => t('Quote'),
				'$edcode' => t('Code'),
				'$edimg' => t('Image'),
				'$edurl' => t('Link'),
				'$edvideo' => t('Video'),
				'$preview' => ((feature_enabled($conv->get_profile_owner(),'preview')) ? t('Preview') : ''),
				'$indent' => $indent,
				'$sourceapp' => t($a->sourcename),
				'$ww' => (($conv->get_mode() === 'network') ? $ww : ''),
				'$rand_num' => random_digits(12)
			));
		}

		return $comment_box;
	}

	private function get_redirect_url() {
		return $this->redirect_url;
	}

	/**
	 * Check if we are a wall to wall item and set the relevant properties
	 */
	protected function check_wall_to_wall() {
		$a = $this->get_app();
		$conv = $this->get_conversation();
		$this->wall_to_wall = false;

		if($this->is_toplevel()) {
			if($conv->get_mode() !== 'profile') {
				if($this->get_data_value('wall') AND !$this->get_data_value('self')) {
					// On the network page, I am the owner. On the display page it will be the profile owner.
					// This will have been stored in $a->page_contact by our calling page.
					// Put this person as the wall owner of the wall-to-wall notice.

					$this->owner_url = zrl($a->page_contact['url']);
					$this->owner_photo = $a->page_contact['thumb'];
					$this->owner_name = $a->page_contact['name'];
					$this->wall_to_wall = true;
				}
				else if($this->get_data_value('owner-link')) {

					$owner_linkmatch = (($this->get_data_value('owner-link')) && link_compare($this->get_data_value('owner-link'),$this->get_data_value('author-link')));
					$alias_linkmatch = (($this->get_data_value('alias')) && link_compare($this->get_data_value('alias'),$this->get_data_value('author-link')));
					$owner_namematch = (($this->get_data_value('owner-name')) && $this->get_data_value('owner-name') == $this->get_data_value('author-name'));

					if((! $owner_linkmatch) && (! $alias_linkmatch) && (! $owner_namematch)) {

						// The author url doesn't match the owner (typically the contact)
						// and also doesn't match the contact alias. 
						// The name match is a hack to catch several weird cases where URLs are 
						// all over the park. It can be tricked, but this prevents you from
						// seeing "Bob Smith to Bob Smith via Wall-to-wall" and you know darn
						// well that it's the same Bob Smith. 

						// But it could be somebody else with the same name. It just isn't highly likely. 


						$this->owner_photo = $this->get_data_value('owner-avatar');
						$this->owner_name = $this->get_data_value('owner-name');
						$this->wall_to_wall = true;
						// If it is our contact, use a friendly redirect link
						if((link_compare($this->get_data_value('owner-link'),$this->get_data_value('url'))) 
							&& ($this->get_data_value('network') === NETWORK_DFRN)) {
							$this->owner_url = $this->get_redirect_url();
						}
						else
							$this->owner_url = zrl($this->get_data_value('owner-link'));
					}
				}
			}
		}

		if(!$this->wall_to_wall) {
			$this->set_template('wall');
			$this->owner_url = '';
			$this->owner_photo = '';
			$this->owner_name = '';
		}
	}

	private function is_wall_to_wall() {
		return $this->wall_to_wall;
	}

	private function get_owner_url() {
		return $this->owner_url;
	}

	private function get_owner_photo() {
		return $this->owner_photo;
	}

	private function get_owner_name() {
		return $this->owner_name;
	}

	private function is_visiting() {
		return $this->visiting;
	}




}
?>
