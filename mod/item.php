<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * This is the POST destination for most all locally posted
 * text stuff. This function handles status, wall-to-wall status,
 * local comments, and remote comments that are posted on this site
 * (as opposed to being delivered in a feed).
 * Also processed here are posts and comments coming through the
 * statusnet/twitter API.
 *
 * All of these become an "item" which is our basic unit of
 * information.
 */

use Friendica\Content\Conversation;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Hook;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\ItemURI;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;
use Friendica\Util\DateTimeFormat;

function item_post()
{
	$uid = DI::userSession()->getLocalUserId();

	if (!$uid) {
		throw new HTTPException\ForbiddenException();
	}

	if (!empty($_REQUEST['dropitems'])) {
		item_drop($uid, $_REQUEST['dropitems']);
	}

	Hook::callAll('post_local_start', $_REQUEST);

	$return_path = $_REQUEST['return'] ?? '';
	$preview     = intval($_REQUEST['preview'] ?? 0);

	/*
	 * Check for doubly-submitted posts, and reject duplicates
	 * Note that we have to ignore previews, otherwise nothing will post
	 * after it's been previewed
	 */
	if (!$preview && !empty($_REQUEST['post_id_random'])) {
		if (DI::session()->get('post-random') == $_REQUEST['post_id_random']) {
			DI::logger()->warning('duplicate post');
			item_post_return(DI::baseUrl(), $return_path);
		} else {
			DI::session()->set('post-random', $_REQUEST['post_id_random']);
		}
	}

	if (empty($_REQUEST['post_id'])) {
		item_insert($uid, $_REQUEST, $preview, $return_path);
	} else {
		item_edit($uid, $_REQUEST, $preview, $return_path);
	}
}

function item_drop(int $uid, string $dropitems)
{
	$arr_drop = explode(',', $dropitems);
	foreach ($arr_drop as $item) {
		Item::deleteForUser(['id' => $item], $uid);
	}

	System::jsonExit(['success' => 1]);
}

function item_edit(int $uid, array $request, bool $preview, string $return_path)
{
	$post = Post::selectFirst(Item::ITEM_FIELDLIST, ['id' => $request['post_id'], 'uid' => $uid]);
	if (!DBA::isResult($post)) {
		if ($return_path) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Unable to locate original post.'));
			DI::baseUrl()->redirect($return_path);
		}
		throw new HTTPException\NotFoundException(DI::l10n()->t('Unable to locate original post.'));
	}

	$post['edit'] = $post;
	$post['file'] = Post\Category::getTextByURIId($post['uri-id'], $post['uid']);

	Post\Media::deleteByURIId($post['uri-id'], [Post\Media::AUDIO, Post\Media::VIDEO, Post\Media::IMAGE, Post\Media::HTML]);
	$post = item_process($post, $request, $preview, $return_path);

	$fields = [
		'title'    => $post['title'],
		'body'     => $post['body'],
		'attach'   => $post['attach'],
		'file'     => $post['file'],
		'location' => $post['location'],
		'coord'    => $post['coord'],
		'edited'   => DateTimeFormat::utcNow(),
		'changed'  => DateTimeFormat::utcNow()
	];

	$fields['body'] = Item::setHashtags($fields['body']);

	$quote_uri_id = Item::getQuoteUriId($fields['body'], $post['uid']);
	if (!empty($quote_uri_id)) {
		$fields['quote-uri-id'] = $quote_uri_id;
		$fields['body']         = BBCode::removeSharedData($post['body']);
	}

	Item::update($fields, ['id' => $post['id']]);
	Item::updateDisplayCache($post['uri-id']);

	if ($return_path) {
		DI::baseUrl()->redirect($return_path);
	}

	throw new HTTPException\OKException(DI::l10n()->t('Post updated.'));
}

function item_insert(int $uid, array $request, bool $preview, string $return_path)
{
	$post = ['uid' => $uid];
	$post = DI::contentItem()->initializePost($post);

	$post['edit']      = null;
	$post['post-type'] = $request['post_type']      ?? '';
	$post['wall']      = $request['wall']           ?? true;
	$post['pubmail']   = $request['pubmail_enable'] ?? false;
	$post['created']   = $request['created_at']     ?? DateTimeFormat::utcNow();
	$post['edited']    = $post['changed'] = $post['commented'] = $post['created'];
	$post['app']       = '';
	$post['inform']    = '';
	$post['postopts']  = '';
	$post['file']      = '';

	if (!empty($request['parent'])) {
		$parent_item = Post::selectFirst(Item::ITEM_FIELDLIST, ['id' => $request['parent']]);
		if ($parent_item) {
			// if this isn't the top-level parent of the conversation, find it
			if ($parent_item['gravity'] != Item::GRAVITY_PARENT) {
				$toplevel_item = Post::selectFirst(Item::ITEM_FIELDLIST, ['id' => $parent_item['parent']]);
			} else {
				$toplevel_item = $parent_item;
			}
		}

		if (empty($toplevel_item)) {
			if ($return_path) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Unable to locate original post.'));
				DI::baseUrl()->redirect($return_path);
			}
			throw new HTTPException\NotFoundException(DI::l10n()->t('Unable to locate original post.'));
		}

		// When commenting on a public post then store the post for the current user
		// This enables interaction like starring and saving into folders
		if ($toplevel_item['uid'] == 0) {
			$stored = Item::storeForUserByUriId($toplevel_item['uri-id'], $post['uid'], ['post-reason' => Item::PR_ACTIVITY]);
			DI::logger()->info('Public item stored for user', ['uri-id' => $toplevel_item['uri-id'], 'uid' => $post['uid'], 'stored' => $stored]);
		}

		$post['parent']     = $toplevel_item['id'];
		$post['gravity']    = Item::GRAVITY_COMMENT;
		$post['thr-parent'] = $parent_item['uri'];
		$post['wall']       = $toplevel_item['wall'];
	} else {
		$parent_item        = [];
		$post['parent']     = 0;
		$post['gravity']    = Item::GRAVITY_PARENT;
		$post['thr-parent'] = $post['uri'];
	}

	$post = DI::contentItem()->getACL($post, $parent_item, $request);

	$post['pubmail'] = $post['pubmail'] && !$post['private'];

	$post = item_process($post, $request, $preview, $return_path);

	$post_id = Item::insert($post);
	if (!$post_id) {
		if ($return_path) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Item wasn\'t stored.'));
			DI::baseUrl()->redirect($return_path);
		}

		throw new HTTPException\InternalServerErrorException(DI::l10n()->t('Item wasn\'t stored.'));
	}

	$post = Post::selectFirst(Item::ITEM_FIELDLIST, ['id' => $post_id]);
	if (!$post) {
		DI::logger()->error('Item couldn\'t be fetched.', ['post_id' => $post_id]);
		if ($return_path) {
			DI::baseUrl()->redirect($return_path);
		}

		throw new HTTPException\InternalServerErrorException(DI::l10n()->t('Item couldn\'t be fetched.'));
	}

	$recipients = explode(',', $request['emailcc'] ?? '');

	DI::contentItem()->postProcessPost($post, $recipients);

	if (($post['private'] == Item::PRIVATE) && ($post['thr-parent-id'] != $post['uri-id'])) {
		DI::contentItem()->copyPermissions($post['thr-parent-id'], $post['uri-id'], $post['parent-uri-id']);
	}

	DI::logger()->debug('post_complete');

	item_post_return(DI::baseUrl(), $return_path);
	// NOTREACHED
}

function item_process(array $post, array $request, bool $preview, string $return_path): array
{
	$post['self']            = true;
	$post['api_source']      = false;
	$post['attach']          = '';
	$post['title']           = trim($request['title'] ?? '');
	$post['content-warning'] = trim($request['summary'] ?? '');
	$post['sensitive']       = !empty($request['sensitive'] ?? false);
	$post['body']            = $request['body'] ?? '';
	$post['location']        = trim($request['location'] ?? '');
	$post['coord']           = trim($request['coord'] ?? '');

	$post = DI::contentItem()->addCategories($post, $request['category'] ?? '');

	// Add the attachment to the body.
	if (!empty($request['has_attachment'])) {
		$post['body'] .= DI::contentItem()->storeAttachmentFromRequest($request);
	}

	$post = DI::contentItem()->finalizePost($post, $preview);

	if (!strlen($post['body'])) {
		if ($preview) {
			System::jsonExit(['preview' => '']);
		}

		if ($return_path) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Empty post discarded.'));
			DI::baseUrl()->redirect($return_path);
		}

		throw new HTTPException\BadRequestException(DI::l10n()->t('Empty post discarded.'));
	}

	// preview mode - prepare the body for display and send it via json
	if ($preview) {
		// We have to preset some fields, so that the conversation can be displayed
		$post['id']             = -1;
		$post['uri-id']         = -1;
		$post['author-network'] = Protocol::DFRN;
		$post['author-updated'] = '';
		$post['author-alias']   = '';
		$post['author-gsid']    = 0;
		$post['author-uri-id']  = ItemURI::getIdByURI($post['author-link']);
		$post['owner-updated']  = '';
		$post['has-media']      = false;
		$post['quote-uri-id']   = Item::getQuoteUriId($post['body'], $post['uid']);
		$post['body']           = BBCode::removeSharedData(Item::setHashtags($post['body']));
		$post['writable']       = true;
		$post['sensitive']      = false;
		$post['post-reason']    = Item::PR_LOCAL;

		$o = DI::conversation()->render([$post], Conversation::MODE_SEARCH, false, true);

		System::jsonExit(['preview' => $o]);
	}

	Hook::callAll('post_local', $post);

	unset($post['edit']);
	unset($post['self']);
	unset($post['api_source']);

	if (!empty($request['scheduled_at'])) {
		$scheduled_at = DateTimeFormat::convert($request['scheduled_at'], 'UTC', DI::appHelper()->getTimeZone());
		if ($scheduled_at > DateTimeFormat::utcNow()) {
			unset($post['created']);
			unset($post['edited']);
			unset($post['commented']);
			unset($post['received']);
			unset($post['changed']);

			Post\Delayed::add($post['uri'], $post, Worker::PRIORITY_HIGH, Post\Delayed::PREPARED_NO_HOOK, $scheduled_at);
			item_post_return(DI::baseUrl(), $return_path);
		}
	}

	if (!empty($post['cancel'])) {
		DI::logger()->info('mod_item: post cancelled by addon.');
		if ($return_path) {
			DI::baseUrl()->redirect($return_path);
		}

		$json = ['cancel' => 1];
		if (!empty($request['jsreload'])) {
			$json['reload'] = DI::baseUrl() . '/' . $request['jsreload'];
		}

		System::jsonExit($json);
	}

	return $post;
}

function item_post_return($baseurl, $return_path)
{
	if ($return_path) {
		DI::baseUrl()->redirect($return_path);
	}

	$json = ['success' => 1];
	if (!empty($_REQUEST['jsreload'])) {
		$json['reload'] = $baseurl . '/' . $_REQUEST['jsreload'];
	}

	DI::logger()->debug('post_json', ['json' => $json]);

	System::jsonExit($json);
}

function item_content()
{
	if (!DI::userSession()->isAuthenticated()) {
		throw new HTTPException\UnauthorizedException();
	}

	$args = DI::args();

	if (!$args->has(2)) {
		throw new HTTPException\BadRequestException();
	}

	$o = '';
	switch ($args->get(1)) {
		case 'drop':
			if (DI::mode()->isAjax()) {
				Item::deleteForUser(['id' => $args->get(2)], DI::userSession()->getLocalUserId());
				// ajax return: [<item id>, 0 (no perm) | <owner id>]
				System::jsonExit([intval($args->get(2)), DI::userSession()->getLocalUserId()]);
			} else {
				if (!empty($args->get(3))) {
					$o = drop_item($args->get(2), $args->get(3));
				} else {
					$o = drop_item($args->get(2));
				}
			}
			break;

		case 'block':
			$item = Post::selectFirstForUser(DI::userSession()->getLocalUserId(), ['guid', 'author-id', 'parent', 'gravity'], ['id' => $args->get(2)]);
			if (empty($item['author-id'])) {
				throw new HTTPException\NotFoundException('Item not found');
			}

			Contact\User::setBlocked($item['author-id'], DI::userSession()->getLocalUserId(), true);

			if (DI::mode()->isAjax()) {
				// ajax return: [<item id>, 0 (no perm) | <owner id>]
				System::jsonExit([intval($args->get(2)), DI::userSession()->getLocalUserId()]);
			} else {
				item_redirect_after_action($item, $args->get(3));
			}
			break;

		case 'ignore':
			$item = Post::selectFirstForUser(DI::userSession()->getLocalUserId(), ['guid', 'author-id', 'parent', 'gravity'], ['id' => $args->get(2)]);
			if (empty($item['author-id'])) {
				throw new HTTPException\NotFoundException('Item not found');
			}

			Contact\User::setIgnored($item['author-id'], DI::userSession()->getLocalUserId(), true);

			if (DI::mode()->isAjax()) {
				// ajax return: [<item id>, 0 (no perm) | <owner id>]
				System::jsonExit([intval($args->get(2)), DI::userSession()->getLocalUserId()]);
			} else {
				item_redirect_after_action($item, $args->get(3));
			}
			break;

		case 'collapse':
			$item = Post::selectFirstForUser(DI::userSession()->getLocalUserId(), ['guid', 'author-id', 'parent', 'gravity'], ['id' => $args->get(2)]);
			if (empty($item['author-id'])) {
				throw new HTTPException\NotFoundException('Item not found');
			}

			Contact\User::setCollapsed($item['author-id'], DI::userSession()->getLocalUserId(), true);

			if (DI::mode()->isAjax()) {
				// ajax return: [<item id>, 0 (no perm) | <owner id>]
				System::jsonExit([intval($args->get(2)), DI::userSession()->getLocalUserId()]);
			} else {
				item_redirect_after_action($item, $args->get(3));
			}
			break;
	}

	return $o;
}

/**
 * @param int    $id
 * @param string $return
 * @return string
 * @throws HTTPException\InternalServerErrorException
 */
function drop_item(int $id, string $return = ''): string
{
	// Locate item to be deleted
	$item = Post::selectFirstForUser(DI::userSession()->getLocalUserId(), ['id', 'uid', 'guid', 'contact-id', 'deleted', 'gravity', 'parent'], ['id' => $id]);

	if (!DBA::isResult($item)) {
		DI::sysmsg()->addNotice(DI::l10n()->t('Item not found.'));
		DI::baseUrl()->redirect('network');
		//NOTREACHED
	}

	if ($item['deleted']) {
		return '';
	}

	$contact_id = 0;

	// check if logged in user is either the author or owner of this item
	if (DI::userSession()->getRemoteContactID($item['uid']) == $item['contact-id']) {
		$contact_id = $item['contact-id'];
	}

	if ((DI::userSession()->getLocalUserId() == $item['uid']) || $contact_id) {
		// delete the item
		Item::deleteForUser(['id' => $item['id']], DI::userSession()->getLocalUserId());

		item_redirect_after_action($item, $return);
		//NOTREACHED
	} else {
		DI::logger()->warning('Permission denied.', ['local' => DI::userSession()->getLocalUserId(), 'uid' => $item['uid'], 'cid' => $contact_id]);
		DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
		DI::baseUrl()->redirect('display/' . $item['guid']);
		//NOTREACHED
	}

	return '';
}

function item_redirect_after_action(array $item, string $returnUrlHex)
{
	$return_url = hex2bin($returnUrlHex);

	// removes update_* from return_url to ignore Ajax refresh
	$return_url = str_replace('update_', '', $return_url);

	// Check if delete a comment
	if ($item['gravity'] == Item::GRAVITY_COMMENT) {
		if (!empty($item['parent'])) {
			$parentitem = Post::selectFirstForUser(DI::userSession()->getLocalUserId(), ['guid'], ['id' => $item['parent']]);
		}

		// Return to parent guid
		if (!empty($parentitem)) {
			DI::baseUrl()->redirect('display/' . $parentitem['guid']);
			//NOTREACHED
		} // In case something goes wrong
		else {
			DI::baseUrl()->redirect('network');
			//NOTREACHED
		}
	} else {
		// if unknown location or deleting top level post called from display
		if (empty($return_url) || strpos($return_url, 'display') !== false) {
			DI::baseUrl()->redirect('network');
			//NOTREACHED
		} else {
			DI::baseUrl()->redirect($return_url);
			//NOTREACHED
		}
	}
}
