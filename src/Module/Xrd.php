<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Photo;
use Friendica\Model\User;
use Friendica\Protocol\ActivityNamespace;
use Friendica\Protocol\Salmon;
use Friendica\Util\Strings;

/**
 * Prints responses to /.well-known/webfinger  or /xrd requests
 */
class Xrd extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$app = DI::app();

		// @TODO: Replace with parameter from router
		if ($app->argv[0] == 'xrd') {
			if (empty($_GET['uri'])) {
				return;
			}

			$uri = urldecode(Strings::escapeTags(trim($_GET['uri'])));
			if (($_SERVER['HTTP_ACCEPT'] ?? '') == 'application/jrd+json') {
				$mode = 'json';
			} else {
				$mode = 'xml';
			}
		} else {
			if (empty($_GET['resource'])) {
				return;
			}

			$uri = urldecode(Strings::escapeTags(trim($_GET['resource'])));
			if (($_SERVER['HTTP_ACCEPT'] ?? '') == 'application/xrd+xml') {
				$mode = 'xml';
			} else {
				$mode = 'json';
			}
		}

		if (substr($uri, 0, 4) === 'http') {
			$name = ltrim(basename($uri), '~');
		} else {
			$local = str_replace('acct:', '', $uri);
			if (substr($local, 0, 2) == '//') {
				$local = substr($local, 2);
			}

			$name = substr($local, 0, strpos($local, '@'));
		}

		$user = User::getByNickname($name);

		if (empty($user)) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		$owner = User::getOwnerDataById($user['uid']);

		$alias = str_replace('/profile/', '/~', $owner['url']);

		$avatar = Photo::selectFirst(['type'], ['uid' => $owner['uid'], 'profile' => true]);

		if (!DBA::isResult($avatar)) {
			$avatar = ['type' => 'image/jpeg'];
		}

		if ($mode == 'xml') {
			self::printXML($alias, DI::baseUrl()->get(), $user, $owner, $avatar);
		} else {
			self::printJSON($alias, DI::baseUrl()->get(), $owner, $avatar);
		}
	}

	private static function printJSON($alias, $baseURL, $owner, $avatar)
	{
		$salmon_key = Salmon::salmonKey($owner['spubkey']);

		header('Access-Control-Allow-Origin: *');
		header('Content-type: application/json; charset=utf-8');

		$json = [
			'subject' => 'acct:' . $owner['addr'],
			'aliases' => [
				$alias,
				$owner['url'],
			],
			'links'   => [
				[
					'rel'  => ActivityNamespace::DFRN ,
					'href' => $owner['url'],
				],
				[
					'rel'  => ActivityNamespace::FEED,
					'type' => 'application/atom+xml',
					'href' => $owner['poll'],
				],
				[
					'rel'  => 'http://webfinger.net/rel/profile-page',
					'type' => 'text/html',
					'href' => $owner['url'],
				],
				[
					'rel'  => 'self',
					'type' => 'application/activity+json',
					'href' => $owner['url'],
				],
				[
					'rel'  => 'http://microformats.org/profile/hcard',
					'type' => 'text/html',
					'href' => $baseURL . '/hcard/' . $owner['nickname'],
				],
				[
					'rel'  => ActivityNamespace::POCO,
					'href' => $owner['poco'],
				],
				[
					'rel'  => 'http://webfinger.net/rel/avatar',
					'type' => $avatar['type'],
					'href' => $owner['photo'],
				],
				[
					'rel'  => 'http://joindiaspora.com/seed_location',
					'type' => 'text/html',
					'href' => $baseURL,
				],
				[
					'rel'  => 'salmon',
					'href' => $baseURL . '/salmon/' . $owner['nickname'],
				],
				[
					'rel'  => 'http://salmon-protocol.org/ns/salmon-replies',
					'href' => $baseURL . '/salmon/' . $owner['nickname'],
				],
				[
					'rel'  => 'http://salmon-protocol.org/ns/salmon-mention',
					'href' => $baseURL . '/salmon/' . $owner['nickname'] . '/mention',
				],
				[
					'rel'      => 'http://ostatus.org/schema/1.0/subscribe',
					'template' => $baseURL . '/follow?url={uri}',
				],
				[
					'rel'  => 'magic-public-key',
					'href' => 'data:application/magic-public-key,' . $salmon_key,
				],
				[
					'rel'  => 'http://purl.org/openwebauth/v1',
					'type' => 'application/x-zot+json',
					'href' => $baseURL . '/owa',
				],
			],
		];

		echo json_encode($json);
		exit();
	}

	private static function printXML($alias, $baseURL, $user, $owner, $avatar)
	{
		$salmon_key = Salmon::salmonKey($owner['spubkey']);

		header('Access-Control-Allow-Origin: *');
		header('Content-type: text/xml');

		$tpl = Renderer::getMarkupTemplate('xrd_person.tpl');

		$o = Renderer::replaceMacros($tpl, [
			'$nick'        => $owner['nickname'],
			'$accturi'     => 'acct:' . $owner['addr'],
			'$alias'       => $alias,
			'$profile_url' => $owner['url'],
			'$hcard_url'   => $baseURL . '/hcard/' . $owner['nickname'],
			'$atom'        => $owner['poll'],
			'$poco_url'    => $owner['poco'],
			'$photo'       => $owner['photo'],
			'$type'        => $avatar['type'],
			'$salmon'      => $baseURL . '/salmon/' . $owner['nickname'],
			'$salmen'      => $baseURL . '/salmon/' . $owner['nickname'] . '/mention',
			'$subscribe'   => $baseURL . '/follow?url={uri}',
			'$openwebauth' => $baseURL . '/owa',
			'$modexp'      => 'data:application/magic-public-key,' . $salmon_key
		]);

		$arr = ['user' => $user, 'xml' => $o];
		Hook::callAll('personal_xrd', $arr);

		echo $arr['xml'];
		exit();
	}
}
