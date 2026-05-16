<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Util;

use Friendica\Test\DiceHttpMockHandlerTrait;
use Friendica\Test\MockedTestCase;
use Friendica\Util\Images;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class ImagesTest extends MockedTestCase
{
	use DiceHttpMockHandlerTrait;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setupHttpMockHandler();
	}

	protected function tearDown(): void
	{
		$this->tearDownFixtures();

		parent::tearDown();
	}

	public function dataImages()
	{
		return [
			'image' => [
				'url'     => 'https://pbs.twimg.com/profile_images/2365515285/9re7kx4xmc0eu9ppmado.png',
				'headers' => [
					'Server'                        => 'tsa_b',
					'Content-Type'                  => 'image/png',
					'Cache-Control'                 => 'max-age=604800,must-revalidate',
					'Last-Modified'                 => 'Thu,04Nov201001:42:54GMT',
					'Content-Length'                => '24875',
					'Access-Control-Allow-Origin'   => '*',
					'Access-Control-Expose-Headers' => 'Content-Length',
					'Date'                          => 'Mon,23Aug202112:39:00GMT',
					'Connection'                    => 'keep-alive',
				],
				'data'      => file_get_contents(__DIR__ . '/../../datasets/curl/image.content'),
				'assertion' => [
					'0'    => '400',
					'1'    => '400',
					'2'    => '3',
					'3'    => 'width="400" height="400"',
					'bits' => '8',
					'mime' => 'image/png',
					'size' => '24875',
				],
			],
			'emptyUrl' => [
				'url'       => '',
				'headers'   => [],
				'data'      => '',
				'assertion' => [],
			],
		];
	}

	/**
	 * Test the Images::getInfoFromURL() method (only remote images, not local/relative!)
	 *
	 * @dataProvider dataImages
	 */
	public function testGetInfoFromRemoteURL(string $url, array $headers, string $data, array $assertion)
	{
		$this->httpRequestHandler->setHandler(new MockHandler([
			new Response(200, $headers, $data),
		]));

		$result = Images::getInfoFromURL($url);

		self::assertIsArray($result);

		foreach ($assertion as $key => $value) {
			self::assertArrayHasKey($key, $result);
			self::assertEquals($value, $result[$key]);
		}
	}

	public function dataScalingDimensions()
	{
		return [
			'landscape' => [
				'width'     => 640,
				'height'    => 480,
				'max'       => 320,
				'assertion' => [
					'width'  => 320,
					'height' => 240,
				],
			],
			'wide_landscape' => [
				'width'     => 640,
				'height'    => 120,
				'max'       => 320,
				'assertion' => [
					'width'  => 320,
					'height' => 60,
				],
			],
			'landscape_round_up' => [
				'width'     => 640,
				'height'    => 479,
				'max'       => 320,
				'assertion' => [
					'width'  => 320,
					'height' => 240,
				],
			],
			'landscape_zero_height' => [
				'width'     => 640,
				'height'    => 1,
				'max'       => 160,
				'assertion' => [
					'width'  => 160,
					'height' => 1,
				],
			],
			'portrait' => [
				'width'     => 480,
				'height'    => 640,
				'max'       => 320,
				'assertion' => [
					'width'  => 240,
					'height' => 320,
				],
			],
			// For portrait with aspect ratio <= 16:9, constrain height
			'portrait_16_9' => [
				'width'     => 1080,
				'height'    => 1920,
				'max'       => 320,
				'assertion' => [
					'width'  => 180,
					'height' => 320,
				],
			],
			// For portrait with aspect ratio > 16:9, constrain width
			'portrait_over_16_9_too_wide' => [
				'width'     => 1080,
				'height'    => 1921,
				'max'       => 320,
				'assertion' => [
					'width'  => 320,
					'height' => 570,
				],
			],
			// For portrait with aspect ratio > 16:9, constrain width
			'portrait_over_16_9_not_too_wide' => [
				'width'     => 1080,
				'height'    => 1921,
				'max'       => 1080,
				'assertion' => [
					'width'  => 1080,
					'height' => 1921,
				],
			],
			'portrait_round_up' => [
				'width'     => 479,
				'height'    => 640,
				'max'       => 320,
				'assertion' => [
					'width'  => 240,
					'height' => 320,
				],
			],
		];
	}

	/**
	 * Test the Images::getScalingDimensions() method
	 *
	 * @dataProvider dataScalingDimensions
	 */
	public function testGetScalingDimensions(int $width, int $height, int $max, array $assertion)
	{
		$result = Images::getScalingDimensions($width, $height, $max);

		self::assertIsArray($result);

		foreach ($assertion as $key => $value) {
			self::assertArrayHasKey($key, $result);
			self::assertEquals($value, $result[$key]);
		}
	}
}
