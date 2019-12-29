<?php

namespace Friendica\Module\Api\Mastodon;

use Friendica\Api\Mastodon;
use Friendica\Core\System;
use Friendica\Model\APContact;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Introduction;
use Friendica\Module\Base\Api;
use Friendica\Network\HTTPException;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/follow_requests
 */
class FollowRequests extends Api
{
	public static function init(array $parameters = [])
	{
		parent::init($parameters);

		if (!self::login()) {
			throw new HTTPException\UnauthorizedException();
		}
	}

	/**
	 * @param array $parameters
	 * @throws HTTPException\BadRequestException
	 * @throws HTTPException\ForbiddenException
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 * @throws HTTPException\UnauthorizedException
	 * @throws \ImagickException
	 *
	 * @see https://docs.joinmastodon.org/methods/accounts/follow_requests#accept-follow
	 * @see https://docs.joinmastodon.org/methods/accounts/follow_requests#reject-follow
	 */
	public static function post(array $parameters = [])
	{
		parent::post($parameters);

		$Intro = DI::intro()->fetch(['id' => $parameters['id'], 'uid' => self::$current_user_id]);

		$contactId = $Intro->{'contact-id'};

		switch ($parameters['action']) {
			case 'authorize':
				$Intro->confirm();
				$relationship = Mastodon\Relationship::createFromContact(Contact::getById($contactId));
				break;
			case 'ignore':
				$Intro->ignore();
				$relationship = Mastodon\Relationship::createDefaultFromContactId($contactId);
				break;
			case 'reject':
				$Intro->discard();
				$relationship = Mastodon\Relationship::createDefaultFromContactId($contactId);
				break;
			default:
				throw new HTTPException\BadRequestException('Unexpected action parameter, expecting "authorize", "ignore" or "reject"');
		}

		System::jsonExit($relationship);
	}

	/**
	 * @param array $parameters
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @see https://docs.joinmastodon.org/methods/accounts/follow_requests#pending-follows
	 */
	public static function rawContent(array $parameters = [])
	{
		$since_id = $_GET['since_id'] ?? null;
		$max_id = $_GET['max_id'] ?? null;
		$limit = intval($_GET['limit'] ?? 40);

		$baseUrl = DI::baseUrl();

		$Introductions = DI::intros()->selectByBoundaries(
			['`uid` = ? AND NOT `ignore`', self::$current_user_id],
			['order' => ['id' => 'DESC']],
			$since_id,
			$max_id,
			$limit
		);

		$return = [];

		/** @var Introduction $Introduction */
		foreach ($Introductions as $Introduction) {
			$cdata = Contact::getPublicAndUserContacID($Introduction->{'contact-id'}, $Introduction->uid);
			if (empty($cdata['public'])) {
				continue;
			}

			$publicContact = Contact::getById($cdata['public']);
			$userContact = Contact::getById($cdata['user']);
			$apcontact = APContact::getByURL($publicContact['url'], false);
			$followRequest = Mastodon\FollowRequest::createFromIntroduction($baseUrl, $Introduction, $publicContact, $apcontact);

			$return[] = $followRequest;
		}

		$base_query = [];
		if (isset($_GET['limit'])) {
			$base_query['limit'] = $limit;
		}

		$links = [];
		if ($Introductions->getTotalCount() > $limit) {
			$links[] = '<' . $baseUrl->get() . '/api/v1/follow_requests?' . http_build_query($base_query + ['max_id' => $Introductions[count($Introductions) - 1]->id]) . '>; rel="next"';
		}
		$links[] = '<' . $baseUrl->get() . '/api/v1/follow_requests?' . http_build_query($base_query + ['since_id' => $Introductions[0]->id]) . '>; rel="prev"';

		header('Link: ' . implode(', ', $links));

		System::jsonExit($return);
	}
}
