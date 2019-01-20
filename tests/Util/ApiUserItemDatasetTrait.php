<?php

namespace Friendica\Test\Util;

/**
 * This trait contains the datasets for user and item tests
 */
trait ApiUserItemDatasetTrait
{
	private $user = [
		'id' => 1,
		'uid' => 42,
		'cid' => 42,
		'username' => 'Test user',
		'nickname' => 'selfcontact',
		'nick' => 'selfcontact',
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

		'allow_cid' => '',
		'allow_gid' => '',
		'deny_cid' => '',
		'deny_gid' => '',

		'hidewall' => false,
	];

	public function dataApiUserItemFull()
	{
		return [
			'self' => [
				'user' => $this->user,
				'item' => [
					'id' => 1,
					'uid' => 42,
					'guid' => '123345-1234',
					'visible' => 1,
					'verb' => 'http://activitystrea.ms/schema/1.0/post',
					'unseen' => 1,
					'body' => 'Parent status',
					'title' => 'A title',
					'app' => 'drfn',
					'network' => 'drfn',
					'parent' => 1,
					'author-link' => 'http://localhost/profile/selfcontact',
					'wall' => 1,
					'starred' => 1,
					'origin' => 1,

					'allow_cid' => '',
					'allow_gid' => '',
					'deny_cid' => '',
					'deny_gid' => '',

					'created' => '01-01-2019 10:10:10',

					'thr-parent' => 1,
					'uri' => '',

					'contact-id' => 123,

					'author-id' => 123,
					'author-network' => 'drfn',
					'private' => 0,
				],
			],
		];
	}

	public function dataApiUserMediaFull()
	{
		return [
			'self' => [
				'user' => $this->user,
				'media' => [
					'id' => 666,
					'uid' => 44,
					'guid' => '123345-1234',
					'size' => 666,
					'width' => 666,
					'height' => 666,
					'tmp_name' => $this->getTempImage(),
					'author-link' => 'http://localhost/profile/selfcontact',
					'name' => 'spacer.png',
					'type' => 'image/png',
					'datasize' => 1234,

					'thr-parent' => 1,
					'uri' => '',

					'parent' => 666,

					'body' => 'Parent status',
					'title' => 'A title',
					'created' => '01-01-2019 10:10:10',
					'app' => 'drfn',
					'network' => 'drfn',
					'starred' => 1,
					'origin' => 1,
				],
			],
		];
	}
}
