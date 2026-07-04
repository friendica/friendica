<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Core\L10n;
use Psr\Log\LoggerInterface;

/** @todo A Factory shouldn't return something to the frontpage, it's for creating content, not showing it */
class Error extends BaseFactory
{
	public function __construct(LoggerInterface $logger, private readonly L10n $l10n)
	{
		parent::__construct($logger);
	}

	public function RecordNotFound(): \Friendica\Object\Api\Mastodon\Error
	{
		$error             = $this->l10n->t('Record not found');
		$error_description = '';
		return new \Friendica\Object\Api\Mastodon\Error($error, $error_description);
	}

	public function UnprocessableEntity(string $error = ''): \Friendica\Object\Api\Mastodon\Error
	{
		$error             = $error ?: $this->l10n->t('Unprocessable Entity');
		$error_description = '';
		return new \Friendica\Object\Api\Mastodon\Error($error, $error_description);
	}

	public function Unauthorized(string $error = '', string $error_description = ''): \Friendica\Object\Api\Mastodon\Error
	{
		$error = $error ?: $this->l10n->t('Unauthorized');
		return new \Friendica\Object\Api\Mastodon\Error($error, $error_description);
	}

	public function Forbidden(string $error = ''): \Friendica\Object\Api\Mastodon\Error
	{
		$error             = $error ?: $this->l10n->t('Token is not authorized with a valid user or is missing a required scope');
		$error_description = '';
		return new \Friendica\Object\Api\Mastodon\Error($error, $error_description);
	}

	public function InternalError(string $error = ''): \Friendica\Object\Api\Mastodon\Error
	{
		$error             = $error ?: $this->l10n->t('Internal Server Error');
		$error_description = '';
		return new \Friendica\Object\Api\Mastodon\Error($error, $error_description);
	}
}
