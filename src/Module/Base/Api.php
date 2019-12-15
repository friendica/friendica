<?php

namespace Friendica\Module\Base;

use Friendica\App\Arguments;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\DI;
use Friendica\Network\HTTPException;

require_once __DIR__ . '/../../../include/api.php';

class Api extends BaseModule
{
	/**
	 * @var string json|xml|rss|atom
	 */
	protected static $format = 'json';
	/**
	 * @var bool|int
	 */
	protected static $current_user_id;

	public static function init(array $parameters = [])
	{
		$arguments = DI::args();

		if (substr($arguments->getQueryString(), -4) === '.xml') {
			self::$format = 'xml';
		}
		if (substr($arguments->getQueryString(), -4) === '.rss') {
			self::$format = 'rss';
		}
		if (substr($arguments->getQueryString(), -4) === '.atom') {
			self::$format = 'atom';
		}
	}

	public static function post(array $parameters = [])
	{
		if (!api_user()) {
			throw new HTTPException\UnauthorizedException(L10n::t('Permission denied.'));
		}

		$a = DI::app();

		if (!empty($a->user['uid']) && $a->user['uid'] != api_user()) {
			throw new HTTPException\ForbiddenException(L10n::t('Permission denied.'));
		}
	}

	/**
	 * Log in user via OAuth1 or Simple HTTP Auth.
	 * Simple Auth allow username in form of <pre>user@server</pre>, ignoring server part
	 *
	 * @brief Login API user
	 *
	 * @return bool Was a user authenticated?
	 * @throws HTTPException\ForbiddenException
	 * @throws HTTPException\UnauthorizedException
	 * @throws HTTPException\InternalServerErrorException
	 * @hook  'authenticate'
	 *               array $addon_auth
	 *               'username' => username from login form
	 *               'password' => password from login form
	 *               'authenticated' => return status,
	 *               'user_record' => return authenticated user record
	 */
	protected static function login()
	{
		api_login(DI::app());

		self::$current_user_id = api_user();

		return (bool)self::$current_user_id;
	}

	/**
	 * @brief Get user info array.
	 *
	 * @param int|string $contact_id Contact ID or URL
	 * @return array|bool
	 * @throws HTTPException\BadRequestException
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\UnauthorizedException
	 * @throws \ImagickException
	 */
	protected static function getUser($contact_id = null)
	{
		return api_get_user(DI::app(), $contact_id);
	}

	protected static function format($root_element, $data)
	{
		switch (self::$format) {
			case "atom":
			case "rss":
			case "xml":
				$ret = api_create_xml($data, $root_element);
				break;
			case "json":
			default:
				$ret = $data;
				break;
		}

		return $ret;
	}
}
