<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content;
use Friendica\Core\Renderer;
use Friendica\DI;

/**
 * Prints the possible Smilies of this node
 */
class Smilies extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		if (!empty(DI::args()->getArgv()[1]) && (DI::args()->getArgv()[1] === "json")) {
			$smilies = Content\Smilies::getList();
			$results = [];
			for ($i = 0; $i < count($smilies['texts']); $i++) {
				$results[] = ['text' => $smilies['texts'][$i], 'icon' => $smilies['icons'][$i]];
			}
			$this->jsonExit($results);
		}
	}

	protected function content(array $request = []): string
	{
		$smilies = Content\Smilies::getList();
		$count   = count($smilies['texts'] ?? []);

		$tpl = Renderer::getMarkupTemplate('smilies.tpl');
		return Renderer::replaceMacros($tpl, [
			'$count'   => $count,
			'$smilies' => $smilies,
		]);
	}
}
