<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object;

use Friendica\Content\Conversation;
use Friendica\Core\Protocol;
use Friendica\DI;
use Friendica\Protocol\Activity;
use Friendica\Security\Security;

/**
 * A list of threads
 *
 * We should think about making this a SPL Iterator
 */
class Thread
{
	/** @var Post[] */
	private $parents       = [];
	private $mode          = null;
	private $writable      = false;
	private $profile_owner = 0;
	private $preview       = false;

	/**
	 * Constructor
	 *
	 * @param string  $mode     The mode
	 * @param boolean $preview  Are we in the preview mode?
	 * @param boolean $writable Override the writable check
	 * @throws \Exception
	 */
	public function __construct($mode, $preview, $writable = false)
	{
		$this->setMode($mode, $writable);
		$this->preview = $preview;
	}

	/**
	 * Set the mode we'll be displayed on
	 *
	 * @param string  $mode     The mode to set
	 * @param boolean $writable Override the writable check
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function setMode($mode, $writable)
	{
		if ($this->getMode() == $mode) {
			return;
		}

		$appHelper = DI::appHelper();

		switch ($mode) {
			case Conversation::MODE_NETWORK:
			case Conversation::MODE_NOTES:
				$this->profile_owner = DI::userSession()->getLocalUserId();
				$this->writable      = true;
				break;
			case Conversation::MODE_PROFILE:
			case Conversation::MODE_DISPLAY:
				$this->profile_owner = $appHelper->getProfileOwner();
				$this->writable      = Security::canWriteToUserWall($this->profile_owner) || $writable;
				break;
			case Conversation::MODE_CHANNEL:
			case Conversation::MODE_COMMUNITY:
			case Conversation::MODE_CONTACTS:
				$this->profile_owner = 0;
				$this->writable      = $writable;
				break;
			default:
				DI::logger()->info('[ERROR] Conversation::setMode : Unhandled mode ('. $mode .').');
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
	 * @param Post $item The item to insert
	 *
	 * @return mixed The inserted item on success
	 *               false on failure
	 * @throws \Exception
	 */
	public function addParent(Post $item)
	{
		$item_id = $item->getId();

		if (!$item_id) {
			DI::logger()->info('[ERROR] Conversation::addThread : Item has no ID!!');
			return false;
		}

		if ($this->getParent($item->getId())) {
			DI::logger()->info('[WARN] Conversation::addThread : Thread already exists ('. $item->getId() .').');
			return false;
		}

		/*
		 * Only add will be displayed
		 */
		if ($item->getDataValue('network') === Protocol::MAIL && DI::userSession()->getLocalUserId() != $item->getDataValue('uid')) {
			DI::logger()->info('[WARN] Conversation::addThread : Thread is a mail ('. $item->getId() .').');
			return false;
		}

		if ($item->getDataValue('verb') === Activity::LIKE || $item->getDataValue('verb') === Activity::DISLIKE) {
			DI::logger()->info('[WARN] Conversation::addThread : Thread is a (dis)like ('. $item->getId() .').');
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
	 * @param array $conv_responses data
	 * @param string $formSecurityToken A 'contact_action' form security token
	 *
	 * @return mixed The data requested on success
	 *               false on failure
	 * @throws \Exception
	 */
	public function getTemplateData(array $conv_responses, string $formSecurityToken)
	{
		$result = [];

		foreach ($this->parents as $item) {
			if ($item->getDataValue('network') === Protocol::MAIL && DI::userSession()->getLocalUserId() != $item->getDataValue('uid')) {
				continue;
			}

			$item_data = $item->getTemplateData($conv_responses, $formSecurityToken);

			if (!$item_data) {
				DI::logger()->info('[ERROR] Conversation::getTemplateData : Failed to get item template data ('. $item->getId() .').');
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
