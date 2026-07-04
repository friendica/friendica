<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

use Friendica\Core\Protocol;
use Friendica\Model\Contact;

return [
	'user' => [
		[
			'uid'      => 42,
			'username' => 'Test user',
			'nickname' => 'selfcontact',
			'verified' => 1,
			'password' => '$2y$10$DLRNTRmJgKe1cSrFJ5Jb0edCqvXlA9sh/RHdSnfxjbR.04yZRm4Qm',
			'theme'    => 'frio',
		],
	],
	'item-uri' => [
		[
			'id'   => 42,
			'uri'  => 'http://localhost/profile/selfcontact',
			'guid' => '42',
		],
	],
	'contact' => [
		[
			'id'       => 42,
			'uid'      => 42,
			'uri-id'   => 42,
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
		[
			'id'            => 1,
			'uid'           => 42,
			'contact-id'    => 42,
			'backend-class' => null,
			'backend-ref'   => 'f0c0d0i2',
			'data'          => 'without class',
		],
		// move from storage-backend to maybe filesystem backend, skip at database backend
		[
			'id'            => 2,
			'uid'           => 42,
			'contact-id'    => 42,
			'backend-class' => 'Database',
			'backend-ref'   => 1,
			'data'          => '',
		],
		// move data if invalid storage
		[
			'id'            => 3,
			'uid'           => 42,
			'contact-id'    => 42,
			'backend-class' => 'invalid!',
			'backend-ref'   => 'unimported',
			'data'          => 'invalid data moved',
		],
		// @todo Check failing test because of this (never loaded) fixture
		//		[
		//			'id'            => 4,
		//			'uid'           => 42,
		//			'contact-id'    => 42,
		//			'backend-class' => 'invalid!',
		//			'backend-ref'   => 'unimported',
		//			'data'          => '',
		//		],
	],
	'storage' => [
		[
			'id'   => 1,
			'data' => 'inside database',
		],
	],
];
