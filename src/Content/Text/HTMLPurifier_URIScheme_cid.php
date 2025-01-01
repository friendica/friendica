<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Text;

use HTMLPurifier_Config;
use HTMLPurifier_Context;
use HTMLPurifier_URI;
use HTMLPurifier_URIScheme;

/**
 * Validates content-id ("cid") as used in multi-part MIME messages, as defined by RFC 2392
 */
class HTMLPurifier_URIScheme_cid extends HTMLPurifier_URIScheme
{
	/**
	 * @type bool
	 */
	public $browsable = true;

	/**
	 * @type bool
	 */
	public $may_omit_host = true;

	/**
	 * @param HTMLPurifier_URI $uri
	 * @param HTMLPurifier_Config $config
	 * @param HTMLPurifier_Context $context
	 * @return bool
	 */
	public function doValidate(&$uri, $config, $context)
	{
		$uri->userinfo = null;
		$uri->host     = null;
		$uri->port     = null;
		$uri->query    = null;
		// typecode check needed on path
		return true;
	}
}
