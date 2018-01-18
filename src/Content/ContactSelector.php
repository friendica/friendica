<?php
/**
 * @file src/Content/ContactSelector.php
 */
namespace Friendica\Content;

use Friendica\Core\Addon;
use Friendica\Database\DBM;
use Friendica\Protocol\Diaspora;
use dba;

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

		$s = dba::select('profile', ['id', 'profile-name', 'is-default'], ['uid' => $$_SESSION['uid']]);
		$r = dba::inArray($s);

		if (DBM::is_result($r)) {
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
			0 => t('Frequently'),
			1 => t('Hourly'),
			2 => t('Twice daily'),
			3 => t('Daily'),
			4 => t('Weekly'),
			5 => t('Monthly')
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
			NETWORK_DFRN     => t('Friendica'),
			NETWORK_OSTATUS  => t('OStatus'),
			NETWORK_FEED     => t('RSS/Atom'),
			NETWORK_MAIL     => t('Email'),
			NETWORK_DIASPORA => t('Diaspora'),
			NETWORK_FACEBOOK => t('Facebook'),
			NETWORK_ZOT      => t('Zot!'),
			NETWORK_LINKEDIN => t('LinkedIn'),
			NETWORK_XMPP     => t('XMPP/IM'),
			NETWORK_MYSPACE  => t('MySpace'),
			NETWORK_GPLUS    => t('Google+'),
			NETWORK_PUMPIO   => t('pump.io'),
			NETWORK_TWITTER  => t('Twitter'),
			NETWORK_DIASPORA2 => t('Diaspora Connector'),
			NETWORK_STATUSNET => t('GNU Social Connector'),
			NETWORK_PNUT      => t('pnut'),
			NETWORK_APPNET => t('App.net')
		];

		Addon::callHooks('network_to_name', $nets);

		$search  = array_keys($nets);
		$replace = array_values($nets);

		$networkname = str_replace($search, $replace, $s);

		if ((in_array($s, [NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS])) && ($profile != "")) {
			$r = dba::fetch_first("SELECT `gserver`.`platform` FROM `gcontact`
					INNER JOIN `gserver` ON `gserver`.`nurl` = `gcontact`.`server_url`
					WHERE `gcontact`.`nurl` = ? AND `platform` != ''", normalise_link($profile));

			if (DBM::is_result($r)) {
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
		$select = ['', t('Male'), t('Female'), t('Currently Male'), t('Currently Female'), t('Mostly Male'), t('Mostly Female'), t('Transgender'), t('Intersex'), t('Transsexual'), t('Hermaphrodite'), t('Neuter'), t('Non-specific'), t('Other'), t('Undecided')];
	
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
		$select = ['', t('Males'), t('Females'), t('Gay'), t('Lesbian'), t('No Preference'), t('Bisexual'), t('Autosexual'), t('Abstinent'), t('Virgin'), t('Deviant'), t('Fetish'), t('Oodles'), t('Nonsexual')];
	
	
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
		$select = ['', t('Single'), t('Lonely'), t('Available'), t('Unavailable'), t('Has crush'), t('Infatuated'), t('Dating'), t('Unfaithful'), t('Sex Addict'), t('Friends'), t('Friends/Benefits'), t('Casual'), t('Engaged'), t('Married'), t('Imaginarily married'), t('Partners'), t('Cohabiting'), t('Common law'), t('Happy'), t('Not looking'), t('Swinger'), t('Betrayed'), t('Separated'), t('Unstable'), t('Divorced'), t('Imaginarily divorced'), t('Widowed'), t('Uncertain'), t('It\'s complicated'), t('Don\'t care'), t('Ask me')];
	
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
