<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Model\Contact;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;
use Friendica\Util\DateTimeFormat;

class Poll extends BaseFactory
{
	/**
	 * @param int $id  Id the question
	 * @param int $uid Item user
	 *
	 * @return \Friendica\Object\Api\Mastodon\Poll
	 * @throws HTTPException\NotFoundException
	 */
	public function createFromId(int $id, int $uid = 0): \Friendica\Object\Api\Mastodon\Poll
	{
		$question = Post\Question::getById($id);
		if (empty($question)) {
			throw new HTTPException\NotFoundException('Poll with id ' . $id . ' not found' . ($uid ? ' for user ' . $uid : '.'));
		}

		if (!Post::exists(['uri-id' => $question['uri-id'], 'uid' => [0, $uid]])) {
			throw new HTTPException\NotFoundException('Poll with id ' . $id . ' not found' . ($uid ? ' for user ' . $uid : '.'));
		}

		$question_options = Post\QuestionOption::getByURIId($question['uri-id']);
		if (empty($question_options)) {
			throw new HTTPException\NotFoundException('No options found for Poll with id ' . $id . ' not found' . ($uid ? ' for user ' . $uid : '.'));
		}

		$expired = false;

		if (!empty($question['end-time'])) {
			$expired = DateTimeFormat::utcNow() > DateTimeFormat::utc($question['end-time']);
		}

		$votes   = 0;
		$options = [];

		foreach ($question_options as $option) {
			$options[$option['id']] = ['title' => $option['name'], 'votes_count' => $option['replies']];
			$votes += $option['replies'];
		}

		if (empty($uid)) {
			$ownvotes = null;
			$voted    = null;
		} else {
			$author_id = Contact::getPublicIdByUserId($uid);
			$ownvotes  = $author_id ? Post\QuestionVote::getChoices($question['uri-id'], $author_id) : [];
			$voted     = !empty($ownvotes);
		}

		return new \Friendica\Object\Api\Mastodon\Poll($question, $options, $expired, $votes, $ownvotes, $voted);
	}
}
