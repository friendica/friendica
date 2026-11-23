<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

use Friendica\Core\Protocol;
use Friendica\Model\Contact;

return [
	'user' => [
		// Id prefix: 10
		[
			'uid'      => 1042,
			'username' => 'Self contact',
			'nickname' => 'selfcontact',
			'verified' => 1,
			'password' => '$2y$10$DLRNTRmJgKe1cSrFJ5Jb0edCqvXlA9sh/RHdSnfxjbR.04yZRm4Qm',
			'theme'    => 'frio',
		],
	],
	'item-uri' => [
		// Item prefix: 20
		[
			'id'   => 20042,
			'uri'  => 'http://localhost/profile/selfcontact',
			'guid' => '42',
		],
	],
	'contact' => [
		// Id prefix: 30
		[
			'id'       => 3042,
			'uid'      => 1042,
			'uri-id'   => 20042,
			'name'     => 'Self contact',
			'nick'     => 'selfcontact',
			'self'     => 1,
			'nurl'     => 'http://localhost/profile/selfcontact',
			'url'      => 'http://localhost/profile/selfcontact',
			'about'    => 'User used in tests',
			'pending'  => 0,
			'blocked'  => 0,
			'rel'      => Contact::FOLLOWER,
			'network'  => Protocol::DFRN,
			'location' => 'DFRN',
		],
	],
	'photo' => [
		// move from data-attribute to storage backend
		// id prefix: 25
		[
			'id'            => 251,
			'uid'           => 1042,
			'contact-id'    => 3042,
			'backend-class' => null,
			'backend-ref'   => 'f0c0d0i2',
			'data'          => 'without class',
		],
		// move from storage-backend to maybe filesystem backend, skip at database backend
		[
			'id'            => 252,
			'uid'           => 1042,
			'contact-id'    => 3042,
			'backend-class' => 'Database',
			'backend-ref'   => 351,
			'data'          => '',
		],
		// move data if invalid storage
		[
			'id'            => 253,
			'uid'           => 1042,
			'contact-id'    => 3042,
			'backend-class' => 'invalid!',
			'backend-ref'   => 'unimported',
			'data'          => 'invalid data moved',
		],
		// @todo Check failing test because of this (never loaded) fixture
		//		[
		//			'id'            => 254,
		//			'uid'           => 1042,
		//			'contact-id'    => 3042,
		//			'backend-class' => 'invalid!',
		//			'backend-ref'   => 'unimported',
		//			'data'          => '',
		//		],
	],
	'storage' => [
		// Id prefix: 35
		[
			'id'   => 351,
			'data' => 'inside database',
		],
	],
];
