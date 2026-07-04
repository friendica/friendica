<?php

// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

return [
	'storage' => [
		[
			'id'   => 1,
			'data' => file_get_contents(__DIR__ . '/test_photo.content'),
		],
	],
	'photo' => [
		[
			"id"            => 1,
			"uid"           => 42,
			"contact-id"    => 0,
			"guid"          => "71361a283a6aa6a2",
			"resource-id"   => "709057080661a283a6aa598501504178",
			"hash"          => "fc84f6c0ae79f626e4b7f65f438e60c1",
			"created"       => "2021-11-27 19:14:47",
			"edited"        => "2021-11-27 19:14:47",
			"title"         => "",
			"desc"          => "",
			"album"         => "test_album",
			"photo-type"    => 0,
			"filename"      => "traffic-signs-g1c8094e4f_1920.jpg",
			"type"          => "image/jpeg",
			"height"        => 226,
			"width"         => 320,
			"datasize"      => 96968,
			"data"          => "",
			"scale"         => 2,
			"profile"       => 0,
			"allow_cid"     => "",
			"allow_gid"     => "",
			"deny_cid"      => "",
			"deny_gid"      => "",
			"accessible"    => 0,
			"backend-class" => "Database",
			"backend-ref"   => "1",
			"updated"       => "0001-01-01 00:00:00",
		],
	],
];
