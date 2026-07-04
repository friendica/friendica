<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Util;

use Friendica\Util\HTTPInputData;

/**
 * This class is used to enable testability for HTTPInputData
 * It overrides the two PHP input functionality with custom content
 */
class HTTPInputDataDouble extends HTTPInputData
{
	/** @var false|resource */
	protected $injectedStream = false;
	/** @var false|string */
	protected $injectedContent = false;
	/** @var false|string */
	protected $injectedContentType = false;

	/**
	 * injects the PHP input stream for a test
	 *
	 * @param false|resource $stream
	 */
	public function setPhpInputStream($stream)
	{
		$this->injectedStream = $stream;
	}

	/**
	 * injects the PHP input content for a test
	 *
	 * @param false|string $content
	 */
	public function setPhpInputContent($content)
	{
		$this->injectedContent = $content;
	}

	/**
	 * injects the PHP input content type for a test
	 *
	 * @param false|string $contentType
	 */
	public function setPhpInputContentType($contentType)
	{
		$this->injectedContentType = $contentType;
	}

	/** {@inheritDoc} */
	protected function getPhpInputStream()
	{
		return $this->injectedStream;
	}

	/** {@inheritDoc} */
	protected function getPhpInputContent()
	{
		return $this->injectedContent;
	}

	protected function fetchFileData($stream, string $boundary, array $headers, string $filename)
	{
		$data = parent::fetchFileData($stream, $boundary, $headers, $filename);
		if (!empty($data['tmp_name'])) {
			unlink($data['tmp_name']);
			$data['tmp_name'] = $data['name'];
		}

		return $data;
	}
}
