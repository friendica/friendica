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

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
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
		} else {
			$ownvotes = [];
		}

		return new \Friendica\Object\Api\Mastodon\Poll($question, $options, $expired, $votes, $ownvotes);
	}
}
