<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\DI;

class Manifest extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$config = DI::config();

		$tpl = Renderer::getMarkupTemplate('manifest.tpl');

		header('Content-type: application/manifest+json');

		$touch_icon = $config->get('system', 'touch_icon', 'images/friendica-128.png');
		if ($touch_icon == '') {
			$touch_icon = 'images/friendica-128.png';
		}

		$output = Renderer::replaceMacros($tpl, [
			'$touch_icon' => $touch_icon,
			'$title' => $config->get('config', 'sitename', 'Friendica'),
		]);

		echo $output;

		exit();
	}
}
