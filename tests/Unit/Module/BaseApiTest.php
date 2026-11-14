<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Module;

use Friendica\App;
use Friendica\AppHelper;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Factory\Api\Mastodon\Error;
use Friendica\Factory\Api\Twitter\User as TwitterUser;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Util\Profiler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class BaseApiTest extends TestCase
{
	private const BASE_URL = 'http://example.com';
	private const COMMAND  = 'api/v1/timelines/home';

	#[DataProvider('createModuleAndResponseProvider')]
	public function testSetPaginationLinkHeaderWithBoundariesSetsLinkHeader(ApiResponse $response, BaseApi $module): void
	{
		$module->getRequest([], ['limit' => 20]);
		$module::setBoundaries(100); // @phpstan-ignore staticMethod.protected
		$module::setBoundaries(200); // @phpstan-ignore staticMethod.protected
		$module->setPaginationLinkHeader(); // @phpstan-ignore method.protected

		$headers = $response->getHeaders();
		self::assertArrayHasKey('Link', $headers);

		$expectedLink = '<' . self::BASE_URL . '/' . self::COMMAND . '?limit=20&max_id=100>; rel="next", <'
			. self::BASE_URL . '/' . self::COMMAND . '?limit=20&min_id=200>; rel="prev"';

		self::assertSame($expectedLink, $headers['Link']);

		$psr7Response = $response->generate();
		self::assertSame($expectedLink, $psr7Response->getHeaderLine('Link'));
	}

	#[DataProvider('createModuleAndResponseProvider')]
	public function testSetPaginationLinkHeaderWithoutBoundariesSetsNoLinkHeader(ApiResponse $response, BaseApi $module): void
	{
		$module->getRequest([], ['limit' => 20]);
		$module->setPaginationLinkHeader(); // @phpstan-ignore method.protected

		$headers = $response->getHeaders();
		self::assertArrayNotHasKey('Link', $headers);
	}

	#[DataProvider('createModuleAndResponseProvider')]
	public function testSetPaginationLinkHeaderAsDateSetsIso8601Format(ApiResponse $response, BaseApi $module): void
	{
		$minDate = new \DateTime('2026-01-01T00:00:00.000Z');
		$maxDate = new \DateTime('2026-12-31T23:59:59.999Z');

		$module->getRequest([], ['limit' => 20]);
		$module::setBoundaries($minDate); // @phpstan-ignore staticMethod.protected
		$module::setBoundaries($maxDate); // @phpstan-ignore staticMethod.protected
		$module->setPaginationLinkHeader(true); // @phpstan-ignore method.protected

		$headers = $response->getHeaders();
		self::assertArrayHasKey('Link', $headers);

		$expectedLink = '<' . self::BASE_URL . '/' . self::COMMAND . '?limit=20&max_id=' . rawurlencode('2026-01-01T00:00:00.000Z') . '>; rel="next", <'
			. self::BASE_URL . '/' . self::COMMAND . '?limit=20&min_id=' . rawurlencode('2026-12-31T23:59:59.999Z') . '>; rel="prev"';

		self::assertSame($expectedLink, $headers['Link']);
	}

	#[DataProvider('createModuleAndResponseProvider')]
	public function testSetPaginationLinkHeaderByOffsetLimitSetsPrevAndNext(ApiResponse $response, BaseApi $module): void
	{
		$offset = 20;
		$limit  = 20;

		$module->getRequest([], []);
		$module->setPaginationLinkHeaderByOffsetLimit($offset, $limit); // @phpstan-ignore method.protected

		$headers = $response->getHeaders();
		self::assertArrayHasKey('Link', $headers);

		$prevOffset = $offset - $limit;
		$nextOffset = $offset + $limit;

		$expectedLink = '<' . self::BASE_URL . '/' . self::COMMAND . '?limit=' . $limit . '&offset=' . $nextOffset . '>; rel="next", <'
			. self::BASE_URL . '/' . self::COMMAND . '?limit=' . $limit . '&offset=' . $prevOffset . '>; rel="prev"';

		self::assertSame($expectedLink, $headers['Link']);
	}

	#[DataProvider('createModuleAndResponseProvider')]
	public function testSetPaginationLinkHeaderByOffsetLimitWithoutPrevSetsOnlyNext(ApiResponse $response, BaseApi $module): void
	{
		$offset = 5;
		$limit  = 20;

		$module->getRequest([], []);
		$module->setPaginationLinkHeaderByOffsetLimit($offset, $limit); // @phpstan-ignore method.protected

		$headers = $response->getHeaders();
		self::assertArrayHasKey('Link', $headers);

		$nextOffset = $offset + $limit;

		$expectedLink = '<' . self::BASE_URL . '/' . self::COMMAND . '?limit=' . $limit . '&offset=' . $nextOffset . '>; rel="next"';

		self::assertSame($expectedLink, $headers['Link']);
	}

	public static function createModuleAndResponseProvider(): array
	{
		$baseUrl = new App\BaseURL(self::createConfigStub(), self::createStub(LoggerInterface::class));
		$args    = new App\Arguments('', self::COMMAND);

		$response = new ApiResponse(
			self::createStub(L10n::class),
			$args,
			self::createStub(LoggerInterface::class),
			$baseUrl,
			self::createStub(TwitterUser::class),
			self::createStub(IManageConfigValues::class),
		);

		$module = new class (
			self::createStub(Error::class),
			self::createStub(AppHelper::class),
			self::createStub(L10n::class),
			$baseUrl,
			$args,
			self::createStub(LoggerInterface::class),
			self::createStub(Profiler::class),
			$response,
		) extends BaseApi {
			public function __construct(
				Error $errorFactory,
				AppHelper $appHelper,
				L10n $l10n,
				App\BaseURL $baseUrl,
				App\Arguments $args,
				LoggerInterface $logger,
				Profiler $profiler,
				ApiResponse $response,
			) {
				$this->errorFactory = $errorFactory;
				$this->appHelper    = $appHelper;
				$this->l10n         = $l10n;
				$this->baseUrl      = $baseUrl;
				$this->args         = $args;
				$this->logger       = $logger;
				$this->profiler     = $profiler;
				$this->response     = $response;
				$this->server       = [];
			}

			public function setPaginationLinkHeader(bool $asDate = false): void
			{
				parent::setPaginationLinkHeader($asDate);
			}

			public function setPaginationLinkHeaderByOffsetLimit(int $offset, int $limit): void
			{
				parent::setPaginationLinkHeaderByOffsetLimit($offset, $limit);
			}

			public static function setBoundaries($id): void
			{
				parent::setBoundaries($id);
			}
		};

		return ['default' => [$response, $module]];
	}

	private static function createConfigStub(): IManageConfigValues
	{
		$config = self::createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'url', null, self::BASE_URL],
		]);

		return $config;
	}
}
