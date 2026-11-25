<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

use Friendica\Core\Protocol;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Notification;

return [
	'gserver' => [
		[
			'url'              => 'https://friendica.local',
			'nurl'             => 'http://friendica.local',
			'register_policy'  => 0,
			'registered-users' => 0,
			'network'          => 'unkn',
		],
	],
	// Base test config to avoid notice messages
	'user' => [
		// Id prefix: 10
		[
			'uid'      => 1042, // selfcontact
			'username' => 'Self contact',
			'nickname' => 'selfcontact',
			'verified' => 1,
			'prvkey'   => "-----BEGIN RSA PRIVATE KEY-----\nMIICXgIBAAKBgQDVqxF9kIgtgRL0+q+jTi578FA1r1+crEmlYc0pdxcbmmrhjuRc\nrK1gX3r0mnP25fkHzG+6CAjgbDBRFM1/RXBCyp/KHVks7eQ4yr4MxTRlsxo5qf2o\nnbyNzM7Q+LZhFhe/yIoGN/fuEjlqBE98IfPOrUjsQPX240vGNXIkfLiAWwIDAQAB\nAoGBAIwuiPIdggqAtWQ+mD8HCx5LQwSFw6/xpPu5F7ZNqL52aAsGCbL3o2QoIG4c\na1qf9Ot16BNgNBqxQF3hzRTkBMrKYlmNTUkwJXun/zjQJq2JvOlcrSuXlIucUjs4\nXekVN25aYPHrX9m2FEIUwZTb4UYXbR80KbIDI53BkQ6EwSbpAkEA7aO49CR2Hf1Y\n1d2GaUI/Z0wvbj//+t0Kg0bPt16ca8KVjEQQA5ylsDaiw510jDz9NBQxSOk6If23\nUeRixc1RDQJBAOYtN4YnPM1Zfp6IxXlqMCc+xUWRTPEPFt+WpG+v79koNamAeA6o\nZzTl92hl58IqSdbgojeE2zXWQRvlimFMLQcCQQCV6jND0byyLqFcSeQBg0l8YROK\n+dUC7W80YfeoNod3c8nkMwvnO2tLPyxvO2XLEq6prBNra7bAus5rWyj0oBIBAkEA\n1EvUMFm0TLpEfLgtWuTD8Q6GKLnxO0ztjd+FXrXpBGN/ywyArxRHzJRmctW6wmz6\nmcOqGobhIHCysKYv0bnOtQJAc2M5RwlASHH4jGJzXgt3nboyiJfufM0RV9iry3ho\nCXQRWAONKoLqnsfC6qNP8OzY8FMJcwmPWj7Q/6z6yLBFTA==\n-----END RSA PRIVATE KEY-----",
			'pubkey'   => "-----BEGIN PUBLIC KEY-----\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDVqxF9kIgtgRL0+q+jTi578FA1\nr1+crEmlYc0pdxcbmmrhjuRcrK1gX3r0mnP25fkHzG+6CAjgbDBRFM1/RXBCyp/K\nHVks7eQ4yr4MxTRlsxo5qf2onbyNzM7Q+LZhFhe/yIoGN/fuEjlqBE98IfPOrUjs\nQPX240vGNXIkfLiAWwIDAQAB\n-----END PUBLIC KEY-----",			'password' => '$2y$10$DLRNTRmJgKe1cSrFJ5Jb0edCqvXlA9sh/RHdSnfxjbR.04yZRm4Qm',
			'theme'    => 'frio',
		],
	],
	'item-uri' => [
		// Id prefix: 20
		[
			'id'   => 20001, // 'https://friendica.local/objects/1'
			'uri'  => 'https://friendica.local/objects/1',
			'guid' => '1',
		],
		[
			'id'   => 20002, // 'https://friendica.local/objects/2'
			'uri'  => 'https://friendica.local/objects/2',
			'guid' => '2',
		],
		[
			'id'   => 20003, // 'https://friendica.local/objects/3'
			'uri'  => 'https://friendica.local/objects/3',
			'guid' => '3',
		],
		[
			'id'   => 20004, // 'https://friendica.local/objects/4'
			'uri'  => '4',
			'guid' => '4',
		],
		[
			'id'   => 20005, // 'https://friendica.local/objects/5'
			'uri'  => '5',
			'guid' => '5',
		],
		[
			'id'   => 20006, // 'https://friendica.local/objects/6'
			'uri'  => '6',
			'guid' => '6',
		],
		[
			'id'   => 20007, // 'https://friendica.local/objects/7'
			'uri'  => '7',
			'guid' => '7',
		],
		[
			'id'   => 20042, // 'https://friendica.local/profile/selfcontact'
			'uri'  => 'https://friendica.local/profile/selfcontact',
			'guid' => '42',
		],
		[
			'id'   => 20043, // 'https://friendica.local/profile/othercontact'
			'uri'  => 'https://friendica.local/profile/othercontact',
			'guid' => '43',
		],
		[
			'id'   => 20044, // 'https://friendica.local/profile/friendcontact'
			'uri'  => 'https://friendica.local/profile/friendcontact',
			'guid' => '44',
		],
		[
			'id'   => 20046, // 'https://friendica.local/profile/mutualcontact'
			'uri'  => 'https://friendica.local/profile/mutualcontact',
			'guid' => '46',
		],
		[
			'id'   => 20049, // 'https://domain.tld/profile/remotecontact'
			'uri'  => 'https://domain.tld/profile/remotecontact',
			'guid' => '49',
		],
		[
			'id'   => 20100, // 'https://friendica.local/objects/100'
			'uri'  => 'https://friendica.local/objects/100',
			'guid' => '100',
		],
	],
	'contact' => [
		// Id prefix: 30
		[
			'id'       => 3042, // selfcontact (selfcontact)
			'uid'      => 1042, // selfcontact
			'uri-id'   => 20042, // 'https://friendica.local/profile/selfcontact'
			'name'     => 'Self contact',
			'nick'     => 'selfcontact',
			'self'     => 1,
			'nurl'     => 'http://friendica.local/profile/selfcontact',
			'url'      => 'https://friendica.local/profile/selfcontact',
			'notify'   => 'https://friendica.local/friendica/inbox',
			'addr'     => 'selfcontact@friendica.local',
			'about'    => 'User used in tests',
			'prvkey'   => "-----BEGIN RSA PRIVATE KEY-----\nMIICXgIBAAKBgQDVqxF9kIgtgRL0+q+jTi578FA1r1+crEmlYc0pdxcbmmrhjuRc\nrK1gX3r0mnP25fkHzG+6CAjgbDBRFM1/RXBCyp/KHVks7eQ4yr4MxTRlsxo5qf2o\nnbyNzM7Q+LZhFhe/yIoGN/fuEjlqBE98IfPOrUjsQPX240vGNXIkfLiAWwIDAQAB\nAoGBAIwuiPIdggqAtWQ+mD8HCx5LQwSFw6/xpPu5F7ZNqL52aAsGCbL3o2QoIG4c\na1qf9Ot16BNgNBqxQF3hzRTkBMrKYlmNTUkwJXun/zjQJq2JvOlcrSuXlIucUjs4\nXekVN25aYPHrX9m2FEIUwZTb4UYXbR80KbIDI53BkQ6EwSbpAkEA7aO49CR2Hf1Y\n1d2GaUI/Z0wvbj//+t0Kg0bPt16ca8KVjEQQA5ylsDaiw510jDz9NBQxSOk6If23\nUeRixc1RDQJBAOYtN4YnPM1Zfp6IxXlqMCc+xUWRTPEPFt+WpG+v79koNamAeA6o\nZzTl92hl58IqSdbgojeE2zXWQRvlimFMLQcCQQCV6jND0byyLqFcSeQBg0l8YROK\n+dUC7W80YfeoNod3c8nkMwvnO2tLPyxvO2XLEq6prBNra7bAus5rWyj0oBIBAkEA\n1EvUMFm0TLpEfLgtWuTD8Q6GKLnxO0ztjd+FXrXpBGN/ywyArxRHzJRmctW6wmz6\nmcOqGobhIHCysKYv0bnOtQJAc2M5RwlASHH4jGJzXgt3nboyiJfufM0RV9iry3ho\nCXQRWAONKoLqnsfC6qNP8OzY8FMJcwmPWj7Q/6z6yLBFTA==\n-----END RSA PRIVATE KEY-----",
			'pubkey'   => "-----BEGIN PUBLIC KEY-----\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDVqxF9kIgtgRL0+q+jTi578FA1\nr1+crEmlYc0pdxcbmmrhjuRcrK1gX3r0mnP25fkHzG+6CAjgbDBRFM1/RXBCyp/K\nHVks7eQ4yr4MxTRlsxo5qf2onbyNzM7Q+LZhFhe/yIoGN/fuEjlqBE98IfPOrUjs\nQPX240vGNXIkfLiAWwIDAQAB\n-----END PUBLIC KEY-----",
			'pending'  => 0,
			'blocked'  => 0,
			'rel'      => Contact::FOLLOWER,
			'network'  => Protocol::DFRN,
			'location' => 'DFRN',
			'baseurl'  => 'https://friendica.local',
		],
		// Having the same name and nick allows us to test
		// the fallback to api_get_nick() in api_get_user()
		[
			'id'       => 3043, // othercontact (public)
			'uid'      => 0,
			'uri-id'   => 20043, // 'https://friendica.local/profile/othercontact'
			'name'     => 'othercontact',
			'nick'     => 'othercontact',
			'self'     => 0,
			'nurl'     => 'http://friendica.local/profile/othercontact',
			'url'      => 'https://friendica.local/profile/othercontact',
			'notify'   => 'https://friendica.local/friendica/inbox',
			'addr'     => 'othercontact@friendica.local',
			'pending'  => 0,
			'blocked'  => 0,
			'rel'      => Contact::NOTHING,
			'network'  => Protocol::DFRN,
			'location' => 'DFRN',
		],
		[
			'id'       => 3044, // friendcontact (selfcontact)
			'uid'      => 1042, // selfcontact
			'uri-id'   => 20044, // 'https://friendica.local/profile/friendcontact'
			'name'     => 'Friend contact',
			'nick'     => 'friendcontact',
			'self'     => 0,
			'nurl'     => 'http://friendica.local/profile/friendcontact',
			'url'      => 'https://friendica.local/profile/friendcontact',
			'notify'   => 'https://friendica.local/friendica/inbox',
			'addr'     => 'friendcontact@friendica.local',
			'pending'  => 0,
			'blocked'  => 0,
			'rel'      => Contact::SHARING,
			'network'  => Protocol::DFRN,
			'location' => 'DFRN',
		],
		[
			'id'       => 3045, // friendcontact (public)
			'uid'      => 0,
			'uri-id'   => 20044, // 'https://friendica.local/profile/friendcontact'
			'name'     => 'Friend contact',
			'nick'     => 'friendcontact',
			'self'     => 0,
			'nurl'     => 'http://friendica.local/profile/friendcontact',
			'url'      => 'https://friendica.local/profile/friendcontact',
			'notify'   => 'https://friendica.local/friendica/inbox',
			'addr'     => 'friendcontact@friendica.local',
			'pending'  => 0,
			'blocked'  => 0,
			'rel'      => Contact::SHARING,
			'network'  => Protocol::DFRN,
			'location' => 'DFRN',
		],
		[
			'id'       => 3046, // mutualcontact (selfcontact)
			'uid'      => 1042, // selfcontact
			'uri-id'   => 20046, // 'https://friendica.local/profile/mutualcontact'
			'name'     => 'Mutual contact',
			'nick'     => 'mutualcontact',
			'self'     => 0,
			'nurl'     => 'http://friendica.local/profile/mutualcontact',
			'url'      => 'https://friendica.local/profile/mutualcontact',
			'notify'   => 'https://friendica.local/friendica/inbox',
			'addr'     => 'mutualcontact@friendica.local',
			'pending'  => 0,
			'blocked'  => 0,
			'rel'      => Contact::FRIEND,
			'network'  => Protocol::DFRN,
			'location' => 'DFRN',
		],
		[
			'id'       => 3047, // mutualcontact (public)
			'uid'      => 0,
			'uri-id'   => 20046, // 'https://friendica.local/profile/mutualcontact'
			'name'     => 'Mutual contact',
			'nick'     => 'mutualcontact',
			'self'     => 0,
			'nurl'     => 'http://friendica.local/profile/mutualcontact',
			'url'      => 'https://friendica.local/profile/mutualcontact',
			'notify'   => 'https://friendica.local/friendica/inbox',
			'addr'     => 'mutualcontact@friendica.local',
			'pending'  => 0,
			'blocked'  => 0,
			'rel'      => Contact::SHARING,
			'network'  => Protocol::DFRN,
			'location' => 'DFRN',
		],
		[
			'id'       => 3048, // selfcontact (public)
			'uid'      => 0,
			'uri-id'   => 20042, // 'https://friendica.local/profile/selfcontact'
			'name'     => 'Self contact',
			'nick'     => 'selfcontact',
			'self'     => 0,
			'nurl'     => 'http://friendica.local/profile/selfcontact',
			'url'      => 'https://friendica.local/profile/selfcontact',
			'notify'   => 'https://friendica.local/friendica/inbox',
			'addr'     => 'selfcontact@friendica.local',
			'about'    => 'User used in tests',
			'pending'  => 0,
			'blocked'  => 0,
			'rel'      => Contact::FOLLOWER,
			'network'  => Protocol::DFRN,
			'location' => 'DFRN',
		],
		[
			'id'       => 3049, // remotecontact (public)
			'uid'      => 0,
			'uri-id'   => 20049, // 'https://domain.tld/profile/remotecontact'
			'name'     => 'Remote user',
			'nick'     => 'remotecontact',
			'self'     => 0,
			'nurl'     => 'http://domain.tld/profile/remotecontact',
			'url'      => 'https://domain.tld/profile/remotecontact',
			'alias'    => 'https://domain.tld/~remotecontact',
			'addr'     => 'remotecontact@domain.tld',
			'about'    => 'User used in tests',
			'pending'  => 0,
			'blocked'  => 0,
			'rel'      => Contact::FOLLOWER,
			'network'  => Protocol::ACTIVITYPUB,
			'location' => 'AP',
		],
	],
	'apcontact' => [
		[
			'url'              => 'https://friendica.local/profile/selfcontact',
			'uri-id'           => 20042, // 'https://friendica.local/profile/selfcontact'
			'uuid'             => '42',
			'type'             => 'Person',
			'following'        => 'https://friendica.local/following/selfcontact',
			'followers'        => 'https://friendica.local/followers/selfcontact',
			'inbox'            => 'https://friendica.local/inbox/selfcontact',
			'outbox'           => 'https://friendica.local/outbox/selfcontact',
			'sharedinbox'      => 'https://friendica.local/inbox',
			'manually-approve' => 1,
			'discoverable'     => 0,
			'nick'             => 'selfcontact',
			'name'             => 'Self contact',
			'about'            => 'User used in tests',
			'xmpp'             => null,
			'matrix'           => null,
			'photo'            => 'https://friendica.local/photo/profile/admin.jpeg',
			'header'           => null,
			'addr'             => 'selfcontact@localhost',
			'alias'            => null,
			'pubkey'           => "-----BEGIN PUBLIC KEY-----\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDVqxF9kIgtgRL0+q+jTi578FA1\nr1+crEmlYc0pdxcbmmrhjuRcrK1gX3r0mnP25fkHzG+6CAjgbDBRFM1/RXBCyp/K\nHVks7eQ4yr4MxTRlsxo5qf2onbyNzM7Q+LZhFhe/yIoGN/fuEjlqBE98IfPOrUjs\nQPX240vGNXIkfLiAWwIDAQAB\n-----END PUBLIC KEY-----",
			'subscribe'        => '/follow?url={uri}',
			'baseurl'          => null,
			'gsid'             => null,
			'generator'        => "Friendica 'Siberian Iris' 2021.12-dev-1443",
			'following_count'  => 0,
			'followers_count'  => 0,
			'statuses_count'   => 0,
			'updated'          => '2021-11-19 19:17:59',
		],
	],
	'verb' => [
		// Id prefix: 40
		[
			'id'   => 400,
			'name' => '',
		],
		[
			'id'   => 401,
			'name' => 'http://activitystrea.ms/schema/1.0/like',
		],
		[
			'id'   => 402,
			'name' => 'http://purl.org/macgirvin/dfrn/1.0/dislike',
		],
		[
			'id'   => 403,
			'name' => 'http://purl.org/zot/activity/attendyes',
		],
		[
			'id'   => 404,
			'name' => 'http://purl.org/zot/activity/attendno',
		],
		[
			'id'   => 405,
			'name' => 'http://purl.org/zot/activity/attendmaybe',
		],
		[
			'id'   => 406,
			'name' => 'http://activitystrea.ms/schema/1.0/follow',
		],
		[
			'id'   => 407,
			'name' => 'https://www.w3.org/ns/activitystreams#Announce',
		],
		[
			'id'   => 408,
			'name' => 'http://activitystrea.ms/schema/1.0/post',
		],
	],
	'post-content' => [
		[
			'uri-id' => 20001, // 'https://friendica.local/objects/1'
			'body'   => 'Parent status',
			'plink'  => 'https://friendica.local/display/1',
		],
		[
			'uri-id' => 20002, // 'https://friendica.local/objects/2'
			'body'   => 'Reply',
			'plink'  => 'https://friendica.local/display/2',
		],
		[
			'uri-id' => 20003, // 'https://friendica.local/objects/3'
			'body'   => 'Other user status',
			'plink'  => 'https://friendica.local/display/3',
		],
		[
			'uri-id' => 20004, // 'https://friendica.local/objects/4'
			'body'   => 'Friend user reply',
			'plink'  => 'https://friendica.local/display/4',
		],
		[
			'uri-id' => 20005, // 'https://friendica.local/objects/5'
			'body'   => '[share]Shared status[/share]',
			'plink'  => 'https://friendica.local/display/5',
		],
		[
			'uri-id' => 20006, // 'https://friendica.local/objects/6'
			'body'   => 'Friend user status',
			'plink'  => 'https://friendica.local/display/6',
		],
		[
			'uri-id' => 20007, // 'https://friendica.local/objects/7'
			'title'  => 'item_title',
			'body'   => 'perspiciatis impedit voluptatem quis molestiae ea qui '
						. 'reiciendis dolorum aut ducimus sunt consequatur inventore dolor '
						. 'officiis pariatur doloremque nemo culpa aut quidem qui dolore '
						. 'laudantium atque commodi alias voluptatem non possimus aperiam '
						. 'ipsum rerum consequuntur aut amet fugit quia aliquid praesentium '
						. 'repellendus quibusdam et et inventore mollitia rerum sit autem '
						. 'pariatur maiores ipsum accusantium perferendis vel sit possimus '
						. 'veritatis nihil distinctio qui eum repellat officia illum quos '
						. 'impedit quam iste esse unde qui suscipit aut facilis ut inventore '
						. 'omnis exercitationem quo magnam consequatur maxime aut illum '
						. 'soluta quaerat natus unde aspernatur et sed beatae nihil ullam '
						. 'temporibus corporis ratione blanditiis perspiciatis impedit '
						. 'voluptatem quis molestiae ea qui reiciendis dolorum aut ducimus '
						. 'sunt consequatur inventore dolor officiis pariatur doloremque '
						. 'nemo culpa aut quidem qui dolore laudantium atque commodi alias '
						. 'voluptatem non possimus aperiam ipsum rerum consequuntur aut '
						. 'amet fugit quia aliquid praesentium repellendus quibusdam et et '
						. 'inventore mollitia rerum sit autem pariatur maiores ipsum accusantium '
						. 'perferendis vel sit possimus veritatis nihil distinctio qui eum '
						. 'repellat officia illum quos impedit quam iste esse unde qui '
						. 'suscipit aut facilis ut inventore omnis exercitationem quo magnam '
						. 'consequatur maxime aut illum soluta quaerat natus unde aspernatur '
						. 'et sed beatae nihil ullam temporibus corporis ratione blanditiis',
			'plink' => 'https://friendica.local/display/6',
		],
		[
			'uri-id' => 20100, // 'https://friendica.local/objects/100'
			'title'  => 'item_title',
			'body'   => ':like ~friendica no [code]:dislike[/code] :-p :-[ :hugging face: <3 :smiley heart 333: ⽕',
			'plink'  => 'https://friendica.local/display/100',
		],
	],
	'post' => [
		[
			'uri-id'        => 20001, // 'https://friendica.local/objects/1'
			'parent-uri-id' => 20001, // 'https://friendica.local/objects/1'
			'thr-parent-id' => 20001, // 'https://friendica.local/objects/1'
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 3048, // selfcontact (public)
			'author-id'     => 3048, // selfcontact (public)
			'causer-id'     => 3048, // selfcontact (public)
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
		],
		[
			'uri-id'        => 20002, // 'https://friendica.local/objects/2'
			'parent-uri-id' => 20001, // 'https://friendica.local/objects/1'
			'thr-parent-id' => 20001, // 'https://friendica.local/objects/1'
			'gravity'       => Item::GRAVITY_COMMENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 3043, // othercontact (public)
			'author-id'     => 3043, // othercontact (public)
			'causer-id'     => 3043, // othercontact (public)
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
		],
		[
			'uri-id'        => 20003, // 'https://friendica.local/objects/3'
			'parent-uri-id' => 20003, // 'https://friendica.local/objects/3'
			'thr-parent-id' => 20003, // 'https://friendica.local/objects/3'
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 3043, // othercontact (public)
			'author-id'     => 3043, // othercontact (public)
			'causer-id'     => 3043, // othercontact (public)
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
		],
		[
			'uri-id'        => 20004, // 'https://friendica.local/objects/4'
			'parent-uri-id' => 20001, // 'https://friendica.local/objects/1'
			'thr-parent-id' => 20001, // 'https://friendica.local/objects/1'
			'gravity'       => Item::GRAVITY_COMMENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 3043, // othercontact (public)
			'author-id'     => 3045, // friendcontact (public)
			'causer-id'     => 3045, // friendcontact (public)
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
		],
		[
			'uri-id'        => 20005, // 'https://friendica.local/objects/5'
			'parent-uri-id' => 20001, // 'https://friendica.local/objects/1'
			'thr-parent-id' => 20001, // 'https://friendica.local/objects/1'
			'gravity'       => Item::GRAVITY_COMMENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 3043, // othercontact (public)
			'author-id'     => 3043, // othercontact (public)
			'causer-id'     => 3043, // othercontact (public)
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
		],
		[
			'uri-id'        => 20006, // 'https://friendica.local/objects/6'
			'parent-uri-id' => 20006, // 'https://friendica.local/objects/6'
			'thr-parent-id' => 20006, // 'https://friendica.local/objects/6'
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 3043, // othercontact (public)
			'author-id'     => 3045, // friendcontact (public)
			'causer-id'     => 3045, // friendcontact (public)
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
		],
		[
			'uri-id'        => 20007, // 'https://friendica.local/objects/7'
			'parent-uri-id' => 20007, // 'https://friendica.local/objects/7'
			'thr-parent-id' => 20007, // 'https://friendica.local/objects/7'
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 3043, // othercontact (public)
			'author-id'     => 3045, // friendcontact (public)
			'causer-id'     => 3045, // friendcontact (public)
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
		],
	],
	'post-user' => [
		// Id prefix: 15
		[
			'id'            => 1501,
			'uri-id'        => 20001, // 'https://friendica.local/objects/1'
			'visible'       => 1,
			'contact-id'    => 3048, // selfcontact (public)
			'author-id'     => 3048, // selfcontact (public)
			'owner-id'      => 3048, // selfcontact (public)
			'causer-id'     => 3048, // selfcontact (public)
			'uid'           => 1042, // selfcontact
			'vid'           => 408,
			'unseen'        => 1,
			'parent-uri-id' => 20001, // 'https://friendica.local/objects/1'
			'thr-parent-id' => 20001, // 'https://friendica.local/objects/1'
			'private'       => Item::PUBLIC,
			'global'        => true,
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'wall'          => 1,
			'origin'        => 1,
		],
		[
			'id'            => 1502,
			'uri-id'        => 20002, // 'https://friendica.local/objects/2'
			'uid'           => 1042, // selfcontact
			'contact-id'    => 3043, // othercontact (public)
			'unseen'        => 0,
			'origin'        => 1,
			'parent-uri-id' => 20001, // 'https://friendica.local/objects/1'
			'thr-parent-id' => 20001, // 'https://friendica.local/objects/1'
			'gravity'       => Item::GRAVITY_COMMENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 3043, // othercontact (public)
			'author-id'     => 3043, // othercontact (public)
			'causer-id'     => 3043, // othercontact (public)
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
			'wall'          => 1,
		],
		[
			'id'            => 1503,
			'uri-id'        => 20003, // 'https://friendica.local/objects/3'
			'uid'           => 1042, // selfcontact
			'contact-id'    => 3043, // othercontact (public)
			'unseen'        => 0,
			'origin'        => 1,
			'parent-uri-id' => 20003, // 'https://friendica.local/objects/3'
			'thr-parent-id' => 20003, // 'https://friendica.local/objects/3'
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 3043, // othercontact (public)
			'author-id'     => 3043, // othercontact (public)
			'causer-id'     => 3043, // othercontact (public)
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
			'wall'          => 1,
		],
		[
			'id'            => 1504,
			'uri-id'        => 20004, // 'https://friendica.local/objects/4'
			'uid'           => 1042, // selfcontact
			'contact-id'    => 3045, // friendcontact (public)
			'unseen'        => 0,
			'origin'        => 1,
			'parent-uri-id' => 20001, // 'https://friendica.local/objects/1'
			'thr-parent-id' => 20001, // 'https://friendica.local/objects/1'
			'gravity'       => Item::GRAVITY_COMMENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 3043, // othercontact (public)
			'author-id'     => 3045, // friendcontact (public)
			'causer-id'     => 3045, // friendcontact (public)
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
			'wall'          => 1,
		],
		[
			'id'            => 1505,
			'uri-id'        => 20005, // 'https://friendica.local/objects/5'
			'uid'           => 1042, // selfcontact
			'contact-id'    => 3043, // othercontact (public)
			'unseen'        => 0,
			'origin'        => 1,
			'parent-uri-id' => 20001, // 'https://friendica.local/objects/1'
			'thr-parent-id' => 20001, // 'https://friendica.local/objects/1'
			'gravity'       => Item::GRAVITY_COMMENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 3043, // othercontact (public)
			'author-id'     => 3043, // othercontact (public)
			'causer-id'     => 3043, // othercontact (public)
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
			'wall'          => 1,
		],
		[
			'id'            => 1506,
			'uri-id'        => 20006, // 'https://friendica.local/objects/6'
			'uid'           => 1042, // selfcontact
			'contact-id'    => 3045, // friendcontact (public)
			'unseen'        => 0,
			'origin'        => 1,
			'parent-uri-id' => 20006, // 'https://friendica.local/objects/6'
			'thr-parent-id' => 20006, // 'https://friendica.local/objects/6'
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 3043, // othercontact (public)
			'author-id'     => 3045, // friendcontact (public)
			'causer-id'     => 3045, // friendcontact (public)
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
			'wall'          => 1,
		],
		[
			'id'            => 1507,
			'uri-id'        => 20001, // 'https://friendica.local/objects/1'
			'uid'           => 0,
			'contact-id'    => 3043, // othercontact (public)
			'unseen'        => 1,
			'origin'        => 0,
			'parent-uri-id' => 20001, // 'https://friendica.local/objects/1'
			'thr-parent-id' => 20001, // 'https://friendica.local/objects/1'
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 3043, // othercontact (public)
			'author-id'     => 3043, // othercontact (public)
			'causer-id'     => 3043, // othercontact (public)
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
			'wall'          => 0,
		],
		[
			'id'            => 1508,
			'uri-id'        => 20002, // 'https://friendica.local/objects/2'
			'uid'           => 0,
			'contact-id'    => 3043, // othercontact (public)
			'unseen'        => 0,
			'origin'        => 0,
			'parent-uri-id' => 20001, // 'https://friendica.local/objects/1'
			'thr-parent-id' => 20001, // 'https://friendica.local/objects/1'
			'gravity'       => Item::GRAVITY_COMMENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 3043, // othercontact (public)
			'author-id'     => 3043, // othercontact (public)
			'causer-id'     => 3043, // othercontact (public)
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
			'wall'          => 0,
		],
		[
			'id'            => 1509,
			'uri-id'        => 20003, // 'https://friendica.local/objects/3'
			'uid'           => 0,
			'contact-id'    => 3043, // othercontact (public)
			'unseen'        => 0,
			'origin'        => 0,
			'parent-uri-id' => 20003, // 'https://friendica.local/objects/3'
			'thr-parent-id' => 20003, // 'https://friendica.local/objects/3'
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 3043, // othercontact (public)
			'author-id'     => 3043, // othercontact (public)
			'causer-id'     => 3043, // othercontact (public)
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
			'wall'          => 0,
		],
		[
			'id'            => 1510,
			'uri-id'        => 20004, // 'https://friendica.local/objects/4'
			'uid'           => 0,
			'contact-id'    => 3045, // friendcontact (public)
			'unseen'        => 0,
			'origin'        => 0,
			'parent-uri-id' => 20001, // 'https://friendica.local/objects/1'
			'thr-parent-id' => 20001, // 'https://friendica.local/objects/1'
			'gravity'       => Item::GRAVITY_COMMENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 3043, // othercontact (public)
			'author-id'     => 3045, // friendcontact (public)
			'causer-id'     => 3045, // friendcontact (public)
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
			'wall'          => 0,
		],
		[
			'id'            => 1511,
			'uri-id'        => 20005, // 'https://friendica.local/objects/5'
			'uid'           => 0,
			'contact-id'    => 3043, // othercontact (public)
			'unseen'        => 0,
			'origin'        => 0,
			'parent-uri-id' => 20001, // 'https://friendica.local/objects/1'
			'thr-parent-id' => 20001, // 'https://friendica.local/objects/1'
			'gravity'       => Item::GRAVITY_COMMENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 3043, // othercontact (public)
			'author-id'     => 3043, // othercontact (public)
			'causer-id'     => 3043, // othercontact (public)
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
			'wall'          => 0,
		],
		[
			'id'            => 1512,
			'uri-id'        => 20006, // 'https://friendica.local/objects/6'
			'visible'       => 1,
			'contact-id'    => 3045, // friendcontact (public)
			'author-id'     => 3045, // friendcontact (public)
			'owner-id'      => 3043, // othercontact (public)
			'causer-id'     => 3045, // friendcontact (public)
			'uid'           => 0,
			'vid'           => 408,
			'unseen'        => 0,
			'parent-uri-id' => 20006, // 'https://friendica.local/objects/6'
			'thr-parent-id' => 20006, // 'https://friendica.local/objects/6'
			'private'       => Item::PUBLIC,
			'global'        => true,
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'origin'        => 0,
			'deleted'       => 0,
			'wall'          => 0,
		],
		[
			'id'            => 1513,
			'uri-id'        => 20007, // 'https://friendica.local/objects/7'
			'visible'       => 1,
			'contact-id'    => 3045, // friendcontact (public)
			'author-id'     => 3045, // friendcontact (public)
			'owner-id'      => 3043, // othercontact (public)
			'causer-id'     => 3045, // friendcontact (public)
			'uid'           => 0,
			'vid'           => 408,
			'unseen'        => 0,
			'parent-uri-id' => 20007, // 'https://friendica.local/objects/7'
			'thr-parent-id' => 20007, // 'https://friendica.local/objects/7'
			'private'       => Item::PUBLIC,
			'global'        => true,
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'origin'        => 0,
			'deleted'       => 0,
			'wall'          => 0,
		],
		// An emoji post
		[
			'id'            => 1514,
			'uri-id'        => 20100, // 'https://friendica.local/objects/100'
			'visible'       => 1,
			'contact-id'    => 3045, // friendcontact (public)
			'author-id'     => 3045, // friendcontact (public)
			'owner-id'      => 3043, // othercontact (public)
			'causer-id'     => 3045, // friendcontact (public)
			'uid'           => 0,
			'vid'           => 408,
			'unseen'        => 0,
			'parent-uri-id' => 20007, // 'https://friendica.local/objects/7'
			'thr-parent-id' => 20007, // 'https://friendica.local/objects/7'
			'private'       => Item::PUBLIC,
			'global'        => true,
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'origin'        => 0,
			'deleted'       => 0,
			'wall'          => 0,
		],
	],
	'post-thread' => [
		[
			'uri-id'    => 20001, // 'https://friendica.local/objects/1'
			'author-id' => 3048, // selfcontact (public)
			'owner-id'  => 3048, // selfcontact (public)
			'causer-id' => 3048, // selfcontact (public)
			'network'   => Protocol::DFRN,
		],
		[
			'uri-id'    => 20003, // 'https://friendica.local/objects/3'
			'author-id' => 3043, // othercontact (public)
			'owner-id'  => 3043, // othercontact (public)
			'causer-id' => 3043, // othercontact (public)
			'network'   => Protocol::DFRN,
		],
		[
			'uri-id'    => 20006, // 'https://friendica.local/objects/6'
			'author-id' => 3045, // friendcontact (public)
			'owner-id'  => 3045, // friendcontact (public)
			'causer-id' => 3045, // friendcontact (public)
			'network'   => Protocol::DFRN,
		],
		[
			'uri-id'    => 20007, // 'https://friendica.local/objects/7'
			'author-id' => 3045, // friendcontact (public)
			'owner-id'  => 3045, // friendcontact (public)
			'causer-id' => 3045, // friendcontact (public)
			'network'   => Protocol::DFRN,
		],
	],
	'post-thread-user' => [
		[
			'uri-id'       => 20001, // 'https://friendica.local/objects/1'
			'uid'          => 1042, // selfcontact
			'wall'         => 1,
			'post-user-id' => 1501,
			'author-id'    => 3048, // selfcontact (public)
			'owner-id'     => 3048, // selfcontact (public)
			'causer-id'    => 3048, // selfcontact (public)
			'contact-id'   => 3048, // selfcontact (public)
			'network'      => Protocol::DFRN,
			'starred'      => 1,
			'origin'       => 1,
		],
		[
			'uri-id'       => 20003, // 'https://friendica.local/objects/3'
			'uid'          => 1042, // selfcontact
			'wall'         => 1,
			'post-user-id' => 1503,
			'author-id'    => 3043, // othercontact (public)
			'owner-id'     => 3043, // othercontact (public)
			'causer-id'    => 3043, // othercontact (public)
			'contact-id'   => 3043, // othercontact (public)
			'network'      => Protocol::DFRN,
			'starred'      => 0,
			'origin'       => 1,
		],
		[
			'uri-id'       => 20006, // 'https://friendica.local/objects/6'
			'uid'          => 1042, // selfcontact
			'wall'         => 1,
			'post-user-id' => 1506,
			'author-id'    => 3045, // friendcontact (public)
			'owner-id'     => 3045, // friendcontact (public)
			'causer-id'    => 3045, // friendcontact (public)
			'contact-id'   => 3045, // friendcontact (public)
			'network'      => Protocol::DFRN,
			'starred'      => 0,
			'origin'       => 1,
		],
		[
			'uri-id'       => 20001, // 'https://friendica.local/objects/1'
			'uid'          => 0,
			'wall'         => 0,
			'post-user-id' => 1507,
			'author-id'    => 3043, // othercontact (public)
			'owner-id'     => 3043, // othercontact (public)
			'causer-id'    => 3043, // othercontact (public)
			'contact-id'   => 3043, // othercontact (public)
			'network'      => Protocol::DFRN,
			'starred'      => 0,
			'origin'       => 0,
		],
		[
			'uri-id'       => 20003, // 'https://friendica.local/objects/3'
			'uid'          => 0,
			'wall'         => 0,
			'post-user-id' => 1509,
			'author-id'    => 3043, // othercontact (public)
			'owner-id'     => 3043, // othercontact (public)
			'causer-id'    => 3043, // othercontact (public)
			'contact-id'   => 3043, // othercontact (public)
			'network'      => Protocol::DFRN,
			'starred'      => 0,
			'origin'       => 0,
		],
		[
			'uri-id'       => 20006, // 'https://friendica.local/objects/6'
			'uid'          => 0,
			'wall'         => 0,
			'post-user-id' => 1512,
			'author-id'    => 3045, // friendcontact (public)
			'owner-id'     => 3045, // friendcontact (public)
			'causer-id'    => 3045, // friendcontact (public)
			'contact-id'   => 3045, // friendcontact (public)
			'network'      => Protocol::DFRN,
			'starred'      => 0,
			'origin'       => 0,
		],
		[
			'uri-id'       => 20007, // 'https://friendica.local/objects/7'
			'uid'          => 1042, // selfcontact
			'wall'         => 1,
			'post-user-id' => 1507,
			'author-id'    => 3045, // friendcontact (public)
			'owner-id'     => 3045, // friendcontact (public)
			'causer-id'    => 3045, // friendcontact (public)
			'contact-id'   => 3045, // friendcontact (public)
			'network'      => Protocol::DFRN,
			'starred'      => 0,
			'origin'       => 1,
		],
		[
			'uri-id'       => 20007, // 'https://friendica.local/objects/7'
			'uid'          => 0,
			'wall'         => 0,
			'post-user-id' => 1512,
			'author-id'    => 3045, // friendcontact (public)
			'owner-id'     => 3045, // friendcontact (public)
			'causer-id'    => 3045, // friendcontact (public)
			'contact-id'   => 3045, // friendcontact (public)
			'network'      => Protocol::DFRN,
			'starred'      => 0,
			'origin'       => 0,
		],
	],
	'post-origin' => [
		[
			'id'            => 1501,
			'uri-id'        => 20001,
			'uid'           => 1042,
			'parent-uri-id' => 20001,
			'thr-parent-id' => 20001,
			'created'       => '2020-01-01 12:00:00',
			'received'      => '2020-01-01 12:00:00',
			'gravity'       => Item::GRAVITY_PARENT,
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'wall'          => 1,
		],
		[
			'id'            => 1506,
			'uri-id'        => 20006,
			'uid'           => 1042,
			'parent-uri-id' => 20006,
			'thr-parent-id' => 20006,
			'created'       => '2020-01-01 12:00:00',
			'received'      => '2020-01-01 12:00:00',
			'gravity'       => Item::GRAVITY_PARENT,
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'wall'          => 1,
		],
		[
			'id'            => 1502,
			'uri-id'        => 20002,
			'uid'           => 1042,
			'parent-uri-id' => 20001,
			'thr-parent-id' => 20001,
			'created'       => '2020-01-01 12:00:00',
			'received'      => '2020-01-01 12:00:00',
			'gravity'       => Item::GRAVITY_COMMENT,
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'wall'          => 1,
		],
		[
			'id'            => 1503,
			'uri-id'        => 20003,
			'uid'           => 1042,
			'parent-uri-id' => 20003,
			'thr-parent-id' => 20003,
			'created'       => '2020-01-01 12:00:00',
			'received'      => '2020-01-01 12:00:00',
			'gravity'       => Item::GRAVITY_PARENT,
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'wall'          => 1,
		],
		[
			'id'            => 1504,
			'uri-id'        => 20004,
			'uid'           => 1042,
			'parent-uri-id' => 20001,
			'thr-parent-id' => 20001,
			'created'       => '2020-01-01 12:00:00',
			'received'      => '2020-01-01 12:00:00',
			'gravity'       => Item::GRAVITY_COMMENT,
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'wall'          => 1,
		],
		[
			'id'            => 1505,
			'uri-id'        => 20005,
			'uid'           => 1042,
			'parent-uri-id' => 20001,
			'thr-parent-id' => 20001,
			'created'       => '2020-01-01 12:00:00',
			'received'      => '2020-01-01 12:00:00',
			'gravity'       => Item::GRAVITY_COMMENT,
			'vid'           => 408,
			'private'       => Item::PUBLIC,
			'wall'          => 1,
		],
	],
	'notify' => [
		// Id prefix: 50
		[
			'id'         => 501,
			'type'       => 8,
			'name'       => 'Friend contact',
			'url'        => 'https://friendica.local/profile/friendcontact',
			'photo'      => 'https://friendica.local/',
			'date'       => '2020-01-01 12:12:02',
			'msg'        => 'A test reply from an item',
			'uid'        => 1042, // selfcontact
			'link'       => 'https://friendica.local/display/1',
			'iid'        => 1504,
			'seen'       => 0,
			'verb'       => \Friendica\Protocol\Activity::POST,
			'otype'      => Notification\ObjectType::ITEM,
			'name_cache' => 'Friend contact',
			'msg_cache'  => 'A test reply from an item',
		],
	],
	'profile' => [
		// Id prefix: 60
		[
			'id'       => 601,
			'uid'      => 1042, // selfcontact
			'locality' => 'DFRN',
		],
	],
	'group' => [
		// Id prefix: 70
		[
			'id'      => 701,
			'uid'     => 1042, // selfcontact
			'visible' => 1,
			'name'    => 'Visible list',
		],
		[
			'id'      => 702,
			'uid'     => 1042, // selfcontact
			'visible' => 0,
			'name'    => 'Private list',
		],
	],
	'group_member' => [
		// Id prefix: 80
		[
			'id'         => 801,
			'gid'        => 701,
			'contact-id' => 3043, // othercontact (public)
		],
		[
			'id'         => 802,
			'gid'        => 701,
			'contact-id' => 3043, // othercontact (public)
		],
		[
			'id'         => 803,
			'gid'        => 702,
			'contact-id' => 3043, // othercontact (public)
		],
	],
	'search' => [
		// Id prefix: 90
		[
			'id'   => 901,
			'term' => 'Saved search',
			'uid'  => 1042, // selfcontact
		],
	],
];
