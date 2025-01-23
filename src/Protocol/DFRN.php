<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Protocol;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Friendica\App;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\Event;
use Friendica\Model\GServer;
use Friendica\Model\Item;
use Friendica\Model\ItemURI;
use Friendica\Model\Mail;
use Friendica\Model\Notification;
use Friendica\Model\Photo;
use Friendica\Model\Post;
use Friendica\Model\Profile;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;
use Friendica\Network\HTTPException;
use Friendica\Network\Probe;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Images;
use Friendica\Util\Proxy;
use Friendica\Util\Strings;
use Friendica\Util\XML;
use GuzzleHttp\Psr7\Uri;

/**
 * This class contain functions to create and send DFRN XML files
 */
class DFRN
{
	const TOP_LEVEL = 0; // Top level posting
	const REPLY     = 1; // Regular reply that is stored locally
	const REPLY_RC  = 2; // Reply that will be relayed

	/**
	 * Generates an array of contact and user for DFRN imports
	 *
	 * This array contains not only the receiver but also the sender of the message.
	 *
	 * @param integer $cid Contact id
	 * @param integer $uid User id
	 *
	 * @return array importer
	 * @throws \Exception
	 */
	public static function getImporter(int $cid, int $uid = 0): array
	{
		$condition = ['id' => $cid, 'blocked' => false, 'pending' => false];
		$contact   = DBA::selectFirst('contact', [], $condition);
		if (!DBA::isResult($contact)) {
			return [];
		}

		$contact['cpubkey']    = $contact['pubkey'];
		$contact['cprvkey']    = $contact['prvkey'];
		$contact['senderName'] = $contact['name'];

		if ($uid != 0) {
			$condition = ['uid' => $uid, 'verified' => true, 'blocked' => false, 'account_removed' => false, 'account_expired' => false];
			$user      = DBA::selectFirst('user', [], $condition);
			if (!DBA::isResult($user)) {
				return [];
			}

			$user['importer_uid'] = $user['uid'];
			$user['uprvkey']      = $user['prvkey'];
		} else {
			$user = ['importer_uid' => 0, 'uprvkey' => '', 'timezone' => 'UTC',
				'nickname'             => '', 'page-flags' => 0, 'account-type' => 0, 'prvnets' => 0];
		}

		return array_merge($contact, $user);
	}

	/**
	 * Generates the atom entries for delivery.php
	 *
	 * This function is used whenever content is transmitted via DFRN.
	 *
	 * @param array $items Item elements
	 * @param array $owner Owner record
	 *
	 * @return string DFRN entries
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @todo  Find proper type-hints
	 */
	public static function entries(array $items, array $owner): string
	{
		$doc               = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$root = self::addHeader($doc, $owner, 'dfrn:owner', '', false);

		if (! count($items)) {
			return trim($doc->saveXML());
		}

		foreach ($items as $item) {
			// These values aren't sent when sending from the queue.
			/// @todo Check if we can set these values from the queue or if they are needed at all.
			$item['entry:comment-allow'] = ($item['entry:comment-allow'] ?? '') ?: true;
			$item['entry:cid']           = $item['entry:cid'] ?? 0;

			$entry = self::entry($doc, 'text', $item, $owner, $item['entry:comment-allow'], $item['entry:cid']);
			if (isset($entry)) {
				$root->appendChild($entry);
			}
		}

		return trim($doc->saveXML());
	}

	/**
	 * Generate an atom entry for a given uri id and user
	 *
	 * @param int     $uri_id       The uri id
	 * @param int     $uid          The user id
	 * @param boolean $conversation Show the conversation. If false show the single post.
	 *
	 * @return string DFRN feed entry
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function itemFeed(int $uri_id, int $uid, bool $conversation = false): string
	{
		if ($conversation) {
			$condition = ['parent-uri-id' => $uri_id];
		} else {
			$condition = ['uri-id' => $uri_id];
		}

		$condition['uid'] = $uid;

		$items = Post::selectToArray(Item::DELIVER_FIELDLIST, $condition);
		if (!DBA::isResult($items)) {
			return '';
		}

		$item = $items[0];

		if ($item['uid'] != 0) {
			$owner = User::getOwnerDataById($item['uid']);
			if (!$owner) {
				return '';
			}
		} else {
			$owner = ['uid' => 0, 'nick' => 'feed-item'];
		}

		$doc               = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;
		$type              = 'html';

		if ($conversation) {
			$root = $doc->createElementNS(ActivityNamespace::ATOM1, 'feed');
			$doc->appendChild($root);

			$root->setAttribute('xmlns:thr', ActivityNamespace::THREAD);
			$root->setAttribute('xmlns:at', ActivityNamespace::TOMB);
			$root->setAttribute('xmlns:media', ActivityNamespace::MEDIA);
			$root->setAttribute('xmlns:dfrn', ActivityNamespace::DFRN);
			$root->setAttribute('xmlns:activity', ActivityNamespace::ACTIVITY);
			$root->setAttribute('xmlns:georss', ActivityNamespace::GEORSS);
			$root->setAttribute('xmlns:poco', ActivityNamespace::POCO);
			$root->setAttribute('xmlns:ostatus', ActivityNamespace::OSTATUS);
			$root->setAttribute('xmlns:statusnet', ActivityNamespace::STATUSNET);

			foreach ($items as $item) {
				$entry = self::entry($doc, $type, $item, $owner, true, 0);
				if (isset($entry)) {
					$root->appendChild($entry);
				}
			}
		} else {
			self::entry($doc, $type, $item, $owner, true, 0, true);
		}

		$atom = trim($doc->saveXML());
		return $atom;
	}

	/**
	 * Create XML text for DFRN mails
	 *
	 * @param array $mail  Mail record
	 * @param array $owner Owner record
	 *
	 * @return string DFRN mail
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo  Find proper type-hints
	 */
	public static function mail(array $mail, array $owner): string
	{
		$doc               = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$root = self::addHeader($doc, $owner, 'dfrn:owner', '', false);

		$mailElement   = $doc->createElement('dfrn:mail');
		$senderElement = $doc->createElement('dfrn:sender');

		XML::addElement($doc, $senderElement, 'dfrn:name', $owner['name']);
		XML::addElement($doc, $senderElement, 'dfrn:uri', $owner['url']);
		XML::addElement($doc, $senderElement, 'dfrn:avatar', $owner['thumb']);

		$mailElement->appendChild($senderElement);

		XML::addElement($doc, $mailElement, 'dfrn:id', $mail['uri']);
		XML::addElement($doc, $mailElement, 'dfrn:in-reply-to', $mail['parent-uri']);
		XML::addElement($doc, $mailElement, 'dfrn:sentdate', DateTimeFormat::utc($mail['created'] . '+00:00', DateTimeFormat::ATOM));
		XML::addElement($doc, $mailElement, 'dfrn:subject', $mail['title']);
		XML::addElement($doc, $mailElement, 'dfrn:content', $mail['body']);

		$root->appendChild($mailElement);

		return trim($doc->saveXML());
	}

	/**
	 * Create XML text for DFRN friend suggestions
	 *
	 * @param array $item  suggestion elements
	 * @param array $owner Owner record
	 *
	 * @return string DFRN suggestions
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo  Find proper type-hints
	 */
	public static function fsuggest(array $item, array $owner): string
	{
		$doc               = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$root = self::addHeader($doc, $owner, 'dfrn:owner', '', false);

		$suggest = $doc->createElement('dfrn:suggest');

		XML::addElement($doc, $suggest, 'dfrn:url', $item['url']);
		XML::addElement($doc, $suggest, 'dfrn:name', $item['name']);
		XML::addElement($doc, $suggest, 'dfrn:photo', $item['photo']);
		XML::addElement($doc, $suggest, 'dfrn:request', $item['request']);
		XML::addElement($doc, $suggest, 'dfrn:note', $item['note']);

		$root->appendChild($suggest);

		return trim($doc->saveXML());
	}

	/**
	 * Create XML text for DFRN relocations
	 *
	 * @param array $owner Owner record
	 * @param int   $uid   User ID
	 *
	 * @return string DFRN relocations
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo  Find proper type-hints
	 */
	public static function relocate(array $owner, int $uid): string
	{

		/* get site pubkey. this could be a new installation with no site keys*/
		$pubkey = DI::config()->get('system', 'site_pubkey');
		if (! $pubkey) {
			$res = Crypto::newKeypair(1024);
			DI::config()->set('system', 'site_prvkey', $res['prvkey']);
			DI::config()->set('system', 'site_pubkey', $res['pubkey']);
		}

		$profilephotos = Photo::selectToArray(['resource-id', 'scale', 'type'], ['profile' => true, 'uid' => $uid], ['order' => ['scale']]);

		$photos = [];
		foreach ($profilephotos as $p) {
			$photos[$p['scale']] = DI::baseUrl() . '/photo/' . $p['resource-id'] . '-' . $p['scale'] . Images::getExtensionByMimeType($p['type']);
		}

		$doc               = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$root = self::addHeader($doc, $owner, 'dfrn:owner', '', false);

		$relocate = $doc->createElement('dfrn:relocate');

		XML::addElement($doc, $relocate, 'dfrn:url', $owner['url']);
		XML::addElement($doc, $relocate, 'dfrn:name', $owner['name']);
		XML::addElement($doc, $relocate, 'dfrn:addr', $owner['addr']);
		XML::addElement($doc, $relocate, 'dfrn:avatar', $owner['avatar']);
		XML::addElement($doc, $relocate, 'dfrn:photo', $photos[4]);
		XML::addElement($doc, $relocate, 'dfrn:thumb', $photos[5]);
		XML::addElement($doc, $relocate, 'dfrn:micro', $photos[6]);
		XML::addElement($doc, $relocate, 'dfrn:request', $owner['request']);
		XML::addElement($doc, $relocate, 'dfrn:confirm', $owner['confirm']);
		XML::addElement($doc, $relocate, 'dfrn:notify', $owner['notify']);
		XML::addElement($doc, $relocate, 'dfrn:poll', $owner['poll']);
		XML::addElement($doc, $relocate, 'dfrn:sitepubkey', DI::config()->get('system', 'site_pubkey'));

		$root->appendChild($relocate);

		return trim($doc->saveXML());
	}

	/**
	 * Adds the header elements for the DFRN protocol
	 *
	 * @param DOMDocument $doc           XML document
	 * @param array       $owner         Owner record
	 * @param string      $authorelement Element name for the author
	 * @param string      $alternatelink link to profile or category
	 * @param bool        $public        Is it a header for public posts?
	 * @return DOMElement XML root element
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo  Find proper type-hint for returned type
	 */
	private static function addHeader(DOMDocument $doc, array $owner, string $authorelement, string $alternatelink = '', bool $public = false): DOMElement
	{
		if ($alternatelink == '') {
			$alternatelink = $owner['url'];
		}

		$root = $doc->createElementNS(ActivityNamespace::ATOM1, 'feed');
		$doc->appendChild($root);

		$root->setAttribute('xmlns:thr', ActivityNamespace::THREAD);
		$root->setAttribute('xmlns:at', ActivityNamespace::TOMB);
		$root->setAttribute('xmlns:media', ActivityNamespace::MEDIA);
		$root->setAttribute('xmlns:dfrn', ActivityNamespace::DFRN);
		$root->setAttribute('xmlns:activity', ActivityNamespace::ACTIVITY);
		$root->setAttribute('xmlns:georss', ActivityNamespace::GEORSS);
		$root->setAttribute('xmlns:poco', ActivityNamespace::POCO);
		$root->setAttribute('xmlns:ostatus', ActivityNamespace::OSTATUS);
		$root->setAttribute('xmlns:statusnet', ActivityNamespace::STATUSNET);

		XML::addElement($doc, $root, 'id', DI::baseUrl() . '/profile/' . $owner['nick']);
		XML::addElement($doc, $root, 'title', $owner['name']);

		$attributes = ['uri' => 'https://friendi.ca', 'version' => App::VERSION . '-' . DB_UPDATE_VERSION];
		XML::addElement($doc, $root, 'generator', App::PLATFORM, $attributes);

		$attributes = ['rel' => 'license', 'href' => 'http://creativecommons.org/licenses/by/3.0/'];
		XML::addElement($doc, $root, 'link', '', $attributes);

		$attributes = ['rel' => 'alternate', 'type' => 'text/html', 'href' => $alternatelink];
		XML::addElement($doc, $root, 'link', '', $attributes);

		// For backward compatibility we keep this element
		if (in_array($owner['page-flags'], [User::PAGE_FLAGS_COMMUNITY, User::PAGE_FLAGS_COMM_MAN])) {
			XML::addElement($doc, $root, 'dfrn:community', 1);
		}

		// The former element is replaced by this one
		XML::addElement($doc, $root, 'dfrn:account_type', $owner['account-type']);

		/// @todo We need a way to transmit the different page flags like "User::PAGE_FLAGS_PRVGROUP"

		XML::addElement($doc, $root, 'updated', DateTimeFormat::utcNow(DateTimeFormat::ATOM));

		$author = self::addAuthor($doc, $owner, $authorelement, $public);
		$root->appendChild($author);

		return $root;
	}

	/**
	 * Determine the next birthday, but only if the birthday is published
	 * in the default profile. We _could_ also look for a private profile that the
	 * recipient can see, but somebody could get mad at us if they start getting
	 * public birthday greetings when they haven't made this info public.
	 *
	 * Assuming we are able to publish this info, we are then going to convert
	 * the start time from the owner's timezone to UTC.
	 *
	 * This will potentially solve the problem found with some social networks
	 * where birthdays are converted to the viewer's timezone and salutations from
	 * elsewhere in the world show up on the wrong day. We will convert it to the
	 * viewer's timezone also, but first we are going to convert it from the birthday
	 * person's timezone to GMT - so the viewer may find the birthday starting at
	 * 6:00PM the day before, but that will correspond to midnight to the birthday person.
	 *
	 * @param int $uid User id
	 * @param string $tz Time zone string, like UTC
	 * @return string Formatted birthday string
	 */
	private static function determineNextBirthday(int $uid, string $tz): string
	{
		$birthday = '';

		if (!strlen($tz)) {
			$tz = 'UTC';
		}

		$profile = DBA::selectFirst('profile', ['dob'], ['uid' => $uid]);
		if (DBA::isResult($profile)) {
			$tmp_dob = substr($profile['dob'], 5);
			if (intval($tmp_dob)) {
				$y     = DateTimeFormat::timezoneNow($tz, 'Y');
				$bd    = $y . '-' . $tmp_dob . ' 00:00';
				$t_dob = strtotime($bd);
				$now   = strtotime(DateTimeFormat::timezoneNow($tz));
				if ($t_dob < $now) {
					$bd = (int) $y + 1 . '-' . $tmp_dob . ' 00:00';
				}
				$birthday = DateTimeFormat::convert($bd, 'UTC', $tz, DateTimeFormat::ATOM);
			}
		}

		return $birthday;
	}

	/**
	 * Adds the author element in the header for the DFRN protocol
	 *
	 * @param DOMDocument $doc           XML document
	 * @param array       $owner         Owner record
	 * @param string      $authorelement Element name for the author
	 * @param boolean     $public        boolean
	 * @return DOMElement XML author object
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo  Find proper type-hints
	 */
	private static function addAuthor(DOMDocument $doc, array $owner, string $authorelement, bool $public): DOMElement
	{
		// Should the profile be "unsearchable" in the net? Then add the "hide" element
		$hide = DBA::exists('profile', ['uid' => $owner['uid'], 'net-publish' => false]);

		$author = $doc->createElement($authorelement);

		$namdate = DateTimeFormat::utc($owner['name-date'].'+00:00', DateTimeFormat::ATOM);
		$picdate = DateTimeFormat::utc($owner['avatar-date'].'+00:00', DateTimeFormat::ATOM);

		$attributes = [];

		if (!$public || !$hide) {
			$attributes = ['dfrn:updated' => $namdate];
		}

		XML::addElement($doc, $author, 'name', $owner['name'], $attributes);
		XML::addElement($doc, $author, 'uri', DI::baseUrl().'/profile/' . $owner['nickname'], $attributes);
		XML::addElement($doc, $author, 'dfrn:handle', $owner['addr'], $attributes);

		$attributes = [
			'rel'          => 'photo',
			'type'         => 'image/jpeg',
			'media:width'  => Proxy::PIXEL_SMALL,
			'media:height' => Proxy::PIXEL_SMALL,
			'href'         => User::getAvatarUrl($owner, Proxy::SIZE_SMALL),
		];

		if (!$public || !$hide) {
			$attributes['dfrn:updated'] = $picdate;
		}

		XML::addElement($doc, $author, 'link', '', $attributes);

		$attributes['rel'] = 'avatar';
		XML::addElement($doc, $author, 'link', '', $attributes);

		if ($hide) {
			XML::addElement($doc, $author, 'dfrn:hide', 'true');
		}

		// The following fields will only be generated if the data isn't meant for a public feed
		if ($public) {
			return $author;
		}

		$birthday = self::determineNextBirthday($owner['uid'], $owner['timezone']);

		if ($birthday) {
			XML::addElement($doc, $author, 'dfrn:birthday', $birthday);
		}

		// Only show contact details when we are allowed to
		$profile = DBA::selectFirst(
			'owner-view',
			['about', 'name', 'homepage', 'nickname', 'timezone', 'locality', 'region', 'country-name', 'pub_keywords', 'xmpp', 'dob'],
			['uid' => $owner['uid'], 'hidewall' => false]
		);
		if (DBA::isResult($profile)) {
			XML::addElement($doc, $author, 'poco:displayName', $profile['name']);
			XML::addElement($doc, $author, 'poco:updated', $namdate);

			if (trim($profile['dob']) > DBA::NULL_DATE) {
				XML::addElement($doc, $author, 'poco:birthday', '0000-'.date('m-d', strtotime($profile['dob'])));
			}

			XML::addElement($doc, $author, 'poco:note', $profile['about']);
			XML::addElement($doc, $author, 'poco:preferredUsername', $profile['nickname']);

			XML::addElement($doc, $author, 'poco:utcOffset', DateTimeFormat::timezoneNow($profile['timezone'], 'P'));

			if (trim($profile['homepage'])) {
				$urls = $doc->createElement('poco:urls');
				XML::addElement($doc, $urls, 'poco:type', 'homepage');
				XML::addElement($doc, $urls, 'poco:value', $profile['homepage']);
				XML::addElement($doc, $urls, 'poco:primary', 'true');
				$author->appendChild($urls);
			}

			if (trim($profile['pub_keywords'] ?? '')) {
				$keywords = explode(',', $profile['pub_keywords']);

				foreach ($keywords as $keyword) {
					XML::addElement($doc, $author, 'poco:tags', trim($keyword));
				}
			}

			if (trim($profile['xmpp'])) {
				$ims = $doc->createElement('poco:ims');
				XML::addElement($doc, $ims, 'poco:type', 'xmpp');
				XML::addElement($doc, $ims, 'poco:value', $profile['xmpp']);
				XML::addElement($doc, $ims, 'poco:primary', 'true');
				$author->appendChild($ims);
			}

			if (trim($profile['locality'] . $profile['region'] . $profile['country-name'])) {
				$element = $doc->createElement('poco:address');

				XML::addElement($doc, $element, 'poco:formatted', Profile::formatLocation($profile));

				if (trim($profile['locality']) != '') {
					XML::addElement($doc, $element, 'poco:locality', $profile['locality']);
				}

				if (trim($profile['region']) != '') {
					XML::addElement($doc, $element, 'poco:region', $profile['region']);
				}

				if (trim($profile['country-name']) != '') {
					XML::addElement($doc, $element, 'poco:country', $profile['country-name']);
				}

				$author->appendChild($element);
			}
		}

		return $author;
	}

	/**
	 * Adds the author elements in the "entry" elements of the DFRN protocol
	 *
	 * @param DOMDocument $doc         XML document
	 * @param string      $element     Element name for the author
	 * @param string      $contact_url Link of the contact
	 * @param array       $item        Item elements
	 * @return DOMElement XML author object
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo  Find proper type-hints
	 */
	private static function addEntryAuthor(DOMDocument $doc, string $element, string $contact_url, array $item): DOMElement
	{
		$author = $doc->createElement($element);

		$contact = Contact::getByURLForUser($contact_url, $item['uid'], false, ['url', 'name', 'addr', 'photo']);
		if (!empty($contact)) {
			XML::addElement($doc, $author, 'name', $contact['name']);
			XML::addElement($doc, $author, 'uri', $contact['url']);
			XML::addElement($doc, $author, 'dfrn:handle', $contact['addr']);

			/// @Todo
			/// - Check real image type and image size
			/// - Check which of these elements we should use
			$attributes = [
				'rel'          => 'photo',
				'type'         => 'image/jpeg',
				'media:width'  => 80,
				'media:height' => 80,
				'href'         => $contact['photo'],
			];
			XML::addElement($doc, $author, 'link', '', $attributes);

			$attributes = [
				'rel'          => 'avatar',
				'type'         => 'image/jpeg',
				'media:width'  => 80,
				'media:height' => 80,
				'href'         => $contact['photo'],
			];
			XML::addElement($doc, $author, 'link', '', $attributes);
		}

		return $author;
	}

	/**
	 * Adds the activity elements
	 *
	 * @param DOMDocument $doc      XML document
	 * @param string      $element  Element name for the activity
	 * @param string      $activity activity value
	 * @param int         $uriid    Uri-Id of the post
	 * @return DOMElement XML activity object
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo  Find proper type-hints
	 */
	private static function createActivity(DOMDocument $doc, string $element, string $activity, int $uriid)
	{
		if ($activity) {
			$entry = $doc->createElement($element);

			$r = XML::parseString($activity);
			if (!$r) {
				return false;
			}

			if ($r->type) {
				XML::addElement($doc, $entry, "activity:object-type", $r->type);
			}

			if ($r->id) {
				XML::addElement($doc, $entry, "id", $r->id);
			}

			if ($r->title) {
				XML::addElement($doc, $entry, "title", $r->title);
			}

			if ($r->link) {
				if (substr($r->link, 0, 1) == '<') {
					if (strstr($r->link, '&') && (! strstr($r->link, '&amp;'))) {
						$r->link = str_replace('&', '&amp;', $r->link);
					}

					$r->link = preg_replace('/\<link(.*?)\"\>/', '<link$1"/>', $r->link);

					// XML does need a single element as root element so we add a dummy element here
					$data = XML::parseString("<dummy>" . $r->link . "</dummy>");
					if (is_object($data)) {
						foreach ($data->link as $link) {
							$attributes = [];
							foreach ($link->attributes() as $parameter => $value) {
								$attributes[$parameter] = $value;
							}
							XML::addElement($doc, $entry, "link", "", $attributes);
						}
					}
				} else {
					$attributes = ["rel" => "alternate", "type" => "text/html", "href" => $r->link];
					XML::addElement($doc, $entry, "link", "", $attributes);
				}
			}
			if ($r->content) {
				XML::addElement($doc, $entry, "content", BBCode::convertForUriId($uriid, $r->content, BBCode::EXTERNAL), ["type" => "html"]);
			}

			return $entry;
		}

		return false;
	}

	/**
	 * Adds the elements for attachments
	 *
	 * @param object $doc  XML document
	 * @param object $root XML root
	 * @param array  $item Item element
	 *
	 * @return void XML attachment object
	 * @todo  Find proper type-hints
	 */
	private static function getAttachment($doc, $root, array $item)
	{
		foreach (Post\Media::getByURIId($item['uri-id'], [Post\Media::DOCUMENT, Post\Media::TORRENT]) as $attachment) {
			$attributes = ['rel' => 'enclosure',
				'href'              => $attachment['url'],
				'type'              => $attachment['mimetype']];

			if (!empty($attachment['size'])) {
				$attributes['length'] = intval($attachment['size']);
			}
			if (!empty($attachment['description'])) {
				$attributes['title'] = $attachment['description'];
			}

			XML::addElement($doc, $root, 'link', '', $attributes);
		}
	}

	/**
	 * Adds the "entry" elements for the DFRN protocol
	 *
	 * @param DOMDocument $doc     XML document
	 * @param string      $type    "text" or "html"
	 * @param array       $item    Item element
	 * @param array       $owner   Owner record
	 * @param bool        $comment Trigger the sending of the "comment" element
	 * @param int         $cid     Contact ID of the recipient
	 * @param bool        $single  If set, the entry is created as an XML document with a single "entry" element
	 *
	 * @return null|\DOMElement XML entry object
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @todo  Find proper type-hints
	 */
	private static function entry(DOMDocument $doc, string $type, array $item, array $owner, bool $comment = false, int $cid = 0, bool $single = false)
	{
		$mentioned = [];

		if (!$item['parent']) {
			DI::logger()->warning('Item without parent found.', ['type' => $type, 'item' => $item]);
			return null;
		}

		if ($item['deleted']) {
			$attributes = ["ref" => $item['uri'], "when" => DateTimeFormat::utc($item['edited'] . '+00:00', DateTimeFormat::ATOM)];
			return XML::createElement($doc, "at:deleted-entry", "", $attributes);
		}

		if (!$single) {
			$entry = $doc->createElement("entry");
		} else {
			$entry = $doc->createElementNS(ActivityNamespace::ATOM1, 'entry');
			$doc->appendChild($entry);

			$entry->setAttribute("xmlns:thr", ActivityNamespace::THREAD);
			$entry->setAttribute("xmlns:at", ActivityNamespace::TOMB);
			$entry->setAttribute("xmlns:media", ActivityNamespace::MEDIA);
			$entry->setAttribute("xmlns:dfrn", ActivityNamespace::DFRN);
			$entry->setAttribute("xmlns:activity", ActivityNamespace::ACTIVITY);
			$entry->setAttribute("xmlns:georss", ActivityNamespace::GEORSS);
			$entry->setAttribute("xmlns:poco", ActivityNamespace::POCO);
			$entry->setAttribute("xmlns:ostatus", ActivityNamespace::OSTATUS);
			$entry->setAttribute("xmlns:statusnet", ActivityNamespace::STATUSNET);
		}

		$body = Post\Media::addAttachmentsToBody($item['uri-id'], DI::contentItem()->addSharedPost($item));
		$body = Post\Media::addHTMLAttachmentToBody($item['uri-id'], $body);

		if ($item['private'] == Item::PRIVATE) {
			$body = Item::fixPrivatePhotos($body, $owner['uid'], $item, $cid);
		}

		// Remove the abstract element. It is only locally important.
		$body = BBCode::stripAbstract($body);

		$htmlbody = '';
		if ($type == 'html') {
			$htmlbody = $body;

			if ($item['title'] != "") {
				$htmlbody = "[b]" . $item['title'] . "[/b]\n\n" . $htmlbody;
			}

			$htmlbody = BBCode::convertForUriId($item['uri-id'], $htmlbody, BBCode::ACTIVITYPUB);
		}

		$author = self::addEntryAuthor($doc, "author", $item["author-link"], $item);
		$entry->appendChild($author);

		$dfrnowner = self::addEntryAuthor($doc, "dfrn:owner", $item["owner-link"], $item);
		$entry->appendChild($dfrnowner);

		if ($item['gravity'] != Item::GRAVITY_PARENT) {
			$parent = Post::selectFirst(['guid', 'plink'], ['uri' => $item['thr-parent'], 'uid' => $item['uid']]);
			if (DBA::isResult($parent)) {
				$attributes = ["ref"  => $item['thr-parent'], "type" => "text/html",
					"href"               => $parent['plink'],
					"dfrn:diaspora_guid" => $parent['guid']];
				XML::addElement($doc, $entry, "thr:in-reply-to", "", $attributes);
			}
		}

		// Add conversation data. This is used for OStatus
		$attributes = [
			'href' => $item['conversation'],
			'ref'  => $item['conversation'],
		];

		XML::addElement($doc, $entry, 'ostatus:conversation', $item['conversation'], $attributes);

		XML::addElement($doc, $entry, 'id', $item['uri']);
		XML::addElement($doc, $entry, 'title', $item['title']);

		XML::addElement($doc, $entry, 'published', DateTimeFormat::utc($item['created'] . '+00:00', DateTimeFormat::ATOM));
		XML::addElement($doc, $entry, 'updated', DateTimeFormat::utc($item['edited'] . '+00:00', DateTimeFormat::ATOM));

		// "dfrn:env" is used to read the content
		XML::addElement($doc, $entry, 'dfrn:env', Strings::base64UrlEncode($body, true));

		// The "content" field is not read by the receiver. We could remove it when the type is "text"
		// We keep it at the moment, maybe there is some old version that doesn't read "dfrn:env"
		XML::addElement($doc, $entry, 'content', (($type == 'html') ? $htmlbody : $body), ['type' => $type]);

		// We save this value in "plink". Maybe we should read it from there as well?
		XML::addElement(
			$doc,
			$entry,
			'link',
			'',
			[
				'rel'  => 'alternate',
				'type' => 'text/html',
				'href' => DI::baseUrl() . '/display/' . $item['guid']
			],
		);

		// "comment-allow" is some old fashioned stuff for old Friendica versions.
		// It is included in the rewritten code for completeness
		if ($comment) {
			XML::addElement($doc, $entry, 'dfrn:comment-allow', 1);
		}

		if ($item['location']) {
			XML::addElement($doc, $entry, 'dfrn:location', $item['location']);
		}

		if ($item['coord']) {
			XML::addElement($doc, $entry, 'georss:point', $item['coord']);
		}

		if ($item['private']) {
			// Friendica versions prior to 2020.3 can't handle "unlisted" properly. So we can only transmit public and private
			XML::addElement($doc, $entry, 'dfrn:private', ($item['private'] == Item::PRIVATE ? Item::PRIVATE : Item::PUBLIC));
			XML::addElement($doc, $entry, 'dfrn:unlisted', $item['private'] == Item::UNLISTED);
		}

		if ($item['extid']) {
			XML::addElement($doc, $entry, 'dfrn:extid', $item['extid']);
		}

		if ($item['post-type'] == Item::PT_PAGE) {
			XML::addElement($doc, $entry, 'dfrn:bookmark', 'true');
		}

		if ($item['app']) {
			XML::addElement($doc, $entry, 'statusnet:notice_info', '', ['local_id' => $item['id'], 'source' => $item['app']]);
		}

		XML::addElement($doc, $entry, 'dfrn:diaspora_guid', $item['guid']);

		// The signed text contains the content in Markdown, the sender handle and the signature for the content
		// It is needed for relayed comments to Diaspora.
		if ($item['signed_text']) {
			$sign = base64_encode(json_encode(['signed_text' => $item['signed_text'],'signature' => '','signer' => '']));
			XML::addElement($doc, $entry, 'dfrn:diaspora_signature', $sign);
		}

		XML::addElement($doc, $entry, 'activity:verb', self::constructVerb($item));

		if ($item['object-type'] != '') {
			XML::addElement($doc, $entry, 'activity:object-type', $item['object-type']);
		} elseif ($item['gravity'] == Item::GRAVITY_PARENT) {
			XML::addElement($doc, $entry, 'activity:object-type', Activity\ObjectType::NOTE);
		} else {
			XML::addElement($doc, $entry, 'activity:object-type', Activity\ObjectType::COMMENT);
		}

		$actobj = self::createActivity($doc, 'activity:object', $item['object'] ?? '', $item['uri-id']);
		if ($actobj) {
			$entry->appendChild($actobj);
		}

		$actarg = self::createActivity($doc, 'activity:target', $item['target'] ?? '', $item['uri-id']);
		if ($actarg) {
			$entry->appendChild($actarg);
		}

		$tags = Tag::getByURIId($item['uri-id']);

		if (count($tags)) {
			foreach ($tags as $tag) {
				if (($type != 'html') || ($tag['type'] == Tag::HASHTAG)) {
					XML::addElement($doc, $entry, 'category', '', ['scheme' => 'X-DFRN:' . Tag::TAG_CHARACTER[$tag['type']] . ':' . $tag['url'], 'term' => $tag['name']]);
				}
				if ($tag['type'] != Tag::HASHTAG) {
					$mentioned[$tag['url']] = $tag['url'];
				}
			}
		}

		foreach ($mentioned as $mention) {
			$condition = ['uid' => $owner['uid'], 'nurl' => Strings::normaliseLink($mention)];
			$contact   = DBA::selectFirst('contact', ['contact-type'], $condition);

			if (DBA::isResult($contact) && ($contact['contact-type'] == Contact::TYPE_COMMUNITY)) {
				XML::addElement(
					$doc,
					$entry,
					'link',
					'',
					[
						'rel'                 => 'mentioned',
						'ostatus:object-type' => Activity\ObjectType::GROUP,
						'href'                => $mention,
					],
				);
			} else {
				XML::addElement(
					$doc,
					$entry,
					'link',
					'',
					[
						'rel'                 => 'mentioned',
						'ostatus:object-type' => Activity\ObjectType::PERSON,
						'href'                => $mention,
					],
				);
			}
		}

		self::getAttachment($doc, $entry, $item);

		return $entry;
	}

	/**
	 * Transmits atom content to the contacts via the Diaspora transport layer
	 *
	 * @param array  $owner   Owner record
	 * @param array  $contact Contact record of the receiver
	 * @param string $atom    Content that will be transmitted
	 * @param bool   $public_batch
	 * @return int Deliver status. Negative values mean an error.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function transmit(array $owner, array $contact, string $atom, bool $public_batch = false)
	{
		if (!$public_batch) {
			if (empty($contact['addr'])) {
				DI::logger()->notice('Empty contact handle for ' . $contact['id'] . ' - ' . $contact['url'] . ' - trying to update it.');
				if (Contact::updateFromProbe($contact['id'])) {
					$new_contact     = DBA::selectFirst('contact', ['addr'], ['id' => $contact['id']]);
					$contact['addr'] = $new_contact['addr'];
				}

				if (empty($contact['addr'])) {
					DI::logger()->notice('Unable to find contact handle for ' . $contact['id'] . ' - ' . $contact['url']);
					return -21;
				}
			}

			try {
				$pubkey = DI::dsprContact()->getByAddr(WebFingerUri::fromString($contact['addr']))->pubKey;
			} catch (HTTPException\NotFoundException|\InvalidArgumentException $e) {
				DI::logger()->notice('Unable to find contact details for ' . $contact['id'] . ' - ' . $contact['addr']);
				return -22;
			}
		} else {
			$pubkey = '';
		}

		$envelope = Diaspora::buildMessage($atom, $owner, $contact, $owner['uprvkey'], $pubkey, $public_batch);

		// Create the endpoint for public posts. This is some WIP and should later be added to the probing
		if ($public_batch && empty($contact['batch'])) {
			$parts      = parse_url($contact['notify']);
			$path_parts = explode('/', $parts['path']);
			array_pop($path_parts);
			$parts['path']    = implode('/', $path_parts);
			$contact['batch'] = (string)Uri::fromParts($parts);
		}

		$dest_url = ($public_batch ? $contact['batch'] : $contact['notify']);

		if (empty($dest_url)) {
			DI::logger()->info('Empty destination', ['public' => $public_batch, 'contact' => $contact]);
			return -24;
		}

		$content_type = ($public_batch ? 'application/magic-envelope+xml' : 'application/json');

		try {
			$postResult = DI::httpClient()->post($dest_url, $envelope, ['Content-Type' => $content_type], 0, HttpClientRequest::DFRN);
		} catch (\Throwable $th) {
			DI::logger()->notice('Got exception', ['code' => $th->getCode(), 'message' => $th->getMessage()]);
			return -25;
		}

		$xml = $postResult->getBodyString();

		$curl_stat = $postResult->getReturnCode();
		if (!empty($contact['gsid']) && ($postResult->isTimeout() || empty($curl_stat))) {
			GServer::setFailureById($contact['gsid']);
		}

		if (empty($curl_stat) || empty($xml)) {
			DI::logger()->notice('Empty answer from ' . $contact['id'] . ' - ' . $dest_url);
			return -9; // timed out
		}

		if (($curl_stat == 503) && $postResult->inHeader('retry-after')) {
			return -10;
		}

		if (strpos($xml, '<?xml') === false) {
			DI::logger()->notice('No valid XML returned from ' . $contact['id'] . ' - ' . $dest_url);
			DI::logger()->debug('Returned XML: ' . $xml);
			return 3;
		}

		$res = XML::parseString($xml);

		if (empty($res->status)) {
			return -23;
		}

		if (!empty($contact['gsid'])) {
			GServer::setReachableById($contact['gsid'], Protocol::DFRN);
			Item::incrementOutbound(Protocol::DFRN);
		}

		if (!empty($res->message)) {
			DI::logger()->info('Transmit to ' . $dest_url . ' returned status '.$res->status.' - '.$res->message);
		}

		return intval($res->status);
	}

	/**
	 * Fetch the author data from head or entry items
	 *
	 * @param \DOMXPath $xpath     XPath object
	 * @param \DOMNode  $context   In which context should the data be searched
	 * @param array     $importer  Record of the importer user mixed with contact of the content
	 * @param string    $element   Element name from which the data is fetched
	 * @param bool      $onlyfetch Should the data only be fetched or should it update the contact record as well
	 * @param string    $xml       optional, default empty
	 *
	 * @return array Relevant data of the author
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @todo  Find good type-hints for all parameter
	 */
	private static function fetchauthor(\DOMXPath $xpath, \DOMNode $context, array $importer, string $element, bool $onlyfetch, string $xml = ''): array
	{
		$author         = [];
		$author["name"] = XML::getFirstNodeValue($xpath, $element."/atom:name/text()", $context);
		$author["link"] = XML::getFirstNodeValue($xpath, $element."/atom:uri/text()", $context);

		$fields = ['id', 'uid', 'url', 'network', 'avatar-date', 'avatar', 'name-date', 'uri-date', 'addr',
			'name', 'nick', 'about', 'location', 'keywords', 'xmpp', 'bdyear', 'bd', 'hidden', 'contact-type'];
		$condition = ["`uid` = ? AND `nurl` = ? AND NOT `pending` AND NOT `blocked`",
			$importer["importer_uid"], Strings::normaliseLink($author["link"])];

		if ($importer['account-type'] != User::ACCOUNT_TYPE_COMMUNITY) {
			$condition = DBA::mergeConditions($condition, ['rel' => [Contact::SHARING, Contact::FRIEND]]);
		}

		$contact_old = DBA::selectFirst('contact', $fields, $condition);

		if (DBA::isResult($contact_old)) {
			$author["contact-id"] = $contact_old["id"];
			$author["network"]    = $contact_old["network"];
		} else {
			DI::logger()->info('Contact not found', ['condition' => $condition]);

			$author["contact-unknown"] = true;
			$contact                   = Contact::getByURL($author["link"], null, ["id", "network"]);
			$author["contact-id"]      = $contact["id"]      ?? $importer["id"];
			$author["network"]         = $contact["network"] ?? $importer["network"];
			$onlyfetch                 = true;
		}

		// Until now we aren't serving different sizes - but maybe later
		$avatarlist = [];
		/// @todo check if "avatar" or "photo" would be the best field in the specification
		$avatars = $xpath->query($element . "/atom:link[@rel='avatar']", $context);
		foreach ($avatars as $avatar) {
			$href  = "";
			$width = 0;
			foreach ($avatar->attributes as $attributes) {
				/// @TODO Rewrite these similar if() to one switch
				if ($attributes->name == "href") {
					$href = $attributes->textContent;
				}
				if ($attributes->name == "width") {
					$width = $attributes->textContent;
				}
				if ($attributes->name == "updated") {
					$author["avatar-date"] = $attributes->textContent;
				}
			}
			if (($width > 0) && ($href != "")) {
				$avatarlist[$width] = $href;
			}
		}

		if (count($avatarlist) > 0) {
			krsort($avatarlist);
			$author["avatar"] = current($avatarlist);
		}

		if (empty($author['avatar']) && !empty($author['link'])) {
			$cid = Contact::getIdForURL($author['link'], 0);
			if (!empty($cid)) {
				$contact = DBA::selectFirst('contact', ['avatar'], ['id' => $cid]);
				if (DBA::isResult($contact)) {
					$author['avatar'] = $contact['avatar'];
				}
			}
		}

		if (empty($author['avatar'])) {
			DI::logger()->notice('Empty author: ' . $xml);
			$author['avatar'] = '';
		}

		if (DBA::isResult($contact_old) && !$onlyfetch) {
			DI::logger()->info("Check if contact details for contact " . $contact_old["id"] . " (" . $contact_old["nick"] . ") have to be updated.");

			$poco = ["url" => $contact_old["url"], "network" => $contact_old["network"]];

			// When was the last change to name or uri?
			$name_element = $xpath->query($element . "/atom:name", $context)->item(0);
			foreach ($name_element->attributes as $attributes) {
				if ($attributes->name == "updated") {
					$poco["name-date"] = $attributes->textContent;
				}
			}

			$link_element = $xpath->query($element . "/atom:link", $context)->item(0);
			foreach ($link_element->attributes as $attributes) {
				if ($attributes->name == "updated") {
					$poco["uri-date"] = $attributes->textContent;
				}
			}

			// Update contact data
			$value = XML::getFirstNodeValue($xpath, $element . "/dfrn:handle/text()", $context);
			if ($value != "") {
				$poco["addr"] = $value;
			}

			$value = XML::getFirstNodeValue($xpath, $element . "/poco:displayName/text()", $context);
			if ($value != "") {
				$poco["name"] = $value;
			}

			$value = XML::getFirstNodeValue($xpath, $element . "/poco:preferredUsername/text()", $context);
			if ($value != "") {
				$poco["nick"] = $value;
			}

			$value = XML::getFirstNodeValue($xpath, $element . "/poco:note/text()", $context);
			if ($value != "") {
				$poco["about"] = $value;
			}

			$value = XML::getFirstNodeValue($xpath, $element . "/poco:address/poco:formatted/text()", $context);
			if ($value != "") {
				$poco["location"] = $value;
			}

			/// @todo Only search for elements with "poco:type" = "xmpp"
			$value = XML::getFirstNodeValue($xpath, $element . "/poco:ims/poco:value/text()", $context);
			if ($value != "") {
				$poco["xmpp"] = $value;
			}

			/// @todo Add support for the following fields that we don't support by now in the contact table:
			/// - poco:utcOffset
			/// - poco:urls
			/// - poco:locality
			/// - poco:region
			/// - poco:country

			// If the "hide" element is present then the profile isn't searchable.
			$hide = intval(XML::getFirstNodeValue($xpath, $element . "/dfrn:hide/text()", $context) == "true");

			DI::logger()->info("Hidden status for contact " . $contact_old["url"] . ": " . $hide);

			// If the contact isn't searchable then set the contact to "hidden".
			// Problem: This can be manually overridden by the user.
			if ($hide) {
				$contact_old["hidden"] = true;
			}

			// Save the keywords into the contact table
			$tags        = [];
			$tagelements = $xpath->evaluate($element . "/poco:tags/text()", $context);
			foreach ($tagelements as $tag) {
				$tags[$tag->nodeValue] = $tag->nodeValue;
			}

			if (count($tags)) {
				$poco["keywords"] = implode(", ", $tags);
			}

			// "dfrn:birthday" contains the birthday converted to UTC
			$birthday = XML::getFirstNodeValue($xpath, $element . "/dfrn:birthday/text()", $context);
			try {
				$birthday_date = new \DateTime($birthday);
				if ($birthday_date > new \DateTime()) {
					$poco["bdyear"] = $birthday_date->format("Y");
				}
			} catch (\Exception $e) {
				// Invalid birthday
			}

			// "poco:birthday" is the birthday in the format "yyyy-mm-dd"
			$value = XML::getFirstNodeValue($xpath, $element . "/poco:birthday/text()", $context);

			if (!in_array($value, ["", "0000-00-00", DBA::NULL_DATE])) {
				$bdyear = date("Y");
				$value  = str_replace(["0000", "0001"], $bdyear, $value);

				if (strtotime($value) < time()) {
					$value = str_replace($bdyear, $bdyear + 1, $value);
				}

				$poco["bd"] = $value;
			}

			$contact = array_merge($contact_old, $poco);

			if ($contact_old["bdyear"] != $contact["bdyear"]) {
				Event::createBirthday($contact, $birthday);
			}

			$fields = ['name' => $contact['name'], 'nick' => $contact['nick'], 'about' => $contact['about'],
				'location'       => $contact['location'], 'addr' => $contact['addr'], 'keywords' => $contact['keywords'],
				'bdyear'         => $contact['bdyear'], 'bd' => $contact['bd'], 'hidden' => $contact['hidden'],
				'xmpp'           => $contact['xmpp'], 'name-date' => DateTimeFormat::utc($contact['name-date']),
				'unsearchable'   => $contact['hidden'], 'uri-date' => DateTimeFormat::utc($contact['uri-date'])];

			Contact::update($fields, ['id' => $contact['id'], 'network' => $contact['network']], $contact_old);

			// Update the public contact. Don't set the "hidden" value, this is used differently for public contacts
			unset($fields['hidden']);
			$condition = ['uid' => 0, 'nurl' => Strings::normaliseLink($contact_old['url'])];
			Contact::update($fields, $condition, true);

			Contact::updateAvatar($contact['id'], $author['avatar']);

			$pcid = Contact::getIdForURL($contact_old['url']);
			if (!empty($pcid)) {
				Contact::updateAvatar($pcid, $author['avatar']);
			}
		}

		return $author;
	}

	/**
	 * Transforms activity objects into an XML string
	 *
	 * @param object $xpath    XPath object
	 * @param object $activity Activity object
	 * @param string $element  element name
	 *
	 * @return string XML string
	 * @todo Find good type-hints for all parameter
	 */
	private static function transformActivity($xpath, $activity, string $element): string
	{
		if (!is_object($activity)) {
			return "";
		}

		$obj_doc               = new DOMDocument("1.0", "utf-8");
		$obj_doc->formatOutput = true;

		$obj_element = $obj_doc->createElementNS( ActivityNamespace::ATOM1, $element);

		$activity_type = $xpath->query("activity:object-type/text()", $activity)->item(0)->nodeValue;
		XML::addElement($obj_doc, $obj_element, "type", $activity_type);

		$id = $xpath->query("atom:id", $activity)->item(0);
		if (is_object($id)) {
			$obj_element->appendChild($obj_doc->importNode($id, true));
		}

		$title = $xpath->query("atom:title", $activity)->item(0);
		if (is_object($title)) {
			$obj_element->appendChild($obj_doc->importNode($title, true));
		}

		$links = $xpath->query("atom:link", $activity);
		if (is_object($links)) {
			foreach ($links as $link) {
				$obj_element->appendChild($obj_doc->importNode($link, true));
			}
		}

		$content = $xpath->query("atom:content", $activity)->item(0);
		if (is_object($content)) {
			$obj_element->appendChild($obj_doc->importNode($content, true));
		}

		$obj_doc->appendChild($obj_element);

		$objxml = $obj_doc->saveXML($obj_element);

		/// @todo This isn't totally clean. We should find a way to transform the namespaces
		$objxml = str_replace("<".$element.' xmlns="http://www.w3.org/2005/Atom">', "<".$element.">", $objxml);
		return($objxml);
	}

	/**
	 * Processes the mail elements
	 *
	 * @param DOMXPath $xpath    XPath object
	 * @param DOMNode  $mail     mail elements
	 * @param array    $importer Record of the importer user mixed with contact of the content
	 * @return void
	 * @throws \Exception
	 */
	private static function processMail(DOMXPath $xpath, DOMNode $mail, array $importer)
	{
		DI::logger()->info("Processing mails");

		$msg               = [];
		$msg['uid']        = $importer['importer_uid'];
		$msg['from-name']  = XML::getFirstValue($xpath, 'dfrn:sender/dfrn:name/text()', $mail);
		$msg['from-url']   = XML::getFirstValue($xpath, 'dfrn:sender/dfrn:uri/text()', $mail);
		$msg['from-photo'] = XML::getFirstValue($xpath, 'dfrn:sender/dfrn:avatar/text()', $mail);
		$msg['contact-id'] = $importer['id'];
		$msg['uri']        = XML::getFirstValue($xpath, 'dfrn:id/text()', $mail);
		$msg['parent-uri'] = XML::getFirstValue($xpath, 'dfrn:in-reply-to/text()', $mail);
		$msg['created']    = DateTimeFormat::utc(XML::getFirstValue($xpath, 'dfrn:sentdate/text()', $mail));
		$msg['title']      = XML::getFirstValue($xpath, 'dfrn:subject/text()', $mail);
		$msg['body']       = XML::getFirstValue($xpath, 'dfrn:content/text()', $mail);

		Mail::insert($msg);
	}

	/**
	 * Processes the suggestion elements
	 *
	 * @param DOMXPath $xpath      XPath object
	 * @param DOMNode  $suggestion suggestion elements
	 * @param array    $importer   Record of the importer user mixed with contact of the content
	 * @return boolean
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function processSuggestion(DOMXPath $xpath, DOMNode $suggestion, array $importer)
	{
		DI::logger()->info('Processing suggestions');

		$url  = $xpath->evaluate('string(dfrn:url[1]/text())', $suggestion);
		$cid  = Contact::getIdForURL($url);
		$note = $xpath->evaluate('string(dfrn:note[1]/text())', $suggestion);

		return self::addSuggestion($importer['importer_uid'], $cid, $importer['id'], $note);
	}

	/**
	 * Suggest a given contact to a given user from a given contact
	 *
	 * @param integer $uid
	 * @param integer $cid
	 * @param integer $from_cid
	 * @return bool   Was the adding successful?
	 */
	private static function addSuggestion(int $uid, int $cid, int $from_cid, string $note = ''): bool
	{
		$owner        = User::getOwnerDataById($uid);
		$contact      = Contact::getById($cid);
		$from_contact = Contact::getById($from_cid);

		if (DBA::exists('contact', ['nurl' => Strings::normaliseLink($contact['url']), 'uid' => $uid])) {
			return false;
		}

		// Quit if we already have an introduction for this person
		if (DI::intro()->suggestionExistsForUser($cid, $uid)) {
			return false;
		}

		$suggest            = [];
		$suggest['uid']     = $uid;
		$suggest['cid']     = $from_cid;
		$suggest['url']     = $contact['url'];
		$suggest['name']    = $contact['name'];
		$suggest['photo']   = $contact['photo'];
		$suggest['request'] = $contact['request'];
		$suggest['title']   = '';
		$suggest['body']    = $note;

		DI::intro()->save(DI::introFactory()->createNew(
			$suggest['uid'],
			$suggest['cid'],
			$suggest['body'],
			null,
			$cid
		));

		DI::notify()->createFromArray([
			'type'  => Notification\Type::SUGGEST,
			'otype' => Notification\ObjectType::INTRO,
			'verb'  => Activity::REQ_FRIEND,
			'uid'   => $owner['uid'],
			'cid'   => $from_contact['uid'],
			'item'  => $suggest,
			'link'  => DI::baseUrl().'/notifications/intros',
		]);

		return true;
	}

	/**
	 * Processes the relocation elements
	 *
	 * @param DOMXPath $xpath      XPath object
	 * @param DOMNode  $relocation relocation elements
	 * @param array    $importer   Record of the importer user mixed with contact of the content
	 * @return boolean
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @todo  Find good type-hints for all parameter
	 */
	private static function processRelocation(DOMXPath $xpath, DOMNode $relocation, array $importer): bool
	{
		DI::logger()->info("Processing relocations");

		/// @TODO Rewrite this to one statement
		$relocate               = [];
		$relocate['uid']        = $importer['importer_uid'];
		$relocate['cid']        = $importer['id'];
		$relocate['url']        = $xpath->query('dfrn:url/text()', $relocation)->item(0)->nodeValue;
		$relocate['addr']       = $xpath->query('dfrn:addr/text()', $relocation)->item(0)->nodeValue;
		$relocate['name']       = $xpath->query('dfrn:name/text()', $relocation)->item(0)->nodeValue;
		$relocate['avatar']     = $xpath->query('dfrn:avatar/text()', $relocation)->item(0)->nodeValue;
		$relocate['photo']      = $xpath->query('dfrn:photo/text()', $relocation)->item(0)->nodeValue;
		$relocate['thumb']      = $xpath->query('dfrn:thumb/text()', $relocation)->item(0)->nodeValue;
		$relocate['micro']      = $xpath->query('dfrn:micro/text()', $relocation)->item(0)->nodeValue;
		$relocate['request']    = $xpath->query('dfrn:request/text()', $relocation)->item(0)->nodeValue;
		$relocate['confirm']    = $xpath->query('dfrn:confirm/text()', $relocation)->item(0)->nodeValue;
		$relocate['notify']     = $xpath->query('dfrn:notify/text()', $relocation)->item(0)->nodeValue;
		$relocate['poll']       = $xpath->query('dfrn:poll/text()', $relocation)->item(0)->nodeValue;
		$relocate['sitepubkey'] = $xpath->query('dfrn:sitepubkey/text()', $relocation)->item(0)->nodeValue;

		if (($relocate['avatar'] == '') && ($relocate['photo'] != '')) {
			$relocate['avatar'] = $relocate['photo'];
		}

		if ($relocate['addr'] == '') {
			$relocate['addr'] = preg_replace("=(https?://)(.*)/profile/(.*)=ism", '$3@$2', $relocate['url']);
		}

		// update contact
		$old = Contact::selectFirst(['photo', 'url'], ['id' => $importer['id'], 'uid' => $importer['importer_uid']]);
		if (!DBA::isResult($old)) {
			DI::logger()->warning('Existing contact had not been fetched', ['id' => $importer['id']]);
			return false;
		}

		// Update the contact table. We try to find every entry.
		$fields = [
			'name'        => $relocate['name'],
			'avatar'      => $relocate['avatar'],
			'url'         => $relocate['url'],
			'nurl'        => Strings::normaliseLink($relocate['url']),
			'addr'        => $relocate['addr'],
			'request'     => $relocate['request'],
			'confirm'     => $relocate['confirm'],
			'notify'      => $relocate['notify'],
			'poll'        => $relocate['poll'],
			'site-pubkey' => $relocate['sitepubkey'],
		];
		$condition = ["(`id` = ?) OR (`nurl` = ?)", $importer['id'], Strings::normaliseLink($old['url'])];

		Contact::update($fields, $condition);

		Contact::updateAvatar($importer['id'], $relocate['avatar'], true);

		DI::logger()->info('Contacts are updated.');

		/// @TODO
		/// merge with current record, current contents have priority
		/// update record, set url-updated
		/// update profile photos
		/// schedule a scan?
		return true;
	}

	/**
	 * Updates an item
	 *
	 * @param array $current   the current item record
	 * @param array $item      the new item record
	 * @param array $importer  Record of the importer user mixed with contact of the content
	 * @param int   $entrytype Is it a toplevel entry, a comment or a relayed comment?
	 * @return mixed
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo  set proper type-hints (array?)
	 */
	private static function updateContent(array $current, array $item, array $importer, int $entrytype)
	{
		$changed = false;

		if (self::isEditedTimestampNewer($current, $item)) {
			// do not accept (ignore) an earlier edit than one we currently have.
			if (DateTimeFormat::utc($item['edited']) < $current['edited']) {
				return false;
			}

			$fields = [
				'title'   => $item['title'] ?? '',
				'body'    => $item['body']  ?? '',
				'changed' => DateTimeFormat::utcNow(),
				'edited'  => DateTimeFormat::utc($item['edited']),
			];

			$condition = ["`uri` = ? AND `uid` IN (0, ?)", $item['uri'], $importer['importer_uid']];
			Item::update($fields, $condition);

			$changed = true;
		}
		return $changed;
	}

	/**
	 * Detects the entry type of the item
	 *
	 * @param array $importer Record of the importer user mixed with contact of the content
	 * @param array $item     the new item record
	 *
	 * @return int Is it a toplevel entry, a comment or a relayed comment?
	 * @throws \Exception
	 * @todo  set proper type-hints (array?)
	 */
	private static function getEntryType(array $importer, array $item): int
	{
		if ($item['thr-parent'] != $item['uri']) {
			// was the top-level post for this action written by somebody on this site?
			// Specifically, the recipient?
			if (Post::exists(['uri' => $item['thr-parent'], 'uid' => $importer['importer_uid'], 'self' => true, 'wall' => true])) {
				return self::REPLY_RC;
			} else {
				return self::REPLY;
			}
		} else {
			return self::TOP_LEVEL;
		}
	}

	/**
	 * Processes several actions, depending on the verb
	 *
	 * @param int   $entrytype Is it a toplevel entry, a comment or a relayed comment?
	 * @param array $importer  Record of the importer user mixed with contact of the content
	 * @param array $item      the new item record
	 *
	 * @return bool Should the processing of the entries be continued?
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function processVerbs(int $entrytype, array $importer, array &$item)
	{
		DI::logger()->info('Process verb ' . $item['verb'] . ' and object-type ' . $item['object-type'] . ' for entrytype ' . $entrytype);

		if (($entrytype == self::TOP_LEVEL) && !empty($importer['id'])) {
			// The filling of the "contact" variable is done for legacy reasons
			// The functions below are partly used by ostatus.php as well - where we have this variable
			$contact = Contact::selectFirst([], ['id' => $importer['id']]);

			$activity = DI::activity();

			// Big question: Do we need these functions? They were part of the "consume_feed" function.
			// This function once was responsible for DFRN and OStatus.
			if ($activity->match($item['verb'], Activity::FOLLOW)) {
				DI::logger()->info("New follower");
				Contact::addRelationship($importer, $contact, $item);
				return false;
			}
			if ($activity->match($item['verb'], Activity::UNFOLLOW)) {
				DI::logger()->info("Lost follower");
				Contact::removeFollower($contact);
				return false;
			}
			if ($activity->match($item['verb'], Activity::REQ_FRIEND)) {
				DI::logger()->info("New friend request");
				Contact::addRelationship($importer, $contact, $item, true);
				return false;
			}
			if ($activity->match($item['verb'], Activity::UNFRIEND)) {
				DI::logger()->info("Lost sharer");
				Contact::removeSharer($contact);
				return false;
			}
		} else {
			if (($item['verb'] == Activity::LIKE)
				|| ($item['verb'] == Activity::DISLIKE)
				|| ($item['verb'] == Activity::ATTEND)
				|| ($item['verb'] == Activity::ATTENDNO)
				|| ($item['verb'] == Activity::ATTENDMAYBE)
				|| ($item['verb'] == Activity::ANNOUNCE)
			) {
				$item['gravity'] = Item::GRAVITY_ACTIVITY;
				// only one like or dislike per person
				// split into two queries for performance issues
				$condition = [
					'uid'        => $item['uid'],
					'author-id'  => $item['author-id'],
					'gravity'    => Item::GRAVITY_ACTIVITY,
					'verb'       => $item['verb'],
					'parent-uri' => $item['thr-parent'],
				];
				if (Post::exists($condition)) {
					return false;
				}

				$condition = ['uid' => $item['uid'], 'author-id' => $item['author-id'], 'gravity' => Item::GRAVITY_ACTIVITY,
					'verb'             => $item['verb'], 'thr-parent' => $item['thr-parent']];
				if (Post::exists($condition)) {
					return false;
				}

				// The owner of an activity must be the author
				$item['owner-name']   = $item['author-name'];
				$item['owner-link']   = $item['author-link'];
				$item['owner-avatar'] = $item['author-avatar'];
				$item['owner-id']     = $item['author-id'];
			}

			if (($item['verb'] == Activity::TAG) && ($item['object-type'] == Activity\ObjectType::TAGTERM)) {
				$xo = XML::parseString($item['object']);
				$xt = XML::parseString($item['target']);

				if ($xt->type == Activity\ObjectType::NOTE) {
					$item_tag = Post::selectFirst(['id', 'uri-id'], ['uri' => $xt->id, 'uid' => $importer['importer_uid']]);
					if (!DBA::isResult($item_tag)) {
						DI::logger()->warning('Post had not been fetched', ['uri' => $xt->id, 'uid' => $importer['importer_uid']]);
						return false;
					}

					// extract tag, if not duplicate, add to parent item
					if ($xo->content) {
						Tag::store($item_tag['uri-id'], Tag::HASHTAG, $xo->content);
					}
				}
			}
		}
		return true;
	}

	/**
	 * Processes the link elements
	 *
	 * @param object $links link elements
	 * @param array  $item  the item record
	 * @return void
	 * @todo set proper type-hints
	 */
	private static function parseLinks($links, array &$item)
	{
		$rel    = '';
		$href   = '';
		$type   = null;
		$length = null;
		$title  = null;
		foreach ($links as $link) {
			foreach ($link->attributes as $attributes) {
				switch ($attributes->name) {
					case 'href': $href = $attributes->textContent;
						break;
					case 'rel': $rel = $attributes->textContent;
						break;
					case 'type': $type = $attributes->textContent;
						break;
					case 'length': $length = $attributes->textContent;
						break;
					case 'title': $title = $attributes->textContent;
						break;
				}
			}
			if (($rel != '') && ($href != '')) {
				switch ($rel) {
					case 'alternate':
						$item['plink'] = $href;
						break;

					case 'enclosure':
						Post\Media::insert(['uri-id' => $item['uri-id'], 'type' => Post\Media::DOCUMENT,
							'url'                       => $href, 'mimetype' => $type, 'size' => $length, 'description' => $title]);
						break;
				}
			}
		}
	}

	/**
	 * Checks if an incoming message is wanted
	 *
	 * @param array $item
	 * @param array $importer
	 * @return boolean Is the message wanted?
	 */
	private static function isSolicitedMessage(array $item, array $importer): bool
	{
		if (DBA::exists('contact', ["`nurl` = ? AND `uid` != ? AND `rel` IN (?, ?)",
			Strings::normaliseLink($item["author-link"]), 0, Contact::FRIEND, Contact::SHARING])) {
			DI::logger()->debug('Author has got followers - accepted', ['uri-id' => $item['uri-id'], 'guid' => $item['guid'], 'url' => $item['uri'], 'author' => $item["author-link"]]);
			return true;
		}

		if ($importer['importer_uid'] != 0) {
			DI::logger()->debug('Message is directed to a user - accepted', ['uri-id' => $item['uri-id'], 'guid' => $item['guid'], 'url' => $item['uri'], 'importer' => $importer['importer_uid']]);
			return true;
		}

		if ($item['uri'] != $item['thr-parent']) {
			DI::logger()->debug('Message is no parent - accepted', ['uri-id' => $item['uri-id'], 'guid' => $item['guid'], 'url' => $item['uri']]);
			return true;
		}

		$tags = array_column(Tag::getByURIId($item['uri-id'], [Tag::HASHTAG]), 'name');
		if (Relay::isSolicitedPost($tags, $item['body'], $item['author-id'], $item['uri'], Protocol::DFRN)) {
			DI::logger()->debug('Post is accepted because of the relay settings', ['uri-id' => $item['uri-id'], 'guid' => $item['guid'], 'url' => $item['uri'], 'author' => $item["author-link"]]);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Processes the entry elements which contain the items and comments
	 *
	 * @param array    $header   Array of the header elements that always stay the same
	 * @param DOMXPath $xpath    XPath object
	 * @param DOMNode  $entry    entry elements
	 * @param array    $importer Record of the importer user mixed with contact of the content
	 * @param string   $xml      XML
	 * @param int $protocol Protocol
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @todo  Add type-hints
	 */
	private static function processEntry(array $header, DOMXPath $xpath, DOMNode $entry, array $importer, string $xml, int $protocol)
	{
		DI::logger()->info("Processing entries");

		$item = $header;

		$item['source'] = $xml;

		// Get the uri
		$item['uri'] = XML::getFirstNodeValue($xpath, 'atom:id/text()', $entry);

		$item['edited'] = XML::getFirstNodeValue($xpath, 'atom:updated/text()', $entry);

		$current = Post::selectFirst(
			['id', 'uid', 'edited', 'body'],
			['uri' => $item['uri'], 'uid' => $importer['importer_uid']]
		);
		// Is there an existing item?
		if (DBA::isResult($current) && !self::isEditedTimestampNewer($current, $item)) {
			DI::logger()->info("Item " . $item['uri'] . " (" . $item['edited'] . ") already existed.");
			return;
		}

		// Fetch the owner
		$owner = self::fetchauthor($xpath, $entry, $importer, 'dfrn:owner', true, $xml);

		$owner_unknown = (isset($owner['contact-unknown']) && $owner['contact-unknown']);

		$item['owner-name']   = $owner['name'];
		$item['owner-link']   = $owner['link'];
		$item['owner-avatar'] = $owner['avatar'];
		$item['owner-id']     = Contact::getIdForURL($owner['link'], 0);

		// fetch the author
		$author = self::fetchauthor($xpath, $entry, $importer, 'atom:author', true, $xml);

		$item['author-name']   = $author['name'];
		$item['author-link']   = $author['link'];
		$item['author-avatar'] = $author['avatar'];
		$item['author-id']     = Contact::getIdForURL($author['link'], 0);

		$item['title'] = XML::getFirstNodeValue($xpath, 'atom:title/text()', $entry);

		if (!empty($item['title'])) {
			$item['post-type'] = Item::PT_ARTICLE;
		} else {
			$item['post-type'] = Item::PT_NOTE;
		}

		$item['created'] = XML::getFirstNodeValue($xpath, 'atom:published/text()', $entry);

		$item['body'] = XML::getFirstNodeValue($xpath, 'dfrn:env/text()', $entry);
		$item['body'] = str_replace([' ',"\t","\r","\n"], ['','','',''], $item['body']);

		$item['body'] = Strings::base64UrlDecode($item['body']);

		$item['body'] = BBCode::limitBodySize($item['body']);

		/// @todo We should check for a repeated post and if we know the repeated author.

		// We don't need the content element since "dfrn:env" is always present
		//$item['body'] = $xpath->query('atom:content/text()', $entry)->item(0)->nodeValue;
		$item['location'] = XML::getFirstNodeValue($xpath, 'dfrn:location/text()', $entry);
		$item['coord']    = XML::getFirstNodeValue($xpath, 'georss:point', $entry);
		$item['private']  = XML::getFirstNodeValue($xpath, 'dfrn:private/text()', $entry);

		$unlisted = XML::getFirstNodeValue($xpath, 'dfrn:unlisted/text()', $entry);
		if (!empty($unlisted) && ($item['private'] != Item::PRIVATE)) {
			$item['private'] = Item::UNLISTED;
		}

		$item['extid'] = XML::getFirstNodeValue($xpath, 'dfrn:extid/text()', $entry);

		if (XML::getFirstNodeValue($xpath, 'dfrn:bookmark/text()', $entry) == 'true') {
			$item['post-type'] = Item::PT_PAGE;
		}

		$notice_info = $xpath->query('statusnet:notice_info', $entry);
		if ($notice_info && ($notice_info->length > 0)) {
			foreach ($notice_info->item(0)->attributes as $attributes) {
				if ($attributes->name == 'source') {
					$item['app'] = strip_tags($attributes->textContent);
				}
			}
		}

		$item['guid'] = XML::getFirstNodeValue($xpath, 'dfrn:diaspora_guid/text()', $entry);

		$item['uri-id'] = ItemURI::insert(['uri' => $item['uri'], 'guid' => $item['guid']]);

		$quote_uri_id = Item::getQuoteUriId($item['body'], $item['uid']);
		if (!empty($quote_uri_id)) {
			$item['quote-uri-id'] = $quote_uri_id;
			$item['body']         = BBCode::removeSharedData($item['body']);
		}

		Tag::storeFromBody($item['uri-id'], $item['body']);

		// We store the data from "dfrn:diaspora_signature" in a different table, this is done in "Item::insert"
		$dsprsig = XML::unescape(XML::getFirstNodeValue($xpath, 'dfrn:diaspora_signature/text()', $entry));
		if ($dsprsig != '') {
			$signature = json_decode(base64_decode($dsprsig));
			// We don't store the old style signatures anymore that also contained the "signature" and "signer"
			if (!empty($signature->signed_text) && empty($signature->signature) && empty($signature->signer)) {
				$item['diaspora_signed_text'] = $signature->signed_text;
			}
		}

		$item['verb'] = XML::getFirstNodeValue($xpath, 'activity:verb/text()', $entry);

		if (XML::getFirstNodeValue($xpath, 'activity:object-type/text()', $entry) != '') {
			$item['object-type'] = XML::getFirstNodeValue($xpath, 'activity:object-type/text()', $entry);
		}

		$object         = $xpath->query('activity:object', $entry)->item(0);
		$item['object'] = self::transformActivity($xpath, $object, 'object');

		if (trim($item['object']) != '') {
			$r = XML::parseString($item['object']);
			if (isset($r->type)) {
				$item['object-type'] = $r->type;
			}
		}

		$target         = $xpath->query('activity:target', $entry)->item(0);
		$item['target'] = self::transformActivity($xpath, $target, 'target');

		$categories = $xpath->query('atom:category', $entry);
		if ($categories) {
			foreach ($categories as $category) {
				$term   = '';
				$scheme = '';
				foreach ($category->attributes as $attributes) {
					if ($attributes->name == 'term') {
						$term = $attributes->textContent;
					}

					if ($attributes->name == 'scheme') {
						$scheme = $attributes->textContent;
					}
				}

				if (($term != '') && ($scheme != '')) {
					$parts = explode(':', $scheme);
					if ((count($parts) >= 4) && (array_shift($parts) == 'X-DFRN')) {
						$termurl = array_pop($parts);
						$termurl = array_pop($parts) . ':' . $termurl;
						Tag::store($item['uri-id'], Tag::IMPLICIT_MENTION, $term, $termurl);
					}
				}
			}
		}

		$links = $xpath->query('atom:link', $entry);
		if ($links) {
			self::parseLinks($links, $item);
		}

		$item['conversation'] = XML::getFirstNodeValue($xpath, 'ostatus:conversation/text()', $entry);

		$conv = $xpath->query('ostatus:conversation', $entry);
		if (is_object($conv->item(0))) {
			foreach ($conv->item(0)->attributes as $attributes) {
				if ($attributes->name == 'ref') {
					$item['conversation'] = $attributes->textContent;
				}
				if ($attributes->name == 'href') {
					$item['conversation'] = $attributes->textContent;
				}
			}
		}

		// Is it a reply or a top level posting?
		$item['thr-parent'] = $item['uri'];

		$inreplyto = $xpath->query('thr:in-reply-to', $entry);
		if (is_object($inreplyto->item(0))) {
			foreach ($inreplyto->item(0)->attributes as $attributes) {
				if ($attributes->name == 'ref') {
					$item['thr-parent'] = $attributes->textContent;
				}
			}
		}

		// Check if the message is wanted
		if (!self::isSolicitedMessage($item, $importer)) {
			DBA::delete('item-uri', ['uri' => $item['uri']]);
			return 403;
		}

		// Get the type of the item (Top level post, reply or remote reply)
		$entrytype = self::getEntryType($importer, $item);

		// Now assign the rest of the values that depend on the type of the message
		if (in_array($entrytype, [self::REPLY, self::REPLY_RC])) {
			$item['gravity'] = Item::GRAVITY_COMMENT;

			if (!isset($item['object-type'])) {
				$item['object-type'] = Activity\ObjectType::COMMENT;
			}

			if ($item['contact-id'] != $owner['contact-id']) {
				$item['contact-id'] = $owner['contact-id'];
			}

			if (($item['network'] != $owner['network']) && ($owner['network'] != '')) {
				$item['network'] = $owner['network'];
			}

			if ($item['contact-id'] != $author['contact-id']) {
				$item['contact-id'] = $author['contact-id'];
			}

			if (($item['network'] != $author['network']) && ($author['network'] != '')) {
				$item['network'] = $author['network'];
			}
		}

		if ($entrytype == self::REPLY_RC) {
			$item['wall'] = 1;
		} elseif ($entrytype == self::TOP_LEVEL) {
			$item['gravity'] = Item::GRAVITY_PARENT;

			if (!isset($item['object-type'])) {
				$item['object-type'] = Activity\ObjectType::NOTE;
			}

			// Is it an event?
			if (($item['object-type'] == Activity\ObjectType::EVENT) && !$owner_unknown) {
				DI::logger()->info("Item " . $item['uri'] . " seems to contain an event.");
				$ev = Event::fromBBCode($item['body']);
				if ((!empty($ev['desc']) || !empty($ev['summary'])) && !empty($ev['start'])) {
					DI::logger()->info("Event in item " . $item['uri'] . " was found.");
					$ev['cid']       = $importer['id'];
					$ev['uid']       = $importer['importer_uid'];
					$ev['uri']       = $item['uri'];
					$ev['edited']    = $item['edited'];
					$ev['private']   = $item['private'];
					$ev['guid']      = $item['guid'];
					$ev['plink']     = $item['plink'];
					$ev['network']   = $item['network'];
					$ev['protocol']  = $item['protocol'];
					$ev['direction'] = $item['direction'];
					$ev['source']    = $item['source'];

					$condition = ['uri' => $item['uri'], 'uid' => $importer['importer_uid']];
					$event     = DBA::selectFirst('event', ['id'], $condition);
					if (DBA::isResult($event)) {
						$ev['id'] = $event['id'];
					}

					$event_id = Event::store($ev);
					DI::logger()->info('Event was stored', ['id' => $event_id]);

					$item = Event::getItemArrayForImportedId($event_id, $item);
				}
			}
		}

		if (!self::processVerbs($entrytype, $importer, $item)) {
			DI::logger()->info("Exiting because 'processVerbs' told us so");
			return;
		}

		// This check is done here to be able to receive connection requests in "processVerbs"
		if (($entrytype == self::TOP_LEVEL) && $owner_unknown) {
			DI::logger()->info("Item won't be stored because user " . $importer['importer_uid'] . " doesn't follow " . $item['owner-link'] . ".");
			return;
		}


		// Update content if 'updated' changes
		if (DBA::isResult($current)) {
			if (self::updateContent($current, $item, $importer, $entrytype)) {
				DI::logger()->info("Item " . $item['uri'] . " was updated.");
			} else {
				DI::logger()->info("Item " . $item['uri'] . " already existed.");
			}
			return;
		}

		if (in_array($entrytype, [self::REPLY, self::REPLY_RC])) {
			if (($item['uid'] != 0) && !Post::exists(['uid' => $item['uid'], 'uri' => $item['thr-parent']])) {
				if (DI::pConfig()->get($item['uid'], 'system', 'accept_only_sharer') == Item::COMPLETION_NONE) {
					DI::logger()->info('Completion is set to "none", so we stop here.', ['uid' => $item['uid'], 'owner-id' => $item['owner-id'], 'author-id' => $item['author-id'], 'gravity' => $item['gravity'], 'uri' => $item['uri']]);
					return;
				}
				if (!Contact::isSharing($item['owner-id'], $item['uid']) && !Contact::isSharing($item['author-id'], $item['uid'])) {
					DI::logger()->info('Contact is not sharing with the user', ['uid' => $item['uid'], 'owner-id' => $item['owner-id'], 'author-id' => $item['author-id'], 'gravity' => $item['gravity'], 'uri' => $item['uri']]);
					return;
				}
				if (($item['gravity'] == Item::GRAVITY_ACTIVITY) && DI::pConfig()->get($item['uid'], 'system', 'accept_only_sharer') == Item::COMPLETION_COMMENT) {
					DI::logger()->info('Completion is set to "comment", but this is an activity. so we stop here.', ['uid' => $item['uid'], 'owner-id' => $item['owner-id'], 'author-id' => $item['author-id'], 'gravity' => $item['gravity'], 'uri' => $item['uri']]);
					return;
				}
				DI::logger()->debug('Post is accepted.', ['uid' => $item['uid'], 'owner-id' => $item['owner-id'], 'author-id' => $item['author-id'], 'gravity' => $item['gravity'], 'uri' => $item['uri']]);
			} else {
				DI::logger()->debug('Thread parent exists.', ['uid' => $item['uid'], 'owner-id' => $item['owner-id'], 'author-id' => $item['author-id'], 'gravity' => $item['gravity'], 'uri' => $item['uri']]);
			}

			// Will be overwritten for sharing accounts in Item::insert
			if (empty($item['post-reason']) && ($entrytype == self::REPLY)) {
				$item['post-reason'] = Item::PR_COMMENT;
			}

			$posted_id = Item::insert($item);
			if ($posted_id) {
				DI::logger()->info("Reply from contact " . $item['contact-id'] . " was stored with id " . $posted_id);

				if ($item['uid'] == 0) {
					Item::distribute($posted_id);
				}

				return true;
			}
		} else { // $entrytype == self::TOP_LEVEL
			if (($item['uid'] != 0) && !Contact::isSharing($item['owner-id'], $item['uid']) && !Contact::isSharing($item['author-id'], $item['uid'])) {
				DI::logger()->info('Contact is not sharing with the user', ['uid' => $item['uid'], 'owner-id' => $item['owner-id'], 'author-id' => $item['author-id'], 'gravity' => $item['gravity'], 'uri' => $item['uri']]);
				return;
			}

			// This is my contact on another system, but it's really me.
			// Turn this into a wall post.
			$notify       = Item::isRemoteSelf($importer, $item);
			$item['wall'] = (bool)$notify;

			$posted_id = Item::insert($item, $notify);

			if ($notify) {
				$posted_id = $notify;
			}

			DI::logger()->info("Item was stored with id " . $posted_id);

			if ($item['uid'] == 0) {
				Item::distribute($posted_id);
			}
		}
	}

	/**
	 * Deletes items
	 *
	 * @param DOMXPath $xpath XPath object
	 * @param DOMNode  $deletion deletion elements
	 * @param array   $importer Record of the importer user mixed with contact of the content
	 * @return void
	 * @throws \Exception
	 */
	private static function processDeletion(DOMXPath $xpath, DOMNode $deletion, array $importer)
	{
		DI::logger()->info("Processing deletions");
		$uri = null;

		foreach ($deletion->attributes as $attributes) {
			if ($attributes->name == 'ref') {
				$uri = $attributes->textContent;
			}
		}

		if (!$uri || !$importer['id']) {
			return false;
		}

		$condition = ['uri' => $uri, 'uid' => $importer['importer_uid']];
		$item      = Post::selectFirst(['id', 'parent', 'contact-id', 'uri-id', 'deleted', 'gravity'], $condition);
		if (!DBA::isResult($item)) {
			DI::logger()->info('Item with URI ' . $uri . ' for user ' . $importer['importer_uid'] . ' was not found.');
			return;
		}

		if (DBA::exists('post-category', ['uri-id' => $item['uri-id'], 'uid' => $importer['importer_uid'], 'type' => Post\Category::FILE])) {
			DI::logger()->notice('Item is filed. It will not be deleted.', ['uri' => $uri, 'uri-id' => $item['uri_id'], 'uid' => $importer['importer_uid']]);
			return;
		}

		// When it is a starting post it has to belong to the person that wants to delete it
		if (($item['gravity'] == Item::GRAVITY_PARENT) && ($item['contact-id'] != $importer['id'])) {
			DI::logger()->info('Item with URI ' . $uri . ' do not belong to contact ' . $importer['id'] . ' - ignoring deletion.');
			return;
		}

		// Comments can be deleted by the thread owner or comment owner
		if (($item['gravity'] != Item::GRAVITY_PARENT) && ($item['contact-id'] != $importer['id'])) {
			$condition = ['id' => $item['parent'], 'contact-id' => $importer['id']];
			if (!Post::exists($condition)) {
				DI::logger()->info('Item with URI ' . $uri . ' was not found or must not be deleted by contact ' . $importer['id'] . ' - ignoring deletion.');
				return;
			}
		}

		if ($item['deleted']) {
			return;
		}

		DI::logger()->info('deleting item '.$item['id'].' uri='.$uri);

		Item::markForDeletion(['id' => $item['id']]);
	}

	/**
	 * Imports a DFRN message
	 *
	 * @param string $xml       The DFRN message
	 * @param array  $importer  Record of the importer user mixed with contact of the content
	 * @param int    $protocol  Transport protocol
	 * @param int    $direction Is the message pushed or pulled?
	 * @return integer Import status
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function import(string $xml, array $importer, int $protocol, int $direction): int
	{
		if ($xml == '') {
			return 400;
		}

		$doc = new DOMDocument();
		@$doc->loadXML($xml);

		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('atom', ActivityNamespace::ATOM1);
		$xpath->registerNamespace('thr', ActivityNamespace::THREAD);
		$xpath->registerNamespace('at', ActivityNamespace::TOMB);
		$xpath->registerNamespace('media', ActivityNamespace::MEDIA);
		$xpath->registerNamespace('dfrn', ActivityNamespace::DFRN);
		$xpath->registerNamespace('activity', ActivityNamespace::ACTIVITY);
		$xpath->registerNamespace('georss', ActivityNamespace::GEORSS);
		$xpath->registerNamespace('poco', ActivityNamespace::POCO);
		$xpath->registerNamespace('ostatus', ActivityNamespace::OSTATUS);
		$xpath->registerNamespace('statusnet', ActivityNamespace::STATUSNET);

		$header               = [];
		$header['uid']        = $importer['importer_uid'];
		$header['network']    = Protocol::DFRN;
		$header['protocol']   = $protocol;
		$header['wall']       = 0;
		$header['origin']     = 0;
		$header['contact-id'] = $importer['id'];

		$header = Diaspora::setDirection($header, $direction);

		if ($direction === Conversation::RELAY) {
			$header['post-reason'] = Item::PR_RELAY;
		}

		// Update the contact table if the data has changed

		// The "atom:author" is only present in feeds
		if ($xpath->query('/atom:feed/atom:author')->length > 0) {
			self::fetchauthor($xpath, $doc->firstChild, $importer, 'atom:author', false, $xml);
		}

		// Only the "dfrn:owner" in the head section contains all data
		if ($xpath->query('/atom:feed/dfrn:owner')->length > 0) {
			self::fetchauthor($xpath, $doc->firstChild, $importer, 'dfrn:owner', false, $xml);
		}

		DI::logger()->info("Import DFRN message for user " . $importer['importer_uid'] . " from contact " . $importer['id']);

		if (!empty($importer['gsid']) && ($protocol == Conversation::PARCEL_DIASPORA_DFRN)) {
			GServer::setProtocol($importer['gsid'], Post\DeliveryData::DFRN);
		}

		// is it a public group? Private groups aren't exposed with this method
		$group = intval(XML::getFirstNodeValue($xpath, '/atom:feed/dfrn:community/text()'));

		// The account type is new since 3.5.1
		if ($xpath->query('/atom:feed/dfrn:account_type')->length > 0) {
			// Hint: We are using separate update calls for uid=0 and uid!=0 since a combined call is bad for the database performance

			$accounttype = intval(XML::getFirstNodeValue($xpath, '/atom:feed/dfrn:account_type/text()'));

			if ($accounttype != $importer['contact-type']) {
				Contact::update(['contact-type' => $accounttype], ['id' => $importer['id']]);

				// Updating the public contact as well
				Contact::update(['contact-type' => $accounttype], ['uid' => 0, 'nurl' => $importer['nurl']]);
			}
			// A group contact can either have set "forum" or "prv" - but not both
			if ($accounttype == User::ACCOUNT_TYPE_COMMUNITY) {
				// It's a group, so either set the public or private forum flag
				$condition = ['(`forum` != ? OR `prv` != ?) AND `id` = ?', $group, !$group, $importer['id']];
				Contact::update(['forum' => $group, 'prv' => !$group], $condition);

				// Updating the public contact as well
				$condition = ['(`forum` != ? OR `prv` != ?) AND `uid` = 0 AND `nurl` = ?', $group, !$group, $importer['nurl']];
				Contact::update(['forum' => $group, 'prv' => !$group], $condition);
			} else {
				// It's not a group, so remove the flags
				$condition = ['(`forum` OR `prv`) AND `id` = ?', $importer['id']];
				Contact::update(['forum' => false, 'prv' => false], $condition);

				// Updating the public contact as well
				$condition = ['(`forum` OR `prv`) AND `uid` = 0 AND `nurl` = ?', $importer['nurl']];
				Contact::update(['forum' => false, 'prv' => false], $condition);
			}
		} elseif ($group != $importer['forum']) { // Deprecated since 3.5.1
			$condition = ['`forum` != ? AND `id` = ?', $group, $importer['id']];
			Contact::update(['forum' => $group], $condition);

			// Updating the public contact as well
			$condition = ['`forum` != ? AND `uid` = 0 AND `nurl` = ?', $group, $importer['nurl']];
			Contact::update(['forum' => $group], $condition);
		}


		// We are processing relocations even if we are ignoring a contact
		$relocations = $xpath->query('/atom:feed/dfrn:relocate');
		foreach ($relocations as $relocation) {
			self::processRelocation($xpath, $relocation, $importer);
		}

		if (($importer['uid'] != 0) && !$importer['readonly']) {
			$mails = $xpath->query('/atom:feed/dfrn:mail');
			foreach ($mails as $mail) {
				self::processMail($xpath, $mail, $importer);
			}

			$suggestions = $xpath->query('/atom:feed/dfrn:suggest');
			foreach ($suggestions as $suggestion) {
				self::processSuggestion($xpath, $suggestion, $importer);
			}
		}

		$deletions = $xpath->query('/atom:feed/at:deleted-entry');
		if (!empty($deletions)) {
			foreach ($deletions as $deletion) {
				self::processDeletion($xpath, $deletion, $importer);
			}
			if (count($deletions) > 0) {
				DI::logger()->info(count($deletions) . ' deletions had been processed');
				return 200;
			}
		}

		$entries = $xpath->query('/atom:feed/atom:entry');
		foreach ($entries as $entry) {
			self::processEntry($header, $xpath, $entry, $importer, $xml, $protocol);
		}

		DI::logger()->info("Import done for user " . $importer['importer_uid'] . " from contact " . $importer['id']);
		return 200;
	}

	/**
	 * Returns the activity verb
	 *
	 * @param array $item Item array
	 *
	 * @return string activity verb
	 */
	private static function constructVerb(array $item): string
	{
		if ($item['verb']) {
			return $item['verb'];
		}
		return Activity::POST;
	}

	/**
	 * This function returns true if $update has an edited timestamp newer
	 * than $existing, i.e. $update contains new data which should override
	 * what's already there.  If there is no timestamp yet, the update is
	 * assumed to be newer.  If the update has no timestamp, the existing
	 * item is assumed to be up-to-date.  If the timestamps are equal it
	 * assumes the update has been seen before and should be ignored.
	 *
	 * @param array $existing
	 * @param array $update
	 * @return bool
	 * @throws \Exception
	 */
	private static function isEditedTimestampNewer(array $existing, array $update): bool
	{
		if (empty($existing['edited'])) {
			return true;
		}
		if (empty($update['edited'])) {
			return false;
		}

		$existing_edited = DateTimeFormat::utc($existing['edited']);
		$update_edited   = DateTimeFormat::utc($update['edited']);

		return (strcmp($existing_edited, $update_edited) < 0);
	}

	/**
	 * Checks if the given contact url does support DFRN
	 *
	 * @param string  $url    profile url
	 * @return boolean
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function isSupportedByContactUrl(string $url): bool
	{
		$probe = Probe::uri($url, Protocol::DFRN);
		return $probe['network'] == Protocol::DFRN;
	}
}
