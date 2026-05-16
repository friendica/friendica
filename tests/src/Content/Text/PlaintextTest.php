<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Content\Text;

use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\Plaintext;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Test\FixtureTestCase;

class PlaintextTest extends FixtureTestCase
{
	protected function setUp(): void
	{
		parent::setUp();
	}

	public function dataMessage()
	{
		return [
			'test-1' => [
				'data'     => "Ich habe mein Profil so eingestellt, dass ich alle Folgeanfragen manuell bestätigen muss, was langsam aber sicher richtig in Arbeit ausartet 😉\n\nIch schaue mir immer die anderen Profile an und schaue, was sie so gepostet haben. Wenn die Person noch nichts gepostet hat, ignoriere ich die Anfragen und schaue ggf. nach einiger Zeit wieder nach, ob jetzt was gepostet wurde! Wenn die Posts in eine Richtung gehen, die ich nicht mag, lehne ich die Anfragen ab.\n\nIch ignoriere auch Anfragen, wenn sie von Accounts kommen, die ggf. tausenden von anderen Accounts folgen, da ich davon ausgehe, dass da niemand ernsthaft so vielen Accounts folgen kann.",
				'expected' => [
					'Ich habe mein Profil so eingestellt, dass ich alle Folgeanfragen manuell bestätigen muss, was langsam aber sicher richtig in Arbeit ausartet 😉 (1/6)',
					'Ich schaue mir immer die anderen Profile an und schaue, was sie so gepostet haben. (2/6)',
					'Wenn die Person noch nichts gepostet hat, ignoriere ich die Anfragen und schaue ggf. nach einiger Zeit wieder nach, ob jetzt was gepostet wurde! (3/6)',
					'Wenn die Posts in eine Richtung gehen, die ich nicht mag, lehne ich die Anfragen ab. (4/6)',
					'Ich ignoriere auch Anfragen, wenn sie von Accounts kommen, die ggf. tausenden von anderen Accounts folgen, da ich davon ausgehe, (5/6)',
					'dass da niemand ernsthaft so vielen Accounts folgen kann. (6/6)',
				],
			],
			'test-2' => [
				'data'     => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.',
				'expected' => [
					'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, (1/6)',
					'sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. (2/6)',
					'Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. (3/6)',
					'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, (4/6)',
					'sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. (5/6)',
					'Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. (6/6)',
				],
			],
		];
	}

	/**
	 * Test split long texts
	 *
	 * @dataProvider dataMessage
	 *
	 * @param string $text     Test string
	 * @param array  $expected Expected result
	 *
	 * @throws InternalServerErrorException
	 */
	public function testSplitMessage(string $text, array $expected)
	{
		$item = [
			'uri-id' => -1,
			'uid'    => 0,
			'title'  => '',
			'plink'  => '',
			'body'   => $text,
		];
		$output = Plaintext::getPost($item, 160, false, BBCode::ATPROTOCOL);
		self::assertEquals($expected, $output['parts']);
	}
}
