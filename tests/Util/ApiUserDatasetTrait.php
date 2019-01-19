<?php

namespace Friendica\Test\Util;

/**
 * This trait contains the datasets for user interaction only (login, getUser, ..)
 */
trait ApiUserDatasetTrait
{
	public function dataApiUserFull()
	{
		return [
			'self' => [
				'data' => [
					'id' => 1,
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
	}

	public function dataApiOtherUserFull()
	{
		return [
			'other' => [
				'data' => [
					'uid' => 0,
					'cid' => 0,
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

					'self' => 0,
					'nick' => 'selfcontact',
				],
			],
		];
	}

	public function dataApiUser()
	{
		return [
			'self' => [
				'data' => [
					'uid' => 42,
					'return' => 42,
				],
				'auth' => [
					'allow_api' => true,
					'authenticated' => true,
				],
			],
			'friend' => [
				'data' => [
					'uid' => 43,
					'return' => 43,
				],
				'auth' => [
					'allow_api' => true,
					'authenticated' => true,
				],
			],
			'other' => [
				'data' => [
					'uid' => 44,
					'return' => false,
				],
				'auth' => [
					'allow_api' => true,
					'authenticated' => false,
				],
			],
			'wrong' => [
				'data' => [
					'uid' => null,
					'return' => false,
				],
				'auth' => [
					'allow_api' => false,
					'authenticated' => false,
				],
			]
		];
	}

	public function dataApiUserSearch()
	{
		return [
			'self' => [
				'data' => [
					'uid' => 42,
					'return' => 42,
				],
				'auth' => [
					'allow_api' => true,
					'authenticated' => true,
				],
			],
			'friend' => [
				'data' => [
					'uid' => 43,
					'return' => 43,
				],
				'auth' => [
					'allow_api' => true,
					'authenticated' => true,
				],
			],
			'other' => [
				'data' => [
					'uid' => 44,
					'return' => false,
				],
				'auth' => [
					'allow_api' => true,
					'authenticated' => false,
				],
			],
			'wrong' => [
				'data' => [
					'uid' => null,
					'return' => false,
				],
				'auth' => [
					'allow_api' => false,
					'authenticated' => false,
				],
			]
		];
	}
}
