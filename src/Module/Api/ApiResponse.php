<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Module\Api;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\Core\L10n;
use Friendica\Module\Response;
use Friendica\Util\Arrays;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\XML;
use Psr\Log\LoggerInterface;
use Friendica\Factory\Api\Twitter\User as TwitterUser;

/**
 * This class is used to format and create API responses
 */
class ApiResponse extends Response
{
	/** @var L10n */
	protected $l10n;
	/** @var Arguments */
	protected $args;
	/** @var LoggerInterface */
	protected $logger;
	/** @var BaseURL */
	protected $baseUrl;
	/** @var TwitterUser */
	protected $twitterUser;

	public function __construct(L10n $l10n, Arguments $args, LoggerInterface $logger, BaseURL $baseUrl, TwitterUser $twitterUser)
	{
		$this->l10n        = $l10n;
		$this->args        = $args;
		$this->logger      = $logger;
		$this->baseUrl     = $baseUrl;
		$this->twitterUser = $twitterUser;
	}

	/**
	 * Creates the XML from a JSON style array
	 *
	 * @param array  $data         JSON style array
	 * @param string $root_element Name of the root element
	 *
	 * @return string The XML data
	 */
	public function createXML(array $data, string $root_element): string
	{
		$childname = key($data);
		$data2     = array_pop($data);

		$namespaces = [
			''          => 'http://api.twitter.com',
			'statusnet' => 'http://status.net/schema/api/1/',
			'friendica' => 'http://friendi.ca/schema/api/1/',
			'georss'    => 'http://www.georss.org/georss'
		];

		/// @todo Auto detection of needed namespaces
		if (in_array($root_element, ['ok', 'hash', 'config', 'version', 'ids', 'notes', 'photos'])) {
			$namespaces = [];
		}

		if (is_array($data2)) {
			$key = key($data2);
			Arrays::walkRecursive($data2, ['Friendica\Module\Api\ApiResponse', 'reformatXML']);

			if ($key == '0') {
				$data4 = [];
				$i     = 1;

				foreach ($data2 as $item) {
					$data4[$i++ . ':' . $childname] = $item;
				}

				$data2 = $data4;
			}
		}

		$data3 = [$root_element => $data2];

		return XML::fromArray($data3, $dummy, false, $namespaces);
	}

	/**
	 * Set values for RSS template
	 *
	 * @param array $arr Array to be passed to template
	 * @param int   $cid Contact ID of template
	 *
	 * @return array
	 */
	private function addRSSValues(array $arr, int $cid): array
	{
		if (empty($cid)) {
			return $arr;
		}

		$user_info = $this->twitterUser->createFromContactId($cid)->toArray();

		$arr['$user'] = $user_info;
		$arr['$rss'] = [
			'alternate'    => $user_info['url'],
			'self'         => $this->baseUrl . '/' . $this->args->getQueryString(),
			'base'         => $this->baseUrl,
			'updated'      => DateTimeFormat::utcNow(DateTimeFormat::API),
			'atom_updated' => DateTimeFormat::utcNow(DateTimeFormat::ATOM),
			'language'     => $user_info['lang'],
			'logo'         => $this->baseUrl . '/images/friendica-32.png',
		];

		return $arr;
	}

	/**
	 * Formats the data according to the data type
	 *
	 * @param string $root_element Name of the root element
	 * @param string $type         Return type (atom, rss, xml, json)
	 * @param array  $data         JSON style array
	 * @param int    $cid          ID of the contact for RSS
	 *
	 * @return array|string (string|array) XML data or JSON data
	 */
	public function formatData(string $root_element, string $type, array $data, int $cid = 0)
	{
		switch ($type) {
			case 'rss':
				$data = $this->addRSSValues($data, $cid);
			case 'atom':
			case 'xml':
				return $this->createXML($data, $root_element);

			case 'json':
			default:
				return $data;
		}
	}

	/**
	 * Callback function to transform the array in an array that can be transformed in a XML file
	 *
	 * @param mixed  $item Array item value
	 * @param string $key  Array key
	 *
	 * @return boolean
	 */
	public static function reformatXML(&$item, string &$key): bool
	{
		if (is_bool($item)) {
			$item = ($item ? 'true' : 'false');
		}

		if (substr($key, 0, 10) == 'statusnet_') {
			$key = 'statusnet:' . substr($key, 10);
		} elseif (substr($key, 0, 10) == 'friendica_') {
			$key = 'friendica:' . substr($key, 10);
		}
		return true;
	}

	/**
	 * Exit with error code
	 *
	 * @param int         $code
	 * @param string      $description
	 * @param string      $message
	 * @param string|null $format
	 *
	 * @return void
	 */
	public function error(int $code, string $description, string $message, string $format = null)
	{
		$error = [
			'error'   => $message ?: $description,
			'code'    => $code . ' ' . $description,
			'request' => $this->args->getQueryString()
		];

		$this->setHeader(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1') . ' ' . $code . ' ' . $description);

		$this->exit('status', ['status' => $error], $format);
	}

	/**
	 * Outputs formatted data according to the data type and then exits the execution.
	 *
	 * @param string      $root_element
	 * @param array       $data   An array with a single element containing the returned result
	 * @param string|null $format Output format (xml, json, rss, atom)
	 *
	 * @return void
	 */
	public function exit(string $root_element, array $data, string $format = null, int $cid = 0)
	{
		$format = $format ?? 'json';

		$return = $this->formatData($root_element, $format, $data, $cid);

		switch ($format) {
			case 'xml':
				$this->setType(static::TYPE_XML);
				break;

			case 'json':
				$this->setType(static::TYPE_JSON);
				if (!empty($return)) {
					$json = json_encode(end($return));
					if (!empty($_GET['callback'])) {
						$json = $_GET['callback'] . '(' . $json . ')';
					}
					$return = $json;
				}
				break;

			case 'rss':
				$this->setType(static::TYPE_RSS);
				break;

			case 'atom':
				$this->setType(static::TYPE_ATOM);
				break;
		}

		$this->addContent($return);
	}

	/**
	 * Wrapper around exit() for JSON only responses
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function exitWithJson(array $data)
	{
		$this->exit('content', ['content' => $data], static::TYPE_JSON);
	}

	/**
	 * Quit execution with the message that the endpoint isn't implemented
	 *
	 * @param string $method
	 * @param array  $request (optional) The request content of the current call for later analysis
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function unsupported(string $method = 'all', array $request = [])
	{
		$path = $this->args->getQueryString();
		$this->logger->info('Unimplemented API call',
			[
				'method'  => $method,
				'path'    => $path,
				'agent'   => $_SERVER['HTTP_USER_AGENT'] ?? '',
				'request' => $request,
			]);
		$error = $this->l10n->t('API endpoint %s %s is not implemented but might be in the future.', strtoupper($method), $path);

		$this->error(501, 'Not Implemented', $error);
	}
}
