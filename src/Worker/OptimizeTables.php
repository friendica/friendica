<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Database\DBA;
use Friendica\DI;

/**
 * Optimize tables that are known to grow and shrink all the time
 */
class OptimizeTables
{
	public static function execute()
	{

		if (!DI::lock()->acquire('optimize_tables', 0)) {
			DI::logger()->warning('Lock could not be acquired');
			return;
		}

		DI::logger()->info('Optimize start');

		DBA::optimizeTable('cache');
		DBA::optimizeTable('locks');
		DBA::optimizeTable('oembed');
		DBA::optimizeTable('parsed_url');
		DBA::optimizeTable('session');
		DBA::optimizeTable('post-engagement');
		DBA::optimizeTable('check-full-text-search');

		if (DI::config()->get('system', 'optimize_all_tables')) {
			DBA::optimizeTable('apcontact');
			DBA::optimizeTable('contact');
			DBA::optimizeTable('contact-relation');
			DBA::optimizeTable('conversation');
			DBA::optimizeTable('diaspora-contact');
			DBA::optimizeTable('diaspora-interaction');
			DBA::optimizeTable('fcontact');
			DBA::optimizeTable('gserver');
			DBA::optimizeTable('gserver-tag');
			DBA::optimizeTable('inbox-status');
			DBA::optimizeTable('item-uri');
			DBA::optimizeTable('notification');
			DBA::optimizeTable('notify');
			DBA::optimizeTable('photo');
			DBA::optimizeTable('post');
			DBA::optimizeTable('post-content');
			DBA::optimizeTable('post-delivery-data');
			DBA::optimizeTable('post-link');
			DBA::optimizeTable('post-thread');
			DBA::optimizeTable('post-thread-user');
			DBA::optimizeTable('post-user');
			DBA::optimizeTable('storage');
			DBA::optimizeTable('tag');
		}

		DI::logger()->info('Optimize end');

		DI::lock()->release('optimize_tables');
	}
}
