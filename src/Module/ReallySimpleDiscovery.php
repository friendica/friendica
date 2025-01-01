<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
	protected function rawContent(array $request = [])
	{
		$content = XML::fromArray([
			'rsd' => [
				'@attributes' => [
					'version' => '1.0',
					'xmlns'   => 'http://archipelago.phrasewise.com/rsd',
				],
				'service' => [
					'engineName' => 'Friendica',
					'engineLink' => 'http://friendica.com',
					'apis'       => [
						'api' => [
							'@attributes' => [
								'name'      => 'Twitter',
								'preferred' => 'true',
								'apiLink'   => DI::baseUrl(),
								'blogID'    => '',
							],
							'settings' => [
								'docs' => [
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
		]);
		$this->httpExit($content, Response::TYPE_XML);
	}
}
