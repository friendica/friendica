<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Logger\Util;

use Friendica\App\Request;
use Friendica\Core\Logger\Capability\IHaveCallIntrospections;
use Friendica\Core\System;

/**
 * Get Introspection information about the current call
 */
class Introspection implements IHaveCallIntrospections
{
	/** @var string */
	private $requestId;

	/** @var int  */
	private $skipStackFramesCount;

	/** @var string[] */
	private $skipClassesPartials;

	private $skipFunctions = [
		'call_user_func',
		'call_user_func_array',
	];

	/**
	 * @param string[] $skipClassesPartials  An array of classes to skip during logging
	 * @param int      $skipStackFramesCount If the logger should use information from other hierarchy levels of the call
	 */
	public function __construct(Request $request, array $skipClassesPartials = [], int $skipStackFramesCount = 0)
	{
		$this->requestId            = $request->getRequestId();
		$this->skipClassesPartials  = $skipClassesPartials;
		$this->skipStackFramesCount = $skipStackFramesCount;
	}

	/**
	 * Adds new classes to get skipped
	 *
	 * @param array $classNames
	 */
	public function addClasses(array $classNames): void
	{
		$this->skipClassesPartials = array_merge($this->skipClassesPartials, $classNames);
	}

	/**
	 * Returns the introspection record of the current call
	 *
	 * @return array
	 */
	public function getRecord(): array
	{
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

		$i = 1;

		while ($this->isTraceClassOrSkippedFunction($trace[$i] ?? [])) {
			$i++;
		}

		$i += $this->skipStackFramesCount;

		return [
			'file'       => isset($trace[$i - 1]['file']) ? basename($trace[$i - 1]['file']) : null,
			'line'       => $trace[$i - 1]['line'] ?? null,
			'function'   => $trace[$i]['function'] ?? null,
			'request-id' => $this->requestId,
			'stack'      => System::callstack(15, 1, ['Friendica\Core\Logger\Type\StreamLogger', 'Friendica\Core\Logger\Type\AbstractLogger', 'Friendica\Core\Logger\Type\WorkerLogger', 'Friendica\Core\Logger']),
		];
	}

	/**
	 * Checks if the current trace class or function has to be skipped
	 *
	 * @param array $traceItem The current trace item
	 *
	 * @return bool True if the class or function should get skipped, otherwise false
	 */
	private function isTraceClassOrSkippedFunction(array $traceItem): bool
	{
		if (!$traceItem) {
			return false;
		}

		if (isset($traceItem['class'])) {
			foreach ($this->skipClassesPartials as $part) {
				if (strpos($traceItem['class'], $part) === 0) {
					return true;
				}
			}
		} elseif (in_array($traceItem['function'], $this->skipFunctions)) {
			return true;
		}

		return false;
	}
}
