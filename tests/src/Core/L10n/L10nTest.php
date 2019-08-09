<?php

namespace Friendica\Test\src\Core\L10n;

use Dice\Dice;
use Friendica\App;
use Friendica\BaseObject;
use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Core\Config\Configuration;
use Friendica\Core\L10n\L10n;
use Friendica\Database\Database;
use Friendica\Test\MockedTest;
use Friendica\Util\Profiler;

class L10nTest extends MockedTest
{
	private function assertL10n(string $assertLang, array $assertStrings, L10n $l10n)
	{
		$this->assertEquals($assertLang, $l10n->getCurrentLang());

		foreach ($assertStrings as $key => $data) {
			$this->assertEquals($data['assert'], $l10n->t($key, ...$data['args']));
		}
	}

	private function assertL10nPlural(string $assertLang, array $assertStrings, L10n $l10n)
	{
		$this->assertEquals($assertLang, $l10n->getCurrentLang());

		foreach ($assertStrings as $key => $data) {
			$this->assertEquals($data['assert'], $l10n->tt($key, $data['plural'], $data['count']));
		}
	}

	/**
	 * Test the default language setting
	 */
	public function testDefault()
	{
		$l10n = new L10n();

		$this->assertL10n(L10n::DEFAULT_LANG, ['Thank You,' => ['assert' => 'Thank You,', 'args' => []]], $l10n);
	}

	/**
	 * Test the default language loading
	 */
	public function testLoadDefault()
	{
		$l10n = new L10n();
		$l10n->load();

		$this->assertL10n(L10n::DEFAULT_LANG, ['Thank You,' => ['assert' => 'Thank You,', 'args' => []]], $l10n);
	}

	public function dataLangSettings()
	{
		return [
			'withoutArg' => [
				'lang'    => 'de',
				'strings' => [
					'Thank You,' => [
						'assert' => 'Danke,',
						'args' => [],
					],
				],
			],
			'oneArg' => [
				'lang'    => 'es',
				'strings' => [
					'%s and You' => [
						'assert' => 'Philipp y Tú',
						'args' => ['Philipp'],
					],
				],
			],
			'twoArgs' => [
				'lang'    => 'fr',
				'strings' => [
					'%1$s tagged you at %2$s' => [
						'assert' => 'Philipp vous a étiqueté sur MrPetovan',
						'args' => ['Philipp', 'MrPetovan'],
					],
				],
			],
			'empty' => [
				'lang' => 'pl',
				'string' => [
					'' => [
						'assert' => '',
						'args' => [],
					],
				],
			],
			'unknownLang' => [
				'lang' => 'not',
				'string' => [
					'Test' => [
						'assert' => 'Test',
						'args' => [],
					],
				],
			],
		];
	}

	/**
	 * Test the singular function
	 *
	 * @dataProvider dataLangSettings
	 */
	public function testLoadedT(string $lang, array $strings)
	{
		$l10n = new L10n($lang);
		$l10n->load();

		$this->assertL10n($lang, $strings, $l10n);
	}

	public function dataPlural()
	{
		return [
			'withPlural' => [
				'lang' => 'de',
				'strings' => [
					'%d required parameter was not found at the given location' => [
						'assert' => '2 benötigte Parameter wurden an der angegebenen Stelle nicht gefunden',
						'plural' => '%d required parameters were not found at the given location',
						'count' => 2,
					],
				],
			],
			'unknownLang' => [
				'lang' => 'not',
				'strings' => [
					'Profile' => [
						'assert' => 'Two Profiles',
						'plural' => 'Two Profiles', // custom plural..
						'count' => 2,
					],
				],
			],
			'unknownSingular' => [
				'lang' => 'not',
				'strings' => [
					'Profile' => [
						'assert' => 'Profile',
						'plural' => 'Two Profiles', // custom plural..
						'count' => 1,
					],
				],
			],
		];
	}

	/**
	 * Test the plural function
	 *
	 * @dataProvider dataPlural
	 */
	public function testLoadedTT(string $lang, array $strings)
	{
		$l10n = new L10n($lang);
		$l10n->load();

		$this->assertL10nPlural($lang, $strings, $l10n);
	}

	public function testUserLoading()
	{
		$this->markTestSkipped('Until SESSION is a class');

		$l10n = new L10n();

		$dba = \Mockery::mock(Database::class)->makePartial();
		$server = [];
		$get = [];

		$dice = \Mockery::mock(Dice::class);

		$configCache = new ConfigCache(['system' => [ 'profiler' => false], 'rendertime' => [ 'callstack' => false]]);
		$profiler = new Profiler($configCache);

		$module = new App\Module(15);

		$dice->shouldReceive('create')->with(Profiler::class, [])->andReturn($profiler);
		$dice->shouldReceive('create')->with(App\Module::class, [])->andReturn($module);

		BaseObject::setDependencyInjection($dice);

		$userL10n = $l10n->userLanguage($dba, $module, $server, $get);

		// Immutable test
		$this->assertNotSame($l10n, $userL10n);
	}


	public function dataDetectLang()
	{
		return [
			'empty' => [
				'assertLang' => L10n::DEFAULT_LANG,
				'configLang' => L10n::DEFAULT_LANG,
				'configDefault' => L10n::DEFAULT_LANG,
				'server' => [],
				'get' => [],
				'addons' => '',
			],
			'differentConfig' => [
				'assertLang' => 'pl',
				'configLang' => 'pl',
				'configDefault' => L10n::DEFAULT_LANG,
				'server' => [],
				'get' => [],
				'addons' => '',
			],
			'serverDefault' => [
				'assertLAng' => 'nl',
				'configLang' => 'nl',
				'configDefault' => 'nl',
				'server' => [
					'HTTP_ACCEPT_LANGUAGE' => 'nl',
				],
				'get' => [],
				'addons' => '',
			],
			'serverMultiDefaultEn' => [
				'assertLAng' => 'en',
				'configLang' => 'en',
				'configDefault' => 'en',
				'server' => [
					'HTTP_ACCEPT_LANGUAGE' => 'en,en-US,en-AU;q=0.8,fr;q=0.6,en-GB;q=0.4',
				],
				'get' => [],
				'addons' => '',
			],
			'serverMultiDefaultQ' => [
				'assertLAng' => 'fr',
				'configLang' => 'fr',
				'configDefault' => 'fr',
				'server' => [
					// q=0.8 maps to fr
					'HTTP_ACCEPT_LANGUAGE' => 'q=0.8,fr;q=0.6,q=0.4',
				],
				'get' => [],
				'addons' => '',
			],
			'getOverride' => [
				'assertLAng' => L10n::DEFAULT_LANG,
				'configLang' => L10n::DEFAULT_LANG,
				'configDefault' => L10n::DEFAULT_LANG,
				'server' => [
					// q=0.8 maps to fr
					'HTTP_ACCEPT_LANGUAGE' => 'q=0.8,fr;q=0.6,q=0.4',
				],
				'get' => [
					// get adds the default lang, which is preferred
					'lang' => L10n::DEFAULT_LANG,
				],
				'addons' => '',
			],
			'loadAddonsToo' => [
				'assertLAng' => L10n::DEFAULT_LANG,
				'configLang' => L10n::DEFAULT_LANG,
				'configDefault' => L10n::DEFAULT_LANG,
				'server' => [
					// q=0.8 maps to fr
					'HTTP_ACCEPT_LANGUAGE' => 'q=0.8,fr;q=0.6,q=0.4',
				],
				'get' => [
					// get adds the default lang, which is preferred
					'lang' => L10n::DEFAULT_LANG,
				],
				'addons' => 'blogger',
			],
		];
	}

	/**
	 * Test the language detection and system loading
	 *
	 * @dataProvider dataDetectLang
	 */
	public function testSystemLoading(string $assertLang, $configLang, $configDefault, array $server, array $get, string $addons)
	{
		$l10n = new L10n();

		// mock the addons
		$dba = \Mockery::mock(Database::class)->makePartial();
		$dba->shouldReceive('select')
		    ->with('addon', ['name'], ['installed' => true])
		    ->andReturn($addons);

		// Mock the config setting
		$config = \Mockery::mock(Configuration::class);
		$config->shouldReceive('get')
		       ->with('system', 'language', $configDefault)
		       ->andReturn($configLang);

		$sysL10n = $l10n->systemLanguage($config, $dba, $server, $get);

		$this->assertNotSame($l10n, $sysL10n);

		$this->assertEquals($assertLang, $sysL10n->getCurrentLang());
	}
}
