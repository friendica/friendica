<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Logger;

use Friendica\Core\Logger\Capability\IHaveCallIntrospections;
use Friendica\Core\Logger\Type\SyslogLogger;

/**
 * Wraps the SyslogLogger for replacing the syslog call with a string field.
 */
class SyslogLoggerWrapper extends SyslogLogger
{
	private $content;

	public function __construct(string $channel, IHaveCallIntrospections $introspection, int $logLevel, int $logOptions, int $logFacility)
	{
		parent::__construct($channel, $introspection, $logLevel, $logOptions, $logFacility);

		$this->content = '';
	}

	/**
	 * Gets the content from the wrapped Syslog
	 *
	 * @return string
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * {@inheritdoc}
	 * @noinspection PhpMissingParentCallCommonInspection
	 */
	protected function syslogWrapper(int $level, string $entry)
	{
		$this->content .= $entry . PHP_EOL;
	}
}
