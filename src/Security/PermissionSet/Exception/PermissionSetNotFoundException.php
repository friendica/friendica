<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Security\PermissionSet\Exception;

use Exception;

class PermissionSetNotFoundException extends \RuntimeException
{
	public function __construct($message = '', ?Exception $previous = null)
	{
		parent::__construct($message, 404, $previous);
	}
}
