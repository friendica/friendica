<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Factory\Api\Mastodon;

use Friendica\App\Arguments;
use Friendica\BaseFactory;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Psr\Log\LoggerInterface;

/** @todo A Factory shouldn't return something to the frontpage, it's for creating content, not showing it */
class Error extends BaseFactory
{
	/** @var Arguments */
	private $args;
	/** @var string[] The $_SERVER array */
	private $server;
	/** @var L10n */
	private $l10n;

	public function __construct(LoggerInterface $logger, Arguments $args, L10n $l10n, array $server)
	{
		parent::__construct($logger);
		$this->args   = $args;
		$this->server = $server;
		$this->l10n   = $l10n;
	}

	private function logError(int $errorno, string $error)
	{
		$this->logger->info('API Error', ['no' => $errorno, 'error' => $error, 'method' => $this->args->getMethod(), 'command' => $this->args->getQueryString(), 'user-agent' => $this->server['HTTP_USER_AGENT'] ?? '']);
	}

	public function RecordNotFound()
	{
		$error             = $this->l10n->t('Record not found');
		$error_description = '';
		$errorObj          = new \Friendica\Object\Api\Mastodon\Error($error, $error_description);

		$this->logError(404, $error);
		System::jsonError(404, $errorObj->toArray());
	}

	public function UnprocessableEntity(string $error = '')
	{
		$error             = $error ?: $this->l10n->t('Unprocessable Entity');
		$error_description = '';
		$errorObj          = new \Friendica\Object\Api\Mastodon\Error($error, $error_description);

		$this->logError(422, $error);
		System::jsonError(422, $errorObj->toArray());
	}

	public function Unauthorized(string $error = '', string $error_description = '')
	{
		$error             = $error ?: $this->l10n->t('Unauthorized');
		$errorObj          = new \Friendica\Object\Api\Mastodon\Error($error, $error_description);

		$this->logError(401, $error);
		System::jsonError(401, $errorObj->toArray());
	}

	public function Forbidden(string $error = '')
	{
		$error             = $error ?: $this->l10n->t('Token is not authorized with a valid user or is missing a required scope');
		$error_description = '';
		$errorObj          = new \Friendica\Object\Api\Mastodon\Error($error, $error_description);

		$this->logError(403, $error);
		System::jsonError(403, $errorObj->toArray());
	}

	public function InternalError(string $error = '')
	{
		$error             = $error ?: $this->l10n->t('Internal Server Error');
		$error_description = '';
		$errorObj          = new \Friendica\Object\Api\Mastodon\Error($error, $error_description);

		$this->logError(500, $error);
		System::jsonError(500, $errorObj->toArray());
	}
}
