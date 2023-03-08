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

namespace Friendica\Module\Post\Tag;

use Friendica\App;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Module\Response;
use Friendica\Protocol\Activity;
use Friendica\Protocol\Delivery;
use Friendica\Util\Profiler;
use Friendica\Util\XML;
use Psr\Log\LoggerInterface;

/**
 * Asynchronous post tagging endpoint. Only used in Ajax calls.
 */
class Add extends \Friendica\BaseModule
{
	/** @var IHandleUserSessions */
	private $session;

	public function __construct(IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session = $session;
	}

	protected function post(array $request = [])
	{
		if (!$this->session->isAuthenticated()) {
			return;
		}

		$term = trim($request['term'] ?? '');
		// no commas allowed
		$term = str_replace([',', ' ', '<', '>'], ['', '_', '', ''], $term);

		if (!$term) {
			return;
		}

		$item_id = $this->parameters['item_id'];

		$this->logger->debug('Tag', ['term' => $term, 'item_id' => $item_id]);

		$item = Post::selectFirst([], ['id' => $item_id]);
		if (!$item) {
			$this->logger->info('Item not found', ['item_id' => $item_id]);
			return;
		}

		$owner_uid = $item['uid'];
		if ($this->session->getLocalUserId() != $owner_uid) {
			return;
		}

		$contact = Contact::selectFirst([], ['self' => true, 'uid' => $this->session->getLocalUserId()]);
		if (!$contact) {
			$this->logger->warning('Self contact not found.', ['uid' => $this->session->getLocalUserId()]);
			return;
		}

		$targettype = $item['resource-id'] ? Activity\ObjectType::IMAGE : Activity\ObjectType::NOTE;
		$link       = XML::escape('<link rel="alternate" type="text/html" href="' . $this->baseUrl . '/display/' . $item['guid'] . '" />' . "\n");
		$body       = XML::escape($item['body']);

		$target = <<< EOT
	<target>
		<type>$targettype</type>
		<local>1</local>
		<id>{$item['uri']}</id>
		<link>$link</link>
		<title></title>
		<content>$body</content>
	</target>
EOT;

		$objtype = Activity\ObjectType::TAGTERM;
		$tagid   = $this->baseUrl . '/search?tag=' . urlencode($term);
		$xterm   = XML::escape($term);

		$obj = <<< EOT
	<object>
		<type>$objtype</type>
		<local>1</local>
		<id>$tagid</id>
		<link>$tagid</link>
		<title>$xterm</title>
		<content>$xterm</content>
	</object>
EOT;

		$tagger_link  = '[url=' . $contact['url'] . ']' . $contact['name'] . '[/url]';
		$aauthor_link = '[url=' . $item['author-link'] . ']' . $item['author-name'] . '[/url]';
		$post_link    = '[url=' . $item['plink'] . ']' . ($item['resource-id'] ? $this->t('photo') : $this->t('post')) . '[/url]';
		$term_link    = '#[url=' . $tagid . ']' . $term . '[/url]';

		$post = [
			'guid'          => System::createUUID(),
			'uri'           => Item::newURI(),
			'uid'           => $owner_uid,
			'contact-id'    => $contact['id'],
			'wall'          => $item['wall'],
			'gravity'       => Item::GRAVITY_COMMENT,
			'parent'        => $item['id'],
			'thr-parent'    => $item['uri'],
			'owner-name'    => $item['author-name'],
			'owner-link'    => $item['author-link'],
			'owner-avatar'  => $item['author-avatar'],
			'author-name'   => $contact['name'],
			'author-link'   => $contact['url'],
			'author-avatar' => $contact['thumb'],
			'body'          => $this->t('%1$s tagged %2$s\'s %3$s with %4$s', $tagger_link, $aauthor_link, $post_link, $term_link),
			'verb'          => Activity::TAG,
			'target-type'   => $targettype,
			'target'        => $target,
			'object-type'   => $objtype,
			'object'        => $obj,
			'private'       => $item['private'],
			'allow_cid'     => $item['allow_cid'],
			'allow_gid'     => $item['allow_gid'],
			'deny_cid'      => $item['deny_cid'],
			'deny_gid'      => $item['deny_gid'],
			'visible'       => 1,
			'unseen'        => 1,
			'origin'        => 1,
		];

		$post_id = Item::insert($post);

		if (!$item['visible']) {
			Item::update(['visible' => true], ['id' => $item['id']]);
		}

		Tag::store($item['uri-id'], Tag::HASHTAG, $term);

		$post['id'] = $post_id;
		Hook::callAll('post_local_end', $post);

		$post = Post::selectFirst(['uri-id', 'uid'], ['id' => $post_id]);

		Worker::add(Worker::PRIORITY_HIGH, 'Notifier', Delivery::POST, $post['uri-id'], $post['uid']);
		System::exit();
	}
}
