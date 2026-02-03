<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Conversation\Entity;

class UserDefinedChannel extends Channel
{
	const CIRCLE_GLOBAL    = 0;
	const CIRCLE_ACTIVITY  = -5;
	const CIRCLE_POSTS     = -4;
	const CIRCLE_CREATION  = -3;
	const CIRCLE_FOLLOWING = -1;
	const CIRCLE_FOLLOWERS = -2;
}
