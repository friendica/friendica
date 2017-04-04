<?php


function gender_selector($current="",$suffix="") {
	$o = '';
	$select = array('', t('Male'), t('Female'), t('Currently Male'), t('Currently Female'), t('Mostly Male'), t('Mostly Female'), t('Transgender'), t('Intersex'), t('Transsexual'), t('Hermaphrodite'), t('Neuter'), t('Non-specific'), t('Other'), t('Undecided'));

	call_hooks('gender_selector', $select);

	$o .= "<select name=\"gender$suffix\" id=\"gender-select$suffix\" size=\"1\" >";
	foreach ($select as $selection) {
		if ($selection !== 'NOTRANSLATION') {
			$selected = (($selection == $current) ? ' selected="selected" ' : '');
			$o .= "<option value=\"$selection\" $selected >$selection</option>";
		}
	}
	$o .= '</select>';
	return $o;
}	

function sexpref_selector($current="",$suffix="") {
	$o = '';
	$select = array('', t('Males'), t('Females'), t('Gay'), t('Lesbian'), t('No Preference'), t('Bisexual'), t('Autosexual'), t('Abstinent'), t('Virgin'), t('Deviant'), t('Fetish'), t('Oodles'), t('Nonsexual'));


	call_hooks('sexpref_selector', $select);

	$o .= "<select name=\"sexual$suffix\" id=\"sexual-select$suffix\" size=\"1\" >";
	foreach ($select as $selection) {
		if ($selection !== 'NOTRANSLATION') {
			$selected = (($selection == $current) ? ' selected="selected" ' : '');
			$o .= "<option value=\"$selection\" $selected >$selection</option>";
		}
	}
	$o .= '</select>';
	return $o;
}	


function marital_selector($current="",$suffix="") {
	$o = '';
	$select = array('', t('Single'), t('Lonely'), t('Available'), t('Unavailable'), t('Has crush'), t('Infatuated'), t('Dating'), t('Unfaithful'), t('Sex Addict'), t('Friends'), t('Friends/Benefits'), t('Casual'), t('Engaged'), t('Married'), t('Imaginarily married'), t('Partners'), t('Cohabiting'), t('Common law'), t('Happy'), t('Not looking'), t('Swinger'), t('Betrayed'), t('Separated'), t('Unstable'), t('Divorced'), t('Imaginarily divorced'), t('Widowed'), t('Uncertain'), t('It\'s complicated'), t('Don\'t care'), t('Ask me') );

	call_hooks('marital_selector', $select);

	$o .= "<select name=\"marital\" id=\"marital-select\" size=\"1\" >";
	foreach ($select as $selection) {
		if ($selection !== 'NOTRANSLATION') {
			$selected = (($selection == $current) ? ' selected="selected" ' : '');
			$o .= "<option value=\"$selection\" $selected >$selection</option>";
		}
	}
	$o .= '</select>';
	return $o;
}	
