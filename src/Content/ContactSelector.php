<?php
/**
 * @file src/Content/ContactSelector.php
 */
namespace Friendica\Content;

use Friendica\Core\Addon;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;

/**
 * @brief ContactSelector class
 */
class ContactSelector
{
	/**
	 * @param string $current     current
	 * @param string $foreign_net network
	 */
	public static function profileAssign($current, $foreign_net)
	{
		$o = '';

		$disabled = (($foreign_net) ? ' disabled="true" ' : '');

		$o .= "<select id=\"contact-profile-selector\" class=\"form-control\" $disabled name=\"profile-assign\" >\r\n";

		$s = DBA::select('profile', ['id', 'profile-name', 'is-default'], ['uid' => $_SESSION['uid']]);
		$r = DBA::toArray($s);

		if (DBA::isResult($r)) {
			foreach ($r as $rr) {
				$selected = (($rr['id'] == $current || ($current == 0 && $rr['is-default'] == 1)) ? " selected=\"selected\" " : "");
				$o .= "<option value=\"{$rr['id']}\" $selected >{$rr['profile-name']}</option>\r\n";
			}
		}
		$o .= "</select>\r\n";
		return $o;
	}

	/**
	 * @param string  $current  current
	 * @param boolean $disabled optional, default false
	 * @return object
	 */
	public static function pollInterval($current, $disabled = false)
	{
		$dis = (($disabled) ? ' disabled="disabled" ' : '');
		$o = '';
		$o .= "<select id=\"contact-poll-interval\" name=\"poll\" $dis />" . "\r\n";

		$rep = [
			0 => L10n::t('Frequently'),
			1 => L10n::t('Hourly'),
			2 => L10n::t('Twice daily'),
			3 => L10n::t('Daily'),
			4 => L10n::t('Weekly'),
			5 => L10n::t('Monthly')
		];

		foreach ($rep as $k => $v) {
			$selected = (($k == $current) ? " selected=\"selected\" " : "");
			$o .= "<option value=\"$k\" $selected >$v</option>\r\n";
		}
		$o .= "</select>\r\n";
		return $o;
	}

	/**
	 * @param string $s       network
	 * @param string $profile optional, default empty
	 * @return string
	 */
	public static function networkToName($s, $profile = "")
	{
		$nets = [
			Protocol::DFRN      => L10n::t('Friendica'),
			Protocol::OSTATUS   => L10n::t('OStatus'),
			Protocol::FEED      => L10n::t('RSS/Atom'),
			Protocol::MAIL      => L10n::t('Email'),
			Protocol::DIASPORA  => L10n::t('Diaspora'),
			Protocol::ZOT       => L10n::t('Zot!'),
			Protocol::LINKEDIN  => L10n::t('LinkedIn'),
			Protocol::XMPP      => L10n::t('XMPP/IM'),
			Protocol::MYSPACE   => L10n::t('MySpace'),
			Protocol::GPLUS     => L10n::t('Google+'),
			Protocol::PUMPIO    => L10n::t('pump.io'),
			Protocol::TWITTER   => L10n::t('Twitter'),
			Protocol::DIASPORA2 => L10n::t('Diaspora Connector'),
			Protocol::STATUSNET => L10n::t('GNU Social Connector'),
			Protocol::PNUT      => L10n::t('pnut'),
		];

		Addon::callHooks('network_to_name', $nets);

		$search  = array_keys($nets);
		$replace = array_values($nets);

		$networkname = str_replace($search, $replace, $s);

		if ((in_array($s, [Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS])) && ($profile != "")) {
			$r = DBA::fetchFirst("SELECT `gserver`.`platform` FROM `gcontact`
					INNER JOIN `gserver` ON `gserver`.`nurl` = `gcontact`.`server_url`
					WHERE `gcontact`.`nurl` = ? AND `platform` != ''", normalise_link($profile));

			if (DBA::isResult($r)) {
				$networkname = $r['platform'];
			}
		}

		return $networkname;
	}

	/**
	 * @param string $current optional, default empty
	 * @param string $suffix  optionsl, default empty
	 */
	public static function gender($current = "", $suffix = "")
	{
		$o = '';
		$select = ['', L10n::t('Male'), L10n::t('Female'), L10n::t('Currently Male'), L10n::t('Currently Female'), L10n::t('Mostly Male'), L10n::t('Mostly Female'), L10n::t('Transgender'), L10n::t('Intersex'), L10n::t('Transsexual'), L10n::t('Hermaphrodite'), L10n::t('Neuter'), L10n::t('Non-specific'), L10n::t('Other'), L10n::t('Undecided')];

		Addon::callHooks('gender_selector', $select);

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

	/**
	 * @param string $current optional, default empty
	 * @param string $suffix  optionsl, default empty
	 */
	public static function sexualPreference($current = "", $suffix = "")
	{
		$o = '';
		$select = ['', L10n::t('Males'), L10n::t('Females'), L10n::t('Gay'), L10n::t('Lesbian'), L10n::t('No Preference'), L10n::t('Bisexual'), L10n::t('Autosexual'), L10n::t('Abstinent'), L10n::t('Virgin'), L10n::t('Deviant'), L10n::t('Fetish'), L10n::t('Oodles'), L10n::t('Nonsexual')];


		Addon::callHooks('sexpref_selector', $select);

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

	/**
	 * @param string $current optional, default empty
	 */
	public static function maritalStatus($current = "")
	{
		$o = '';
		$select = ['', L10n::t('Single'), L10n::t('Lonely'), L10n::t('Available'), L10n::t('Unavailable'), L10n::t('Has crush'), L10n::t('Infatuated'), L10n::t('Dating'), L10n::t('Unfaithful'), L10n::t('Sex Addict'), L10n::t('Friends'), L10n::t('Friends/Benefits'), L10n::t('Casual'), L10n::t('Engaged'), L10n::t('Married'), L10n::t('Imaginarily married'), L10n::t('Partners'), L10n::t('Cohabiting'), L10n::t('Common law'), L10n::t('Happy'), L10n::t('Not looking'), L10n::t('Swinger'), L10n::t('Betrayed'), L10n::t('Separated'), L10n::t('Unstable'), L10n::t('Divorced'), L10n::t('Imaginarily divorced'), L10n::t('Widowed'), L10n::t('Uncertain'), L10n::t('It\'s complicated'), L10n::t('Don\'t care'), L10n::t('Ask me')];

		Addon::callHooks('marital_selector', $select);

		$o .= '<select name="marital" id="marital-select" size="1" >';
		foreach ($select as $selection) {
			if ($selection !== 'NOTRANSLATION') {
				$selected = (($selection == $current) ? ' selected="selected" ' : '');
				$o .= "<option value=\"$selection\" $selected >$selection</option>";
			}
		}
		$o .= '</select>';
		return $o;
	}
}
