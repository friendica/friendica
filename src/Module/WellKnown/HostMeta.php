<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\WellKnown;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Module\Response;
use Friendica\Util\Crypto;
use Friendica\Util\XML;

/**
 * Prints the metadata for describing this host
 * @see https://tools.ietf.org/html/rfc6415
 */
class HostMeta extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$config = DI::config();

		if (!$config->get('system', 'site_pubkey', false)) {
			$res = Crypto::newKeypair(1024);

			$config->set('system', 'site_prvkey', $res['prvkey']);
			$config->set('system', 'site_pubkey', $res['pubkey']);
		}

		$domain = (string)DI::baseUrl();

		XML::fromArray([
			'XRD' => [
				'@attributes' => [
					'xmlns' => 'http://docs.oasis-open.org/ns/xri/xrd-1.0',
				],
				'hm:Host' => DI::baseUrl()->getHost(),
				'1:link'  => [
					'@attributes' => [
						'rel'      => 'lrdd',
						'type'     => 'application/xrd+xml',
						'template' => $domain . '/xrd?uri={uri}'
					]
				],
				'2:link' => [
					'@attributes' => [
						'rel'      => 'lrdd',
						'type'     => 'application/json',
						'template' => $domain . '/.well-known/webfinger?resource={uri}'
					]
				],
				'3:link' => [
					'@attributes' => [
						'rel'  => 'acct-mgmt',
						'href' => $domain . '/amcd'
					]
				],
				'4:link' => [
					'@attributes' => [
						'rel'  => 'http://services.mozilla.com/amcd/0.1',
						'href' => $domain . '/amcd'
					]
				],
			],
		], $xml, false, ['hm' => 'http://host-meta.net/xrd/1.0']);

		$this->httpExit($xml->saveXML(), Response::TYPE_XML, 'application/xrd+xml');
	}
}
