<?php

namespace Friendica\Module\Api\Mastodon;

use Friendica\Api\Entity\Mastodon;
use Friendica\Api\Entity\Mastodon\Relationship;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Contact;
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

		$Introduction = DI::intro()->selectFirst(['id' => $parameters['id'], 'uid' => self::$current_user_id]);

		$contactId = $Introduction->{'contact-id'};

		switch ($parameters['action']) {
			case 'authorize':
				$Introduction->confirm();
				DI::intro()->delete($Introduction);

				$relationship = new Relationship($contactId, Contact::getById($contactId));
				break;
			case 'ignore':
				$Introduction->ignore();

				$relationship = new Relationship($contactId);
				break;
			case 'reject':
				$Introduction->discard();
				DI::intro()->delete($Introduction);

				$relationship = new Relationship($contactId);
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

		$Introductions = DI::intro()->selectByBoundaries(
			['`uid` = ? AND NOT `ignore`', self::$current_user_id],
			['order' => ['id' => 'DESC']],
			$since_id,
			$max_id,
			$limit
		);

		$return = [];

		foreach ($Introductions as $key => $Introduction) {
			try {
				$return[] = DI::mstdnFollowRequest()->createFromIntroduction($Introduction);
			} catch (HTTPException\InternalServerErrorException $exception) {
				DI::intro()->delete($Introduction);
				unset($Introductions[$key]);
			}
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
