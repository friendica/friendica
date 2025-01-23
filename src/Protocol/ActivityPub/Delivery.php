<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Protocol\ActivityPub;

use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\Delivery as ProtocolDelivery;
use Friendica\Util\HTTPSignature;

class Delivery
{
	/**
	 * Deliver posts to the given inbox
	 *
	 * @param string $inbox
	 * @return array with the elements "success" and "uri_ids" of the failed posts
	 */
	public static function deliver(string $inbox): array
	{
		$uri_ids    = [];
		$posts      = Post\Delivery::selectForInbox($inbox);
		$serverfail = false;

		foreach ($posts as $post) {
			$owner = User::getOwnerDataById($post['uid']);
			if (!$owner) {
				Post\Delivery::remove($post['uri-id'], $inbox);
				Post\Delivery::incrementFailed($post['uri-id'], $inbox);
				continue;
			}

			$result = [];

			if (!$serverfail) {
				$result = self::deliverToInbox($post['command'], 0, $inbox, $owner, $post['receivers'], $post['uri-id']);

				if ($result['serverfailure']) {
					// In a timeout situation we assume that every delivery to that inbox will time out.
					// So we set the flag and try all deliveries at a later time.
					DI::logger()->notice('Inbox delivery has a server failure', ['inbox' => $inbox]);
					$serverfail = true;
				}
				Worker::coolDown();
			}

			if ($serverfail || (!$result['success'] && !$result['drop'])) {
				$uri_ids[] = $post['uri-id'];
			}
		}

		DI::logger()->debug('Inbox delivery done', ['inbox' => $inbox, 'posts' => count($posts), 'failed' => count($uri_ids), 'serverfailure' => $serverfail]);
		return ['success' => empty($uri_ids), 'uri_ids' => $uri_ids];
	}

	/**
	 * Deliver the given post to the given inbox
	 *
	 * @param string $cmd
	 * @param integer $item_id
	 * @param string $inbox
	 * @param array $owner Sender owner-view record
	 * @param array $receivers
	 * @param integer $uri_id
	 * @return array
	 */
	public static function deliverToInbox(string $cmd, int $item_id, string $inbox, array $owner, array $receivers, int $uri_id): array
	{
		/** @var int $uid */
		$uid = $owner['uid'];

		if (empty($item_id) && !empty($uri_id) && !empty($uid)) {
			$item = Post::selectFirst(['id', 'parent', 'origin', 'gravity', 'verb'], ['uri-id' => $uri_id, 'uid' => [$uid, 0]], ['order' => ['uid' => true]]);
			if (empty($item['id'])) {
				DI::logger()->warning('Item not found, removing delivery', ['uri-id' => $uri_id, 'uid' => $uid, 'cmd' => $cmd, 'inbox' => $inbox]);
				Post\Delivery::remove($uri_id, $inbox);
				return ['success' => true, 'serverfailure' => false, 'drop' => false];
			} elseif (!DI::config()->get('system', 'redistribute_activities') && !$item['origin'] && ($item['gravity'] == Item::GRAVITY_ACTIVITY)) {
				DI::logger()->notice('Activities are not relayed, removing delivery', ['verb' => $item['verb'], 'uri-id' => $uri_id, 'uid' => $uid, 'cmd' => $cmd, 'inbox' => $inbox]);
				Post\Delivery::remove($uri_id, $inbox);
				return ['success' => true, 'serverfailure' => false, 'drop' => false];
			} else {
				$item_id = $item['id'];
			}
		}

		$success    = true;
		$serverfail = false;
		$drop       = false;

		if ($cmd == ProtocolDelivery::MAIL) {
			$data = ActivityPub\Transmitter::createActivityFromMail($item_id);
			if (!empty($data)) {
				$success = HTTPSignature::transmit($data, $inbox, $owner);
			}
		} elseif ($cmd == ProtocolDelivery::SUGGESTION) {
			$success = ActivityPub\Transmitter::sendContactSuggestion($owner, $inbox, $item_id);
		} elseif ($cmd == ProtocolDelivery::RELOCATION) {
			// @todo Implementation pending
		} elseif ($cmd == ProtocolDelivery::REMOVAL) {
			$success = ActivityPub\Transmitter::sendProfileDeletion($owner, $inbox);
		} elseif ($cmd == ProtocolDelivery::PROFILEUPDATE) {
			$success = ActivityPub\Transmitter::sendProfileUpdate($owner, $inbox);
		} else {
			$data = ActivityPub\Transmitter::createCachedActivityFromItem($item_id);
			if (!empty($data)) {
				$timestamp = microtime(true);
				try {
					$response   = HTTPSignature::post($data, $inbox, $owner);
					$success    = $response->isSuccess();
					$serverfail = $response->isTimeout();
				} catch (\Throwable $th) {
					DI::logger()->notice('Got exception', ['code' => $th->getCode(), 'message' => $th->getMessage()]);
					$response   = null;
					$success    = false;
					$serverfail = true;
				}
				$runtime = microtime(true) - $timestamp;
				if ($success === false) {
					// 5xx errors are problems on the server. We don't need to continue delivery then.
					if (!$serverfail && ($response->getReturnCode() >= 500) && ($response->getReturnCode() <= 599)) {
						$serverfail = true;
					}

					// A 404 means that the inbox doesn't exist. We can stop the delivery here.
					if (!$serverfail && ($response->getReturnCode() == 404)) {
						$serverfail = true;
					}

					$xrd_timeout = DI::config()->get('system', 'xrd_timeout');
					if (!$serverfail && $xrd_timeout && ($runtime > $xrd_timeout)) {
						$serverfail = true;
					}

					$curl_timeout = DI::config()->get('system', 'curl_timeout');
					if (!$serverfail && $curl_timeout && ($runtime > $curl_timeout)) {
						$serverfail = true;
					}

					// Resubscribe to relay server upon client error
					if (!$serverfail && ($response->getReturnCode() >= 400) && ($response->getReturnCode() <= 499)) {
						$actor = self::fetchActorForRelayInbox($inbox);
						if (!empty($actor)) {
							$drop = !ActivityPub\Transmitter::sendRelayFollow($actor);
							DI::logger()->notice('Resubscribed to relay', ['url' => $actor, 'success' => !$drop]);
						} elseif ($cmd == ProtocolDelivery::DELETION) {
							// Remote systems not always accept our deletion requests, so we drop them if rejected.
							// Situation is: In Friendica we allow the thread owner to delete foreign comments to their thread.
							// Most AP systems don't allow this, so they will reject the deletion request.
							$drop = true;
						}

					}

					DI::logger()->notice('Delivery failed', ['retcode' => $response->getReturnCode() ?? 0, 'serverfailure' => $serverfail, 'drop' => $drop, 'runtime' => round($runtime, 3), 'uri-id' => $uri_id, 'uid' => $uid, 'item_id' => $item_id, 'cmd' => $cmd, 'inbox' => $inbox]);
				}
				if ($uri_id) {
					if ($success) {
						Post\Delivery::remove($uri_id, $inbox);
					} else {
						Post\Delivery::incrementFailed($uri_id, $inbox);
					}
				}
			} elseif ($uri_id) {
				Post\Delivery::remove($uri_id, $inbox);
			}
		}

		self::setSuccess($receivers, $success);

		DI::logger()->debug('Delivered', ['uri-id' => $uri_id, 'uid' => $uid, 'item_id' => $item_id, 'cmd' => $cmd, 'inbox' => $inbox, 'success' => $success, 'serverfailure' => $serverfail, 'drop' => $drop]);

		if (($success || $drop) && in_array($cmd, [ProtocolDelivery::POST])) {
			Post\DeliveryData::incrementQueueDone($uri_id, Post\DeliveryData::ACTIVITYPUB);
		}

		return ['success' => $success, 'serverfailure' => $serverfail, 'drop' => $drop];
	}

	/**
	 * Fetch the actor of the given inbox of an relay server
	 *
	 * @param string $inbox
	 * @return string
	 */
	private static function fetchActorForRelayInbox(string $inbox): string
	{
		$apcontact = DBA::selectFirst('apcontact', ['url'], ["`sharedinbox` = ? AND `type` = ? AND `url` IN (SELECT `url` FROM `contact` WHERE `uid` = ? AND `rel` = ?)",
			$inbox, 'Application', 0, Contact::FRIEND]);
		return $apcontact['url'] ?? '';
	}

	/**
	 * mark or unmark the given receivers for archival upon success
	 *
	 * @param array $receivers
	 * @param boolean $success
	 * @return void
	 */
	private static function setSuccess(array $receivers, bool $success)
	{
		$gsid           = null;
		$update_counter = 0;

		foreach ($receivers as $receiver) {
			// Only update the first 10 receivers to avoid flooding the remote system with requests
			if ($success && ($update_counter < 10) && Contact::updateByIdIfNeeded($receiver)) {
				$update_counter++;
			}

			$contact = Contact::getById($receiver);
			if (empty($contact)) {
				continue;
			}

			$gsid = $gsid ?: $contact['gsid'];

			if ($success) {
				Contact::unmarkForArchival($contact);
			} else {
				Contact::markForArchival($contact);
			}
		}

		if (!empty($gsid)) {
			GServer::setProtocol($gsid, Post\DeliveryData::ACTIVITYPUB);
		}
	}
}
