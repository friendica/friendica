<?php
/**
 * @copyright Copyright (C) 2021, Friendica
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

namespace Friendica\Module;

use Friendica\Core\Logger;
use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;

use ParagonIE\Paseto\Builder;
use ParagonIE\Paseto\Parser;
use ParagonIE\Paseto\Purpose;
use ParagonIE\Paseto\Keys\SymmetricKey;
use ParagonIE\Paseto\Protocol\Version2;
use ParagonIE\Paseto\Exception\PasetoException;
use ParagonIE\Paseto\JsonToken;
use ParagonIE\Paseto\Rules\{
    IssuedBy,
    ValidAt
};

require_once __DIR__ . '/../../include/api.php';


/**
 * TODO: Define scopes, add scopes, add scope validation (user, user:email, admin...)
 */
abstract class OAuthApi extends BaseApi
{

	/**
	 * Define basic token purpose/application
	 * Useful to sort through the tokens and easily change maximum permitted scope
	 * 
	 * Use case: An infected device or a XSS attack allow a third-party app to delete accounts
	 * without confirmation. We can easily change all third-party apps permission by removing
	 * that specific scope even if the token allows it (because the user granted the permission
	 * in the first place for convenience)
	 */
	const TOKEN_LOCAL = 1; // Local to this instance using current Smart3 templating system
	const TOKEN_WEB = 2; // A web client/PWA app
	const TOKEN_APP = 3; // A native or electron app
	const TOKEN_EMBEDAPP = 4; // Embedded iframe app for friendica, scope is limited by default
	const TOKEN_BOT = 5;


	/**
	 * @var string json|xml|rss|atom
	 */
	protected static $format = 'json';
	
	/**
	 * @var bool|int
	 */
	protected static $token;

	/**
	 * The purpose constant value for which this token will be used for
	 *
	 * @var [type]
	 */
	protected static $purpose;

	/**
	 * To which the token is intended for. Either a domain name or an APP ID
	 *
	 * @var string
	 */
	protected static $audience;


	protected static function checkOAuthSupport() {
		$is_enabled = DI::config()->get('api', 'enable');
		if(!$is_enabled) {
			// FIXME: Needs to return JSON
			throw new HTTPException\ImATeapotException('Endpoint disabled by administrator');
		}
	}
	
	public static function generateAccessToken()
	{
		$paseto_synchronous_key = DI::config()->get('api', 'paseto_synchronous_key');
		$site = DI::config()->get('system', 'url');

		$token = (new Builder())
			->setKey(new SymmetricKey($paseto_synchronous_key))
			->setVersion(new Version2())
			->setPurpose(Purpose::local())
			->setIssuer($site)
			->setIssuedAt()
			->setNotBefore()
			->setExpiration(
				(new \DateTime())->add(new \DateInterval('PT20M')) // 20 minutes
			)
			->setClaims([
				'example' => 'Hello world',
				'security' => 'Now as easy as PIE'
			]);
		return $token->toString();
	}

	/**
	 * Basic check for an access token. This should be called on every endpoint that is protected.
	 * 
	 * @param JsonToken $token
	 * @return boolean
	 */
	public static function ValidateAccessToken(string $accessToken, $purpose): bool
	{
		$paseto_synchronous_key = DI::config()->get('api', 'paseto_synchronous_key');
		$issuedBy = DI::config()->get('system', 'url');

		if($purpose !== self::TOKEN_LOCAL) {
			// If the purpose is not local, set the audience
			$audience = "APP_ID_OR_DOMAIN"; // aud claim

			Logger::error('PASETO Purpose', ['purpose' => $purpose]);
			throw new HTTPException\NotImplementedException;
		}

		$parser = (new Parser())
			->setKey(new SymmetricKey($paseto_synchronous_key))
			// Adding rules to be checked against the token
			->addRule(new ValidAt)
			->addRule(new IssuedBy($issuedBy))
			->setPurpose(Purpose::local())
			// Only allow version 2
			->setAllowedVersions(new Version2());

		try {
			$token = $parser->parse($accessToken);
		} catch (PasetoException $ex) {
			/* Handle invalid token cases here. */
		}
		return false;
	}

	/**
	 * Check PASETO Refresh Token validity and integrity and compare values to the database
	 * to see if it was revoked or re-issued early.
	 *
	 * @param [type] $token
	 * @return bool
	 */
	protected static function ValidateRefreshToken($token): bool {
		return false;
	}

}
