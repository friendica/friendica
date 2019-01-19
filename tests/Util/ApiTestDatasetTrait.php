<?php

namespace Friendica\Test\Util;

/**
 * This trait contains the datasets for each ApiTest
 */
trait ApiTestDatasetTrait
{
	private $data = [
		'user1' => [
			'data' => [
				'uid' => 42,
				'cid' => 42,
				'username' => 'Test user',
				'nickname' => 'selfcontact',
				'verified' => 1,
				'password' => 'password',
				'theme' => 'frio',
				'mobile_theme' => 'frio',
				'page-flags' => [],
				'created' => '01-01-2019 10:10:10',
				'register_date' => '01-01-2019 10:10:10',
				'timezone' => 'Europe/Berlin',
				'location' => 'dfrn',
				'default-location' => 'dfrn',
				'name' => 'Test user',
				'about' => 'nothing',

				'micro' => 'https://micro/',
				'thumb' => 'https://thumn/',
				'photo' => 'https://myphoto/',

				'rel' => 0,

				'schema' => 'my awesome schema',
				'nav_bg' => 'black',
				'link_color' => 'white',
				'background_color' => 'green',

				'url' => 'https://moreurl/',

				'network' => 'dfrn',

				'self' => 1,
				'nick' => 'selfcontact',
			],
		],
	];

	public function dataApiUserFull()
	{
		return $this->data;
	}
}
