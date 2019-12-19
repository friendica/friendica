<?php

namespace Friendica\Module\Diaspora;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Diaspora;
use Friendica\Util\Network;
use Psr\Log\LoggerInterface;

/**
 * This module is part of the Diaspora protocol.
 * It is used for receiving single posts either for public or for a specific user.
 */
class Receive extends BaseModule
{
	/** @var LoggerInterface */
	private static $logger;

	public static function init(array $parameters = [])
	{
		self::$logger = DI::logger();
	}

	public static function post(array $parameters = [])
	{
		$enabled = DI::config()->get('system', 'diaspora_enabled', false);
		if (!$enabled) {
			self::$logger->info('Diaspora disabled.');
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Access denied.'));
		}

		$args = DI::args();

		$type = $args->get(1);

		switch ($type) {
			case 'public':
				self::receivePublic();
				break;
			case 'users':
				self::receiveUser($args->get(2));
				break;
			default:
				self::$logger->info('Wrong call.');
				throw new HTTPException\BadRequestException('wrong call.');
				break;
		}
	}

	/**
	 * Receive a public Diaspora posting
	 *
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function receivePublic()
	{
		self::$logger->info('Diaspora: Receiving post.');

		$msg = self::decodePost();

		self::$logger->info('Diaspora: Dispatching.');

		Diaspora::dispatchPublic($msg);
	}

	/**
	 * Receive a Diaspora posting for a user
	 *
	 * @param string $guid The GUID of the importer
	 *
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function receiveUser(string $guid)
	{
		self::$logger->info('Diaspora: Receiving post.');

		$importer = User::getByGuid($guid);

		$msg = self::decodePost(false, $importer['prvkey'] ?? '');

		self::$logger->info('Diaspora: Dispatching.');

		if (Diaspora::dispatch($importer, $msg)) {
			throw new HTTPException\OKException();
		} else {
			throw new HTTPException\InternalServerErrorException();
		}
	}

	/**
	 * Decodes a Diaspora message based on the posted data
	 *
	 * @param string $privKey The private key of the importer
	 * @param bool   $public  True, if the post is public
	 *
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function decodePost(bool $public = true, string $privKey = '')
	{
		if (empty($_POST['xml'])) {

			$postdata = Network::postdata();

			if (empty($postdata)) {
				throw new HTTPException\InternalServerErrorException('Missing postdata.');
			}

			self::$logger->info('Diaspora: Message is in the new format.');

			$msg = Diaspora::decodeRaw($postdata, $privKey);
		} else {

			$xml = urldecode($_POST['xml']);

			self::$logger->info('Diaspora: Decode message in the old format.');
			$msg = Diaspora::decode($xml, $privKey);

			if ($public && !$msg) {
				self::$logger->info('Diaspora: Decode message in the new format.');
				$msg = Diaspora::decodeRaw($xml, $privKey);
			}
		}

		self::$logger->info('Diaspora: Post decoded.');
		self::$logger->debug('Diaspora: Decoded message.', ['msg' => print_r($msg, true)]);

		if (!is_array($msg)) {
			throw new HTTPException\InternalServerErrorException('Message is not an array.');
		}

		return $msg;
	}
}
