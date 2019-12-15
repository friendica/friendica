<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Util\XML;

/**
 * Prints the rsd.xml
 * @see http://danielberlinger.github.io/rsd/
 */
class ReallySimpleDiscovery extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		header('Content-Type: text/xml');

		$app = DI::app();
		$xml = null;
		echo XML::fromArray([
			'rsd' => [
				'@attributes' => [
					'version' => '1.0',
					'xmlns'   => 'http://archipelago.phrasewise.com/rsd',
				],
				'service'     => [
					'engineName' => 'Friendica',
					'engineLink' => 'http://friendica.com',
					'apis'       => [
						'api' => [
							'@attributes' => [
								'name'      => 'Twitter',
								'preferred' => 'true',
								'apiLink'   => $app->getBaseURL(),
								'blogID'    => '',
							],
							'settings'    => [
								'docs'    => [
									'http://status.net/wiki/TwitterCompatibleAPI',
								],
								'setting' => [
									'@attributes' => [
										'name' => 'OAuth',
									],
									'false',
								],
							],
						]
					],
				],
			],
		], $xml);
		exit();
	}
}
