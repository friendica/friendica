<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Module\Special\HTTPException;
use Friendica\Util\Network;
use Psr\Http\Message\ResponseInterface;

/**
 * Apps class to register new OAuth clients
 * @see https://docs.joinmastodon.org/methods/apps/#create
 */
class Apps extends BaseApi
{
	public function run(HTTPException $httpException, array $request = [], bool $scopecheck = true): ResponseInterface
	{
		return parent::run($httpException, $request, false);
	}

	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function post(array $request = [])
	{
		if (!empty($request['redirect_uris']) && is_array($request['redirect_uris'])) {
			$request['redirect_uris'] = $request['redirect_uris'][0];
		}

		$request = $this->getRequest([
			'client_name'   => '',
			'redirect_uris' => '',
			'scopes'        => 'read',
			'website'       => '',
		], $request);

		// Workaround for AndStatus, see issue https://github.com/andstatus/andstatus/issues/538
		$postdata = Network::postdata();
		if (!empty($postdata)) {
			$postrequest = json_decode($postdata, true);
			if (!empty($postrequest) && is_array($postrequest)) {
				$request = array_merge($request, $postrequest);
			}

			if (!empty($request['redirect_uris']) && is_array($request['redirect_uris'])) {
				$request['redirect_uris'] = $request['redirect_uris'][0];
			}
		}

		if (empty($request['client_name']) || empty($request['redirect_uris'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity($this->t('Missing parameters')));
		}

		$fields = ['name' => $request['client_name'], 'redirect_uri' => $request['redirect_uris']];

		if (!empty($request['scopes'])) {
			$fields['scopes'] = $request['scopes'];
		}

		if (!empty($request['website'])) {
			$fields['website'] = $request['website'];
		}

		$application = DBA::selectFirst('application', ['id'], $fields);
		if (!empty($application['id'])) {
			$this->logger->debug('Found existing application', ['request' => $request, 'id' => $application['id']]);
			$this->jsonExit(DI::mstdnApplication()->createFromApplicationId($application['id'])->toArray());
		}

		$fields['read']          = (stripos((string) $request['scopes'], self::SCOPE_READ) !== false);
		$fields['write']         = (stripos((string) $request['scopes'], self::SCOPE_WRITE) !== false);
		$fields['follow']        = (stripos((string) $request['scopes'], self::SCOPE_FOLLOW) !== false);
		$fields['push']          = (stripos((string) $request['scopes'], self::SCOPE_PUSH) !== false);
		$fields['client_id']     = bin2hex(random_bytes(32));
		$fields['client_secret'] = bin2hex(random_bytes(32));

		if (!DBA::insert('application', $fields)) {
			$this->logAndJsonError(500, $this->errorFactory->InternalError());
		}

		$this->logger->debug('Create new application', ['request' => $request, 'id' => DBA::lastInsertId()]);
		$this->jsonExit(DI::mstdnApplication()->createFromApplicationId(DBA::lastInsertId())->toArray());
	}
}
