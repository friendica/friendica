<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Util;

/**
 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/Zotlabs/Web/HTTPHeaders.php
 */
class HTTPHeaders
{
	private $in_progress = [];
	private $parsed      = [];

	public function __construct($headers)
	{
		$lines = explode("\n", str_replace("\r", '', $headers));

		foreach ($lines as $line) {
			if (preg_match('/^\s+/', $line, $matches) && trim($line)) {
				if (!empty($this->in_progress['k'])) {
					$this->in_progress['v'] .= ' ' . ltrim($line);
					continue;
				}
			} else {
				if (!empty($this->in_progress['k'])) {
					$this->parsed[]    = [$this->in_progress['k'] => $this->in_progress['v']];
					$this->in_progress = [];
				}

				$this->in_progress['k'] = strtolower(substr($line, 0, strpos($line, ':')));
				$this->in_progress['v'] = ltrim(substr($line, strpos($line, ':') + 1));
			}
		}

		if (!empty($this->in_progress['k'])) {
			$this->parsed[$this->in_progress['k']] = $this->in_progress['v'];
			$this->in_progress                     = [];
		}
	}

	public function fetch()
	{
		return $this->parsed;
	}
}
