<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

use Friendica\Content\Smilies;

function add_test_unicode_smilies(array &$b): void
{
	// String-substitution smilies
	// - no whitespaces
	Smilies::add($b, '⽕', '&#x1F525;');
	// - with whitespaces
	Smilies::add($b, ':hugging face:', '&#x1F917;');
	// - with multiple whitespaces
	Smilies::add($b, ':face with hand over mouth:', '&#x1F92D;');
	// Image-based smilies
	// - with whitespaces
	Smilies::add($b, ':smiley heart 333:', '<img class="smiley" src="/images/smiley-heart.gif" alt="smiley-heart" title="smiley-heart" />');
}
