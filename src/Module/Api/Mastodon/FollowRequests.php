<?php

namespace Friendica\Module\Api\Mastodon;

use Friendica\Api\Mastodon;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\APContact;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Introduction;
use Friendica\Module\Base\Api;
use Friendica\Network\HTTPException;

/**
 * @see https://docs.joinmastodon.org/api/rest/follow-requests/
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

	public static function post(array $parameters = [])
	{
		parent::post($parameters);

		/** @var Introduction $Intro */
		$Intro = self::getClass(Introduction::class);
		$Intro->fetch(['id' => $parameters['id'], 'uid' => self::$current_user_id]);

		$contactId = $Intro->{'contact-id'};

		$relationship = new Mastodon\Relationship();
		$relationship->id = $contactId;

		switch ($parameters['action']) {
			case 'authorize':
				$Intro->confirm();
				$relationship = Mastodon\Relationship::createFromContact(Contact::getById($contactId));
				break;
			case 'ignore':
				$Intro->ignore();
				break;
			case 'reject':
				$Intro->discard();
				break;
			default:
				throw new HTTPException\BadRequestException('Unexpected action parameter, expecting "authorize", "ignore" or "reject"');
		}

		System::jsonExit($relationship);
	}

	/**
	 * @param array $parameters
	 * @throws HTTPException\InternalServerErrorException
	 * @see https://docs.joinmastodon.org/api/rest/follow-requests/#get-api-v1-follow-requests
	 */
	public static function rawContent(array $parameters = [])
	{
		$since_id = $_GET['since_id'] ?? null;
		$max_id = $_GET['max_id'] ?? null;
		$limit = intval($_GET['limit'] ?? 40);

		if (isset($since_id) && isset($max_id)) {
			$condition = ['`uid` = ? AND NOT `ignore` AND `id` > ? AND `id` < ?', self::$current_user_id, $since_id, $max_id];
		} elseif (isset($since_id)) {
			$condition = ['`uid` = ? AND NOT `ignore` AND `id` > ?', self::$current_user_id, $since_id];
		} elseif (isset($max_id)) {
			$condition = ['`uid` = ? AND NOT `ignore` AND `id` < ?', self::$current_user_id, $max_id];
		} else {
			$condition = ['`uid` = ? AND NOT `ignore`', self::$current_user_id];
		}

		$count = DBA::count('intro', $condition);

		$intros = DBA::selectToArray(
			'intro',
			[],
			$condition,
			['order' => ['id' => 'DESC'], 'limit' => $limit]
		);

		$return = [];
		foreach ($intros as $intro) {
			$contact = Contact::getById($intro['contact-id']);
			$apcontact = APContact::getByURL($contact['url'], false);
			$account = Mastodon\Account::createFromContact($contact, $apcontact);

			// Not ideal, the same "account" can have multiple ids depending on the context
			$account->id = $intro['id'];

			$return[] = $account;
		}

		$base_query = [];
		if (isset($_GET['limit'])) {
			$base_query['limit'] = $limit;
		}

		$BaseURL = DI::baseUrl();

		$links = [];
		if ($count > $limit) {
			$links[] = '<' . $BaseURL->get() . '/api/v1/follow_requests?' . http_build_query($base_query + ['max_id' => $intros[count($intros) - 1]['id']]) . '>; rel="next"';
		}
		$links[] = '<' . $BaseURL->get() . '/api/v1/follow_requests?' . http_build_query($base_query + ['since_id' => $intros[0]['id']]) . '>; rel="prev"';

		header('Link: ' . implode(', ', $links));

		System::jsonExit($return);
	}
}
