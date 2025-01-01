<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;

/**
 * Static definition for the Firefox Account Manager
 *
 * @see https://wiki.mozilla.org/Labs/Weave/Identity/Account_Manager/Spec/3#Contents_of_the_Account_Management_Control_Document
 */
class AccountManagementControlDocument extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$output = [
			'version'       => 1,
			'sessionstatus' => [
				'method' => 'GET',
				'path'   => '/session',
			],
			'auth-methods' => [
				'username-password-form' => [
					'connect' => [
						'method' => 'POST',
						'path'   => '/login',
						'params' => [
							'username' => 'login-name',
							'password' => 'password',
						],
						'onsuccess' => [
							'action' => 'reload',
						],
					],
					'disconnect' => [
						'method' => 'GET',
						'path'   => '/logout',
					],
				],
			],
			'methods' => [
				'username-password-form' => [
					'connect' => [
						'method' => 'POST',
						'path'   => '/login',
						'params' => [
							'username' => 'login-name',
							'password' => 'password',
						],
						'onsuccess' => [
							'action' => 'reload',
						],
					],
					'disconnect' => [
						'method' => 'GET',
						'path'   => '/logout',
					],
				],
			],
		];

		$this->jsonExit($output);
	}
}
