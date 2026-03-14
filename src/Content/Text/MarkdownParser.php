<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Text;

use Friendica\Util\Strings;
use Michelf\MarkdownExtra;

class MarkdownParser extends MarkdownExtra
{
	protected function doAutoLinks($text)
	{
		$text = parent::doAutoLinks($text);

		$text = preg_replace_callback(
			Strings::autoLinkRegEx(),
			[$this, '_doAutoLinks_url_callback'],
			$text,
		);

		return $text;
	}
}
