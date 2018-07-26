<?php
/**
 * @file mod/nogroup.php
 */
use Friendica\App;
use Friendica\Content\ContactSelector;
use Friendica\Core\L10n;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Core\System;

function nogroup_init(App $a)
{
	if (! local_user()) {
		return;
	}
}

function nogroup_content(App $a)
{
	if (! local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return '';
	}

	goaway(System::baseUrl() . '/group/none');
}
