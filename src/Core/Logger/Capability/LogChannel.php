<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Logger\Capability;

/**
 * An enum class for the Log channels
 */
interface LogChannel
{
	/** @var string channel for the auth_ejabbered script */
	public const AUTH_JABBERED = 'auth_ejabberd';
	/** @var string Default channel in case it isn't set explicit */
	public const DEFAULT = self::APP;
	/** @var string channel for console execution */
	public const CONSOLE = 'console';
	/** @var string channel for developer focused logging */
	public const DEV = 'dev';
	/** @var string channel for daemon executions */
	public const DAEMON = 'daemon';
	/** @var string channel for worker execution */
	public const WORKER = 'worker';
	/** @var string channel for jetstream execution */
	public const JETSTREAM = 'jetstream';
	/** @var string channel for frontend app executions */
	public const APP = 'app';
}
