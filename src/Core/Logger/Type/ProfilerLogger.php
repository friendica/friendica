<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Logger\Type;

use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * This Logger adds additional profiling data in case profiling is enabled.
 * It uses a predefined logger.
 */
class ProfilerLogger implements LoggerInterface
{
	/**
	 * The Profiler for the current call
	 * @var Profiler
	 */
	protected $profiler;

	/**
	 * ProfilerLogger constructor.
	 * @param LoggerInterface $logger   The Logger of the current call
	 * @param Profiler        $profiler The profiler of the current call
	 */
	public function __construct(/**
				 * The Logger of the current call
				 */
		private readonly LoggerInterface $logger,
		Profiler $profiler,
	) {
		$this->profiler = $profiler;
	}

	/**
	 * {@inheritdoc}
	 */
	public function emergency($message, array $context = [])
	{
		$this->profiler->startRecording('file');
		$this->logger->emergency($message, $context);
		$this->profiler->stopRecording();
	}

	/**
	 * {@inheritdoc}
	 */
	public function alert($message, array $context = [])
	{
		$this->profiler->startRecording('file');
		$this->logger->alert($message, $context);
		$this->profiler->stopRecording();
	}

	/**
	 * {@inheritdoc}
	 */
	public function critical($message, array $context = [])
	{
		$this->profiler->startRecording('file');
		$this->logger->critical($message, $context);
		$this->profiler->stopRecording();
	}

	/**
	 * {@inheritdoc}
	 */
	public function error($message, array $context = [])
	{
		$this->profiler->startRecording('file');
		$this->logger->error($message, $context);
		$this->profiler->stopRecording();
	}

	/**
	 * {@inheritdoc}
	 */
	public function warning($message, array $context = [])
	{
		$this->profiler->startRecording('file');
		$this->logger->warning($message, $context);
		$this->profiler->stopRecording();
	}

	/**
	 * {@inheritdoc}
	 */
	public function notice($message, array $context = [])
	{
		$this->profiler->startRecording('file');
		$this->logger->notice($message, $context);
		$this->profiler->stopRecording();
	}

	/**
	 * {@inheritdoc}
	 */
	public function info($message, array $context = [])
	{
		$this->profiler->startRecording('file');
		$this->logger->info($message, $context);
		$this->profiler->stopRecording();
	}

	/**
	 * {@inheritdoc}
	 */
	public function debug($message, array $context = [])
	{
		$this->profiler->startRecording('file');
		$this->logger->debug($message, $context);
		$this->profiler->stopRecording();
	}

	/**
	 * {@inheritdoc}
	 */
	public function log($level, $message, array $context = [])
	{
		$this->profiler->startRecording('file');
		$this->logger->log($level, $message, $context);
		$this->profiler->stopRecording();
	}
}
