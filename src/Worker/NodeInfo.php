<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\DI;
use Friendica\Model\Nodeinfo as ModelNodeInfo;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;

class NodeInfo
{
	public static function execute()
	{
		DI::logger()->info('start');
		ModelNodeInfo::update();
		// Now trying to register
		$url = 'http://the-federation.info/register/' . DI::baseUrl()->getHost();
		DI::logger()->debug('Check registering url', ['url' => $url]);
		$ret = DI::httpClient()->fetch($url, HttpClientAccept::HTML);
		DI::logger()->debug('Check registering answer', ['answer' => $ret]);
		DI::logger()->info('end');
	}
}
