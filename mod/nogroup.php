<?php
/**
 * @file mod/nogroup.php
 */
use Friendica\App;
use Friendica\Core\L10n;

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

	$a->internalRedirect('group/none');
}
