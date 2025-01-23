<?php

/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 */

namespace Friendica\Protocol\ATProtocol;

use Friendica\App\BaseURL;
use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\Item;
use Friendica\Model\ItemURI;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Protocol\Activity;
use Friendica\Protocol\ATProtocol;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;
use stdClass;

/**
 * Class to process AT protocol messages
 */
class Processor
{
	/** @var Database */
	private $db;

	/** @var LoggerInterface */
	private $logger;

	/** @var BaseURL */
	private $baseURL;

	/** @var ATProtocol */
	private $atprotocol;

	/** @var Actor */
	private $actor;

	public function __construct(Database $database, LoggerInterface $logger, BaseURL $baseURL, ATProtocol $atprotocol, Actor $actor)
	{
		$this->db         = $database;
		$this->logger     = $logger;
		$this->baseURL    = $baseURL;
		$this->atprotocol = $atprotocol;
		$this->actor      = $actor;
	}

	public function processAccount(stdClass $data)
	{
		$fields = [
			'archive' => !$data->account->active,
			'failed'  => !$data->account->active,
			'updated' => DateTimeFormat::utc($data->account->time, DateTimeFormat::MYSQL)
		];

		$this->logger->notice('Process account', ['did' => $data->identity->did, 'fields' => $fields]);

		Contact::update($fields, ['nurl' => $data->identity->did, 'network' => Protocol::BLUESKY]);
	}

	public function processIdentity(stdClass $data)
	{
		$fields = [
			'alias'   => ATProtocol::WEB . '/profile/' . $data->identity->handle,
			'nick'    => $data->identity->handle,
			'addr'    => $data->identity->handle,
			'updated' => DateTimeFormat::utc($data->identity->time, DateTimeFormat::MYSQL),
		];

		$this->logger->notice('Process identity', ['did' => $data->identity->did, 'fields' => $fields]);

		Contact::update($fields, ['nurl' => $data->identity->did, 'network' => Protocol::BLUESKY]);
	}

	public function performBlocks(stdClass $data, int $uid)
	{
		if (!$uid) {
			$this->logger->info('Not a block from a local user');
			return;
		}

		if (empty($data->commit->record->subject)) {
			$this->logger->info('No subject in data', ['data' => $data]);
			return;
		}

		$contact = Contact::selectFirst(['id'], ['nurl' => $data->commit->record->subject, 'uid' => 0]);
		if (empty($contact['id'])) {
			$this->logger->info('Contact not found', ['did' => $data->commit->record->subject]);
			return;
		}

		// @todo unblock doesn't provide a subject. We will only arrive here, wenn the operation is "create".
		Contact\User::setBlocked($contact['id'], $uid, ($data->commit->operation == 'create'), true);
		$this->logger->info('Contact blocked', ['id' => $contact['id'], 'did' => $data->commit->record->subject, 'uid' => $uid]);
	}

	public function deleteRecord(stdClass $data)
	{
		$uri     = 'at://' . $data->did . '/' . $data->commit->collection . '/' . $data->commit->rkey;
		$itemuri = $this->db->selectFirst('item-uri', ['id'], ["`uri` LIKE ?", $uri . ':%']);
		if (empty($itemuri['id'])) {
			$this->logger->info('URI not found', ['url' => $uri]);
			return;
		}

		$condition = ['uri-id' => $itemuri['id'], 'author-link' => $data->did, 'network' => Protocol::BLUESKY];
		if (!Post::exists($condition)) {
			$this->logger->info('Record not found', $condition);
			return;
		}
		Item::markForDeletion($condition);
		$this->logger->info('Record deleted', $condition);
	}

	public function createPost(stdClass $data, array $uids, bool $dont_fetch)
	{
		$parent = '';

		if (!empty($data->commit->record->reply)) {
			$root   = $this->getUri($data->commit->record->reply->root);
			$parent = $this->getUri($data->commit->record->reply->parent);
			$uids   = $this->getPostUids($root, true);
			if (!$uids) {
				$this->logger->debug('Comment is not imported since the root post is not found.', ['root' => $root, 'parent' => $parent]);
				return;
			}
			if ($dont_fetch && !$this->getPostUids($parent, false)) {
				$this->logger->debug('Comment is not imported since the parent post is not found.', ['root' => $root, 'parent' => $parent]);
				return;
			}
		}

		foreach ($uids as $uid) {
			$item = [];
			$item = $this->getHeaderFromJetstream($data, $uid);
			if (empty($item)) {
				continue;
			}

			if (!empty($root)) {
				$item['parent-uri'] = $root;
				$item['thr-parent'] = $this->fetchMissingPost($parent, $uid, Item::PR_FETCHED, $item['contact-id'], 0, $parent, false, Conversation::PARCEL_JETSTREAM);
				$item['gravity']    = Item::GRAVITY_COMMENT;
			} else {
				$item['gravity'] = Item::GRAVITY_PARENT;
			}

			$item['body']                  = $this->parseFacets($data->commit->record, $item['uri-id']);
			$item['transmitted-languages'] = $data->commit->record->langs ?? [];

			if (!empty($data->commit->record->embed)) {
				if (empty($post)) {
					$uri  = 'at://' . $data->did . '/' . $data->commit->collection . '/' . $data->commit->rkey;
					$post = $this->atprotocol->XRPCGet('app.bsky.feed.getPostThread', ['uri' => $uri]);
					if (empty($post->thread->post->embed)) {
						$this->logger->notice('Post was not fetched', ['uri' => $uri, 'post' => $post]);
						return;
					}
				}
				$item['source'] = json_encode($post);
				$item           = $this->addMedia($post->thread->post->embed, $item, 0);
			}

			$id = Item::insert($item);

			if ($id) {
				$this->logger->info('Post inserted', ['id' => $id, 'guid' => $item['guid']]);
			} elseif (Post::exists(['uid' => $uid, 'uri-id' => $item['uri-id']])) {
				$this->logger->notice('Post was found', ['guid' => $item['guid'], 'uri' => $item['uri']]);
			} else {
				$this->logger->warning('Post was not inserted', ['guid' => $item['guid'], 'uri' => $item['uri']]);
			}
		}
	}

	public function createRepost(stdClass $data, array $uids, bool $dont_fetch)
	{
		if ($dont_fetch && !$this->getPostUids($this->getUri($data->commit->record->subject), true)) {
			$this->logger->debug('Repost is not imported since the subject is not found.', ['subject' => $this->getUri($data->commit->record->subject)]);
			return;
		}

		foreach ($uids as $uid) {
			$item = $this->getHeaderFromJetstream($data, $uid);
			if (empty($item)) {
				continue;
			}

			$item['gravity']    = Item::GRAVITY_ACTIVITY;
			$item['body']       = $item['verb'] = Activity::ANNOUNCE;
			$item['thr-parent'] = $this->getUri($data->commit->record->subject);
			$item['thr-parent'] = $this->fetchMissingPost($item['thr-parent'], 0, Item::PR_FETCHED, $item['contact-id'], 0, $item['thr-parent'], false, Conversation::PARCEL_JETSTREAM);

			$id = Item::insert($item);

			if ($id) {
				$this->logger->info('Repost inserted', ['id' => $id]);
			} elseif (Post::exists(['uid' => $uid, 'uri-id' => $item['uri-id']])) {
				$this->logger->notice('Repost was found', ['uri' => $item['uri']]);
			} else {
				$this->logger->warning('Repost was not inserted', ['uri' => $item['uri']]);
			}
		}
	}

	public function createLike(stdClass $data)
	{
		$uids = $this->getPostUids($this->getUri($data->commit->record->subject), false);
		if (!$uids) {
			$this->logger->debug('Like is not imported since the subject is not found.', ['subject' => $this->getUri($data->commit->record->subject)]);
			return;
		}
		foreach ($uids as $uid) {
			$item = $this->getHeaderFromJetstream($data, $uid);
			if (empty($item)) {
				continue;
			}

			$item['gravity']    = Item::GRAVITY_ACTIVITY;
			$item['body']       = $item['verb'] = Activity::LIKE;
			$item['thr-parent'] = $this->getPostUri($this->getUri($data->commit->record->subject), $uid);

			$id = Item::insert($item);

			if ($id) {
				$this->logger->info('Like inserted', ['id' => $id]);
			} elseif (Post::exists(['uid' => $uid, 'uri-id' => $item['uri-id']])) {
				$this->logger->notice('Like was found', ['uri' => $item['uri']]);
			} else {
				$this->logger->warning('Like was not inserted', ['uri' => $item['uri']]);
			}
		}
	}

	public function deleteFollow(stdClass $data, array $self): bool
	{
		return !empty($self[$data->did]);
	}

	public function createFollow(stdClass $data, array $self): bool
	{
		if (!empty($self[$data->did])) {
			$uid    = $self[$data->did];
			$target = $data->commit->record->subject;
			$rel    = Contact::SHARING;
			$this->logger->debug('Follow by a local user', ['uid' => $uid, 'following' => $target]);
		} elseif (!empty($self[$data->commit->record->subject])) {
			$uid    = $self[$data->commit->record->subject];
			$target = $data->did;
			$rel    = Contact::FOLLOWER;
			$this->logger->debug('New follower for a local user', ['uid' => $uid, 'follower' => $target]);
		} else {
			$this->logger->debug('No local part', ['did' => $data->did, 'target' => $data->commit->record->subject]);
			return false;
		}
		$contact = $this->actor->getContactByDID($target, $uid, $uid);
		if (empty($contact)) {
			$this->logger->notice('Contact not found', ['uid' => $uid, 'target' => $target]);
			return false;
		}
		Contact::update(['rel' => $rel | $contact['rel']], ['id' => $contact['id']]);
		return true;
	}

	public function processPost(stdClass $post, int $uid, int $post_reason, int $causer, int $level, int $protocol): int
	{
		$uri = $this->getUri($post);

		if ($uri_id = $this->fetchUriId($uri, $uid)) {
			return $uri_id;
		}

		if (empty($post->record)) {
			$this->logger->debug('Invalid post', ['uri' => $uri]);
			return 0;
		}

		$this->logger->debug('Importing post', ['uid' => $uid, 'indexedAt' => $post->indexedAt, 'uri' => $post->uri, 'cid' => $post->cid, 'root' => $post->record->reply->root ?? '']);

		$item = $this->getHeaderFromPost($post, $uri, $uid, $protocol);
		if (empty($item)) {
			return 0;
		}
		$item = $this->getContent($item, $post->record, $uri, $uid, $level);
		if (empty($item)) {
			return 0;
		}

		if (!empty($post->embed)) {
			$item = $this->addMedia($post->embed, $item, $level);
		}

		$item['restrictions'] = $this->getRestrictionsForUser($post, $item, $post_reason);

		if (empty($item['post-reason'])) {
			$item['post-reason'] = $post_reason;
		}

		if ($causer != 0) {
			$item['causer-id'] = $causer;
		}

		$id = Item::insert($item);

		if ($id) {
			$this->logger->info('Fetched post inserted', ['id' => $id, 'guid' => $item['guid']]);
		} elseif (Post::exists(['uid' => $uid, 'uri-id' => $item['uri-id']])) {
			$this->logger->notice('Fetched post was found', ['guid' => $item['guid'], 'uri' => $item['uri']]);
		} else {
			$this->logger->warning('Fetched post was not inserted', ['guid' => $item['guid'], 'uri' => $item['uri']]);
		}

		return $this->fetchUriId($uri, $uid);
	}

	private function getHeaderFromJetstream(stdClass $data, int $uid, int $protocol = Conversation::PARCEL_JETSTREAM): array
	{
		$contact = $this->actor->getContactByDID($data->did, $uid, 0);
		if (empty($contact)) {
			$this->logger->info('Contact not found for user', ['did' => $data->did, 'uid' => $uid]);
			return [];
		}

		$item = [
			'network'       => Protocol::BLUESKY,
			'protocol'      => $protocol,
			'uid'           => $uid,
			'wall'          => false,
			'uri'           => 'at://' . $data->did . '/' . $data->commit->collection . '/' . $data->commit->rkey . ':' . $data->commit->cid,
			'guid'          => $data->commit->cid,
			'created'       => DateTimeFormat::utc($data->commit->record->createdAt, DateTimeFormat::MYSQL),
			'private'       => Item::UNLISTED,
			'verb'          => Activity::POST,
			'contact-id'    => $contact['id'],
			'author-name'   => $contact['name'],
			'author-link'   => $contact['url'],
			'author-avatar' => $contact['avatar'],
			'owner-name'    => $contact['name'],
			'owner-link'    => $contact['url'],
			'owner-avatar'  => $contact['avatar'],
			'plink'         => $contact['alias'] . '/post/' . $data->commit->rkey,
			'source'        => json_encode($data),
		];

		if ((time() - strtotime($item['created'])) > 600) {
			$item['received'] = $item['created'];
		}

		if ($this->postExists($item['uri'], [$uid])) {
			$this->logger->info('Post already exists for user', ['uri' => $item['uri'], 'uid' => $uid]);
			return [];
		}

		$account          = Contact::selectFirstAccountUser(['pid'], ['id' => $contact['id']]);
		$item['owner-id'] = $item['author-id'] = $account['pid'];
		$item['uri-id']   = ItemURI::getIdByURI($item['uri']);

		if (in_array($contact['rel'], [Contact::SHARING, Contact::FRIEND])) {
			$item['post-reason'] = Item::PR_FOLLOWER;
		}

		if (!empty($data->commit->record->labels)) {
			foreach ($data->commit->record->labels as $label) {
				// Only flag posts as sensitive based on labels that had been provided by the author.
				// When "ver" is set to "1" it was flagged by some automated process.
				if (empty($label->ver)) {
					$item['sensitive']       = true;
					$item['content-warning'] = $label->val ?? '';
					$this->logger->debug('Sensitive content', ['uri-id' => $item['uri-id'], 'label' => $label]);
				}
			}
		}

		return $item;
	}

	public function getHeaderFromPost(stdClass $post, string $uri, int $uid, int $protocol): array
	{
		$parts = $this->getUriParts($uri);
		if (empty($post->author) || empty($post->cid) || empty($parts->rkey)) {
			return [];
		}
		$contact = $this->actor->getContactByDID($post->author->did, $uid, 0);
		if (empty($contact)) {
			$this->logger->info('Contact not found for user', ['did' => $post->author->did, 'uid' => $uid]);
			return [];
		}

		$item = [
			'network'       => Protocol::BLUESKY,
			'protocol'      => $protocol,
			'uid'           => $uid,
			'wall'          => false,
			'uri'           => $uri,
			'guid'          => $post->cid,
			'received'      => DateTimeFormat::utc($post->indexedAt, DateTimeFormat::MYSQL),
			'private'       => Item::UNLISTED,
			'verb'          => Activity::POST,
			'contact-id'    => $contact['id'],
			'author-name'   => $contact['name'],
			'author-link'   => $contact['url'],
			'author-avatar' => $contact['avatar'],
			'owner-name'    => $contact['name'],
			'owner-link'    => $contact['url'],
			'owner-avatar'  => $contact['avatar'],
			'plink'         => $contact['alias'] . '/post/' . $parts->rkey,
			'source'        => json_encode($post),
		];

		if ($this->postExists($item['uri'], [$uid])) {
			$this->logger->info('Post already exists for user', ['uri' => $item['uri'], 'uid' => $uid]);
			return [];
		}

		$account = Contact::selectFirstAccountUser(['pid'], ['id' => $contact['id']]);

		$item['owner-id'] = $item['author-id'] = $account['pid'];
		$item['uri-id']   = ItemURI::getIdByURI($uri);

		if (in_array($contact['rel'], [Contact::SHARING, Contact::FRIEND])) {
			$item['post-reason'] = Item::PR_FOLLOWER;
		}

		if (!empty($post->labels)) {
			foreach ($post->labels as $label) {
				// Only flag posts as sensitive based on labels that had been provided by the author.
				// When "ver" is set to "1" it was flagged by some automated process.
				if (empty($label->ver)) {
					$item['sensitive']       = true;
					$item['content-warning'] = $label->val ?? '';
					$this->logger->debug('Sensitive content', ['uri-id' => $item['uri-id'], 'label' => $label]);
				}
			}
		}

		return $item;
	}

	private function getContent(array $item, stdClass $record, string $uri, int $uid, int $level): array
	{
		if (empty($item)) {
			return [];
		}

		if (!empty($record->reply)) {
			$item['parent-uri'] = $this->getUri($record->reply->root);
			if ($item['parent-uri'] != $uri) {
				$item['parent-uri'] = $this->getPostUri($item['parent-uri'], $uid);
				if (empty($item['parent-uri'])) {
					$this->logger->notice('Parent-uri not found', ['uri' => $this->getUri($record->reply->root)]);
					return [];
				}
			}

			$item['thr-parent'] = $this->getUri($record->reply->parent);
			if (!in_array($item['thr-parent'], [$uri, $item['parent-uri']])) {
				$item['thr-parent'] = $this->getPostUri($item['thr-parent'], $uid) ?: $item['thr-parent'];
			}
		}

		$item['body']                  = $this->parseFacets($record, $item['uri-id']);
		$item['created']               = DateTimeFormat::utc($record->createdAt, DateTimeFormat::MYSQL);
		$item['transmitted-languages'] = $record->langs ?? [];

		return $item;
	}

	private function parseFacets(stdClass $record, int $uri_id): string
	{
		$text = $record->text ?? '';

		if (empty($record->facets)) {
			return $text;
		}

		$facets = [];
		foreach ($record->facets as $facet) {
			$facets[$facet->index->byteStart] = $facet;
		}
		krsort($facets);

		foreach ($facets as $facet) {
			$prefix   = substr($text, 0, $facet->index->byteStart);
			$linktext = substr($text, $facet->index->byteStart, $facet->index->byteEnd - $facet->index->byteStart);
			$suffix   = substr($text, $facet->index->byteEnd);

			$url  = '';
			$type = '$type';
			foreach ($facet->features as $feature) {

				switch ($feature->$type) {
					case 'app.bsky.richtext.facet#link':
						$url = $feature->uri;
						break;

					case 'app.bsky.richtext.facet#mention':
						$contact = Contact::getByURL($feature->did, null, ['id']);
						if (!empty($contact['id'])) {
							$url = $this->baseURL . '/contact/' . $contact['id'];
							if (substr($linktext, 0, 1) == '@') {
								$prefix .= '@';
								$linktext = substr($linktext, 1);
							}
						}
						break;

					case 'app.bsky.richtext.facet#tag':
						Tag::store($uri_id, Tag::HASHTAG, $feature->tag);
						$url      = $this->baseURL . '/search?tag=' . urlencode($feature->tag);
						$linktext = '#' . $feature->tag;
						break;

					default:
						$this->logger->notice('Unhandled feature type', ['type' => $feature->$type, 'feature' => $feature, 'record' => $record]);
						break;
				}
			}
			if (!empty($url)) {
				$text = $prefix . '[url=' . $url . ']' . $linktext . '[/url]' . $suffix;
			}
		}
		return $text;
	}

	private function addMedia(stdClass $embed, array $item, int $level): array
	{
		$type = '$type';
		switch ($embed->$type) {
			case 'app.bsky.embed.images#view':
				foreach ($embed->images as $image) {
					$media = [
						'uri-id'      => $item['uri-id'],
						'type'        => Post\Media::IMAGE,
						'url'         => $image->fullsize,
						'preview'     => $image->thumb,
						'description' => $image->alt,
						'height'      => $image->aspectRatio->height ?? null,
						'width'       => $image->aspectRatio->width  ?? null,
					];
					Post\Media::insert($media);
				}
				break;

			case 'app.bsky.embed.video#view':
				$media = [
					'uri-id'      => $item['uri-id'],
					'type'        => Post\Media::HLS,
					'url'         => $embed->playlist,
					'preview'     => $embed->thumbnail,
					'description' => $embed->alt                 ?? '',
					'height'      => $embed->aspectRatio->height ?? null,
					'width'       => $embed->aspectRatio->width  ?? null,
				];
				Post\Media::insert($media);
				break;

			case 'app.bsky.embed.external#view':
				$media = [
					'uri-id'      => $item['uri-id'],
					'type'        => Post\Media::HTML,
					'url'         => $embed->external->uri,
					'preview'     => $embed->external->thumb ?? null,
					'name'        => $embed->external->title,
					'description' => $embed->external->description,
				];
				Post\Media::insert($media);
				break;

			case 'app.bsky.embed.record#view':
				$original_uri = $uri = $this->getUri($embed->record);
				$type         = '$type';
				if (!empty($embed->record->record->$type)) {
					$embed_type = $embed->record->record->$type;
					if ($embed_type == 'app.bsky.graph.starterpack') {
						$this->addStarterpack($item, $embed->record);
						break;
					}
				}
				$fetched_uri = $this->getPostUri($uri, $item['uid']);
				if (!$fetched_uri) {
					$uri = $this->fetchMissingPost($uri, 0, Item::PR_FETCHED, $item['contact-id'], $level, $uri);
				} else {
					$uri = $fetched_uri;
				}
				if ($uri) {
					$shared = Post::selectFirst(['uri-id'], ['uri' => $uri, 'uid' => [$item['uid'], 0]]);
					$uri_id = $shared['uri-id'] ?? 0;
				}
				if (!empty($uri_id)) {
					$item['quote-uri-id'] = $uri_id;
				} else {
					$this->logger->debug('Quoted post could not be fetched', ['original-uri' => $original_uri, 'uri' => $uri]);
				}
				break;

			case 'app.bsky.embed.recordWithMedia#view':
				$this->addMedia($embed->media, $item, $level);
				$original_uri = $uri = $this->getUri($embed->record->record);
				$uri          = $this->fetchMissingPost($uri, 0, Item::PR_FETCHED, $item['contact-id'], $level, $uri);
				if ($uri) {
					$shared = Post::selectFirst(['uri-id'], ['uri' => $uri, 'uid' => [$item['uid'], 0]]);
					$uri_id = $shared['uri-id'] ?? 0;
				}
				if (!empty($uri_id)) {
					$item['quote-uri-id'] = $uri_id;
				} else {
					$this->logger->debug('Quoted post could not be fetched', ['original-uri' => $original_uri, 'uri' => $uri]);
				}
				break;

			default:
				$this->logger->notice('Unhandled embed type', ['uri-id' => $item['uri-id'], 'type' => $embed->$type, 'embed' => $embed]);
				break;
		}
		return $item;
	}

	private function addStarterpack(array $item, stdClass $record)
	{
		$this->logger->debug('Received starterpack', ['uri-id' => $item['uri-id'], 'guid' => $item['guid'], 'uri' => $record->uri]);
		if (!preg_match('#^at://(.+)/app.bsky.graph.starterpack/(.+)#', $record->uri, $matches)) {
			return;
		}

		$media = [
			'uri-id'      => $item['uri-id'],
			'type'        => Post\Media::HTML,
			'url'         => 'https://bsky.app/starter-pack/' . $matches[1] . '/' . $matches[2],
			'name'        => $record->record->name,
			'description' => $record->record->description ?? '',
		];

		Post\Media::insert($media);

		$fields = [
			'name'        => $record->record->name,
			'description' => $record->record->description ?? '',
		];
		Post\Media::update($fields, ['uri-id' => $media['uri-id'], 'url' => $media['url']]);
	}

	private function getRestrictionsForUser(stdClass $post, array $item, int $post_reason): ?int
	{
		if (!empty($post->viewer->replyDisabled)) {
			return Item::CANT_REPLY;
		}

		if (empty($post->threadgate)) {
			return null;
		}

		if (!isset($post->threadgate->record->allow)) {
			return null;
		}

		if ($item['uid'] == 0) {
			return Item::CANT_REPLY;
		}

		$restrict = true;
		$type     = '$type';

		foreach ($post->threadgate->record->allow as $allow) {
			switch ($allow->$type) {
				case 'app.bsky.feed.threadgate#followingRule':
					// Only followers can reply.
					if (Contact::isFollower($item['author-id'], $item['uid'])) {
						$restrict = false;
					}
					break;

				case 'app.bsky.feed.threadgate#mentionRule':
					// Only mentioned accounts can reply.
					if ($post_reason == Item::PR_TO) {
						$restrict = false;
					}
					break;

				case 'app.bsky.feed.threadgate#listRule':
					// Only accounts in the provided list can reply. We don't support this at the moment.
					break;
			}
		}

		return $restrict ? Item::CANT_REPLY : null;
	}

	public function fetchMissingPost(string $uri, int $uid, int $post_reason, int $causer, int $level, string $fallback = '', bool $always_fetch = false, int $Protocol = Conversation::PARCEL_JETSTREAM): string
	{
		$timestamp = microtime(true);
		$stamp     = Strings::getRandomHex(30);
		$this->logger->debug('Fetch missing post', ['uri' => $uri, 'stamp' => $stamp]);

		$fetched_uri = $this->getPostUri($uri, $uid);
		if (!$always_fetch && !empty($fetched_uri)) {
			return $fetched_uri;
		}

		if (++$level > 100) {
			$this->logger->info('Recursion level too deep', ['level' => $level, 'uid' => $uid, 'uri' => $uri, 'fallback' => $fallback]);
			// When the level is too deep we will fallback to the parent uri.
			// Allthough the threading won't be correct, we at least had stored all posts and won't try again
			return $fallback;
		}

		$class = $this->getUriClass($uri);
		if (empty($class)) {
			return $fallback;
		}

		$fetch_uri = $class->uri;

		$this->logger->debug('Fetch missing post', ['level' => $level, 'uid' => $uid, 'uri' => $uri]);
		$data = $this->atprotocol->XRPCGet('app.bsky.feed.getPostThread', ['uri' => $fetch_uri]);
		if (empty($data) || empty($data->thread)) {
			$this->logger->info('Thread was not fetched', ['level' => $level, 'uid' => $uid, 'uri' => $uri, 'fallback' => $fallback]);
			if (microtime(true) - $timestamp > 2) {
				$this->logger->debug('Not fetched', ['duration' => round(microtime(true) - $timestamp, 3), 'uri' => $uri, 'stamp' => $stamp]);
			}
			return $fallback;
		}

		$this->logger->debug('Reply count', ['level' => $level, 'uid' => $uid, 'uri' => $uri]);

		if ($causer != 0) {
			$causer = Contact::getPublicContactId($causer, $uid);
		}

		if (!empty($data->thread->parent)) {
			$parents = $this->fetchParents($data->thread->parent, $uid);

			if (!empty($parents)) {
				if ($data->thread->post->record->reply->root->uri != $parents[0]->uri) {
					$parent_uri = $this->getUri($data->thread->post->record->reply->root);
					$this->fetchMissingPost($parent_uri, $uid, $post_reason, $causer, $level, $data->thread->post->record->reply->root->uri, false, $Protocol);
				}
			}

			foreach ($parents as $parent) {
				$uri_id = $this->processPost($parent, $uid, Item::PR_FETCHED, $causer, $level, $Protocol);
				$this->logger->debug('Parent created', ['uri-id' => $uri_id]);
			}
		}

		$uri = $this->processThread($data->thread, $uid, $post_reason, $causer, $level, $Protocol);
		if (microtime(true) - $timestamp > 2) {
			$this->logger->debug('Fetched and processed post', ['duration' => round(microtime(true) - $timestamp, 3), 'uri' => $uri, 'stamp' => $stamp]);
		}
		return $uri;
	}

	private function fetchParents(stdClass $parent, int $uid, array $parents = []): array
	{
		if (!empty($parent->parent)) {
			$parents = $this->fetchParents($parent->parent, $uid, $parents);
		}

		if (!empty($parent->post) && empty($this->getPostUri($this->getUri($parent->post), $uid))) {
			$parents[] = $parent->post;
		}

		return $parents;
	}

	private function processThread(stdClass $thread, int $uid, int $post_reason, int $causer, int $level, int $protocol): string
	{
		if (empty($thread->post)) {
			$this->logger->info('Invalid post', ['post' => $thread]);
			return '';
		}
		$uri = $this->getUri($thread->post);

		$fetched_uri = $this->getPostUri($uri, $uid);
		if (empty($fetched_uri)) {
			$uri_id = $this->processPost($thread->post, $uid, $post_reason, $causer, $level, $protocol);
			if ($uri_id) {
				$this->logger->debug('Post has been processed and stored', ['uri-id' => $uri_id, 'uri' => $uri]);
				return $uri;
			} else {
				$this->logger->info('Post has not not been stored', ['uri' => $uri]);
				return '';
			}
		} else {
			$this->logger->debug('Post exists', ['uri' => $uri]);
			$uri = $fetched_uri;
		}

		foreach ($thread->replies ?? [] as $reply) {
			$reply_uri = $this->processThread($reply, $uid, Item::PR_FETCHED, $causer, $level, $protocol);
			$this->logger->debug('Reply has been processed', ['uri' => $uri, 'reply' => $reply_uri]);
		}

		return $uri;
	}

	public function getUriParts(string $uri): ?stdClass
	{
		$class = $this->getUriClass($uri);
		if (empty($class)) {
			return null;
		}

		$parts = explode('/', substr($class->uri, 5));

		$class = new stdClass();

		$class->repo       = $parts[0];
		$class->collection = $parts[1];
		$class->rkey       = $parts[2];

		return $class;
	}

	public function getUriClass(string $uri): ?stdClass
	{
		if (empty($uri)) {
			return null;
		}

		$elements = explode(':', $uri);
		if ($elements[0] !== 'at') {
			$post = Post::selectFirstPost(['extid'], ['uri' => $uri]);
			return $this->getUriClass($post['extid'] ?? '');
		}

		$class = new stdClass();

		$class->cid = array_pop($elements);
		$class->uri = implode(':', $elements);

		if ((substr_count($class->uri, '/') == 2) && (substr_count($class->cid, '/') == 2)) {
			$class->uri .= ':' . $class->cid;
			$class->cid = '';
		}

		return $class;
	}

	public function fetchUriId(string $uri, int $uid): string
	{
		$reply = Post::selectFirst(['uri-id'], ['uri' => $uri, 'uid' => [$uid, 0]]);
		if (!empty($reply['uri-id'])) {
			$this->logger->debug('Post exists', ['uri' => $uri]);
			return $reply['uri-id'];
		}
		$reply = Post::selectFirst(['uri-id'], ['extid' => $uri, 'uid' => [$uid, 0]]);
		if (!empty($reply['uri-id'])) {
			$this->logger->debug('Post with extid exists', ['uri' => $uri]);
			return $reply['uri-id'];
		}
		return 0;
	}

	private function getPostUids(string $uri, bool $with_public_user): array
	{
		$condition = $with_public_user ? [] : ["`uid` != ?", 0];

		$uids  = [];
		$posts = Post::select(['uid'], DBA::mergeConditions(['uri' => $uri], $condition));
		while ($post = Post::fetch($posts)) {
			$uids[] = $post['uid'];
		}
		$this->db->close($posts);

		$posts = Post::select(['uid'], DBA::mergeConditions(['extid' => $uri], $condition));
		while ($post = Post::fetch($posts)) {
			$uids[] = $post['uid'];
		}
		$this->db->close($posts);
		return array_unique($uids);
	}

	private function postExists(string $uri, array $uids): bool
	{
		if (Post::exists(['uri' => $uri, 'uid' => $uids])) {
			return true;
		}

		return Post::exists(['extid' => $uri, 'uid' => $uids]);
	}

	public function getUri(stdClass $post): string
	{
		if (empty($post->cid)) {
			$this->logger->info('Invalid URI', ['post' => $post]);
			return '';
		}
		return $post->uri . ':' . $post->cid;
	}

	public function getPostUri(string $uri, int $uid): string
	{
		if (Post::exists(['uri' => $uri, 'uid' => [$uid, 0]])) {
			$this->logger->debug('Post exists', ['uri' => $uri]);
			return $uri;
		}

		$reply = Post::selectFirst(['uri'], ['extid' => $uri, 'uid' => [$uid, 0]]);
		if (!empty($reply['uri'])) {
			$this->logger->debug('Post with extid exists', ['uri' => $uri]);
			return $reply['uri'];
		}
		return '';
	}
}
