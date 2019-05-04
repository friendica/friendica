<?php

namespace Friendica\Console;

use Asika\SimpleConsole\Console;
use Friendica\App;

/**
 * Abstract class for App based console commands
 */
abstract class AbstractAppConsole extends Console
{
	/**
	 * @var App
	 */
	protected $app;

	public function __construct(App $app, $argv = null)
	{
		$this->app = $app;

		parent::__construct($argv);
	}
}
