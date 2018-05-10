<?php
/**
 * @file src/Object/Thread.php
 */
namespace Friendica\Object;

use Friendica\BaseObject;
use Friendica\Object\Post;

require_once 'boot.php';
require_once 'include/text.php';

/**
 * A list of threads
 *
 * We should think about making this a SPL Iterator
 */
class Thread extends BaseObject
{
	private $parents = [];
	private $mode = null;
	private $writable = false;
	private $profile_owner = 0;
	private $preview = false;

	/**
	 * Constructor
	 *
	 * @param string  $mode    The mode
	 * @param boolean $preview Are we in the preview mode?
	 * @param boolean $writable Override the writable check
	 */
	public function __construct($mode, $preview, $writable = false)
	{
		$this->setMode($mode, $writable);
		$this->preview = $preview;
	}

	/**
	 * Set the mode we'll be displayed on
	 *
	 * @param string $mode The mode to set
	 * @param boolean $writable Override the writable check
	 *
	 * @return void
	 */
	private function setMode($mode, $writable)
	{
		if ($this->getMode() == $mode) {
			return;
		}

		$a = self::getApp();

		switch ($mode) {
			case 'network':
			case 'notes':
				$this->profile_owner = local_user();
				$this->writable = true;
				break;
			case 'profile':
				$this->profile_owner = $a->profile['profile_uid'];
				$this->writable = can_write_wall($this->profile_owner);
				break;
			case 'display':
				$this->profile_owner = $a->profile['uid'];
				$this->writable = can_write_wall($this->profile_owner) || $writable;
				break;
			case 'community':
				$this->profile_owner = 0;
				$this->writable = $writable;
				break;
			default:
				logger('[ERROR] Conversation::setMode : Unhandled mode ('. $mode .').', LOGGER_DEBUG);
				return false;
				break;
		}
		$this->mode = $mode;
	}

	/**
	 * Get mode
	 *
	 * @return string
	 */
	public function getMode()
	{
		return $this->mode;
	}

	/**
	 * Check if page is writable
	 *
	 * @return boolean
	 */
	public function isWritable()
	{
		return $this->writable;
	}

	/**
	 * Check if page is a preview
	 *
	 * @return boolean
	 */
	public function isPreview()
	{
		return $this->preview;
	}

	/**
	 * Get profile owner
	 *
	 * @return integer
	 */
	public function getProfileOwner()
	{
		return $this->profile_owner;
	}

	/**
	 * Add a thread to the conversation
	 *
	 * @param object $item The item to insert
	 *
	 * @return mixed The inserted item on success
	 *               false on failure
	 */
	public function addParent(Post $item)
	{
		$item_id = $item->getId();

		if (!$item_id) {
			logger('[ERROR] Conversation::addThread : Item has no ID!!', LOGGER_DEBUG);
			return false;
		}

		if ($this->getParent($item->getId())) {
			logger('[WARN] Conversation::addThread : Thread already exists ('. $item->getId() .').', LOGGER_DEBUG);
			return false;
		}

		/*
		 * Only add will be displayed
		 */
		if ($item->getDataValue('network') === NETWORK_MAIL && local_user() != $item->getDataValue('uid')) {
			logger('[WARN] Conversation::addThread : Thread is a mail ('. $item->getId() .').', LOGGER_DEBUG);
			return false;
		}

		if ($item->getDataValue('verb') === ACTIVITY_LIKE || $item->getDataValue('verb') === ACTIVITY_DISLIKE) {
			logger('[WARN] Conversation::addThread : Thread is a (dis)like ('. $item->getId() .').', LOGGER_DEBUG);
			return false;
		}

		$item->setThread($this);
		$this->parents[] = $item;

		return end($this->parents);
	}

	/**
	 * Get data in a form usable by a conversation template
	 *
	 * We should find a way to avoid using those arguments (at least most of them)
	 *
	 * @param object $conv_responses data
	 *
	 * @return mixed The data requested on success
	 *               false on failure
	 */
	public function getTemplateData($conv_responses)
	{
		$a = self::getApp();
		$result = [];
		$i = 0;

		foreach ($this->parents as $item) {
			if ($item->getDataValue('network') === NETWORK_MAIL && local_user() != $item->getDataValue('uid')) {
				continue;
			}

			$item_data = $item->getTemplateData($conv_responses);

			if (!$item_data) {
				logger('[ERROR] Conversation::getTemplateData : Failed to get item template data ('. $item->getId() .').', LOGGER_DEBUG);
				return false;
			}
			$result[] = $item_data;
		}

		return $result;
	}

	/**
	 * Get a thread based on its item id
	 *
	 * @param integer $id Item id
	 *
	 * @return mixed The found item on success
	 *               false on failure
	 */
	private function getParent($id)
	{
		foreach ($this->parents as $item) {
			if ($item->getId() == $id) {
				return $item;
			}
		}

		return false;
	}
}
