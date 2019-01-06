<?php
/**
 * @file src/Content/ContactSelector.php
 */
namespace Friendica\Content;

use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\Util\Network;
use Friendica\Util\Strings;

/**
 * @brief ContactSelector class
 */
class ContactSelector
{
	/**
	 * @param string $current     current
	 * @param string $foreign_net network
	 * @return string
	 * @throws \Exception
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
	 * @param string $network network
	 * @param string $profile optional, default empty
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function networkToName($network, $profile = "")
	{
		$nets = [
			Protocol::DFRN      =>   L10n::t('DFRN'),
			Protocol::OSTATUS   =>   L10n::t('OStatus'),
			Protocol::FEED      =>   L10n::t('RSS/Atom'),
			Protocol::MAIL      =>   L10n::t('Email'),
			Protocol::DIASPORA  =>   L10n::t('Diaspora'),
			Protocol::ZOT       =>   L10n::t('Zot!'),
			Protocol::LINKEDIN  =>   L10n::t('LinkedIn'),
			Protocol::XMPP      =>   L10n::t('XMPP/IM'),
			Protocol::MYSPACE   =>   L10n::t('MySpace'),
			Protocol::GPLUS     =>   L10n::t('Google+'),
			Protocol::PUMPIO    =>   L10n::t('pump.io'),
			Protocol::TWITTER   =>   L10n::t('Twitter'),
			Protocol::DIASPORA2 =>   L10n::t('Diaspora Connector'),
			Protocol::STATUSNET =>   L10n::t('GNU Social Connector'),
			Protocol::ACTIVITYPUB => L10n::t('ActivityPub'),
			Protocol::PNUT      =>   L10n::t('pnut'),
		];

		Hook::callAll('network_to_name', $nets);

		$search  = array_keys($nets);
		$replace = array_values($nets);

		$networkname = str_replace($search, $replace, $network);

		if ((in_array($network, [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS])) && ($profile != "")) {
			// Create the server url out of the profile url
			$parts = parse_url($profile);
			unset($parts['path']);
			$server_url = [Strings::normaliseLink(Network::unparseURL($parts))];

			// Fetch the server url
			$gcontact = DBA::selectFirst('gcontact', ['server_url'], ['nurl' => Strings::normaliseLink($profile)]);
			if (!empty($gcontact) && !empty($gcontact['server_url'])) {
				$server_url[] = Strings::normaliseLink($gcontact['server_url']);
			}

			// Now query the GServer for the platform name
			$gserver = DBA::selectFirst('gserver', ['platform', 'network'], ['nurl' => $server_url]);

			if (DBA::isResult($gserver)) {
				if (!empty($gserver['platform'])) {
					$platform = $gserver['platform'];
				} elseif (!empty($gserver['network']) && ($gserver['network'] != Protocol::ACTIVITYPUB)) {
					$platform = self::networkToName($gserver['network']);
				}

				if (!empty($platform)) {
					$networkname = $platform;

					if ($network == Protocol::ACTIVITYPUB) {
						$networkname .= ' (AP)';
					}
				}
			}
		}

		return $networkname;
	}

	/**
	 * @param string $current optional, default empty
	 * @param string $suffix  optionsl, default empty
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function gender($current = "", $suffix = "")
	{
		$o = '';
		$select = [
			'EMPTY'            => '',
			'Male'             => L10n::t('Male'),
			'Female'           => L10n::t('Female'),
			'Currently Male'   => L10n::t('Currently Male'),
			'Currently Female' => L10n::t('Currently Female'),
			'Mostly Male'      => L10n::t('Mostly Male'),
			'Mostly Female'    => L10n::t('Mostly Female'),
			'Transgender'      => L10n::t('Transgender'),
			'Intersex'         => L10n::t('Intersex'),
			'Transsexual'      => L10n::t('Transsexual'),
			'Hermaphrodite'    => L10n::t('Hermaphrodite'),
			'Neuter'           => L10n::t('Neuter'),
			'Non-specific'     => L10n::t('Non-specific'),
			'Other'            => L10n::t('Other'),
			'Undecided'        => L10n::t('Undecided'),
		];

		Hook::callAll('gender_selector', $select);

		$o .= "<select name=\"gender$suffix\" id=\"gender-select$suffix\" size=\"1\" >";
		foreach ($select as $neutral => $selection) {
			if ($selection !== 'NOTRANSLATION') {
				$selected = (($neutral == $current) ? ' selected="selected" ' : '');
				$o .= "<option value=\"$neutral\" $selected >$selection</option>";
			}
		}
		$o .= '</select>';
		return $o;
	}

	/**
	 * @param string $current optional, default empty
	 * @param string $suffix  optionsl, default empty
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function sexualPreference($current = "", $suffix = "")
	{
		$o = '';
		$select = [
			'EMPTY'         => '',
			'Males'         => L10n::t('Males'),
			'Females'       => L10n::t('Females'),
			'Gay'           => L10n::t('Gay'),
			'Lesbian'       => L10n::t('Lesbian'),
			'No Preference' => L10n::t('No Preference'),
			'Bisexual'      => L10n::t('Bisexual'),
			'Autosexual'    => L10n::t('Autosexual'),
			'Abstinent'     => L10n::t('Abstinent'),
			'Virgin'        => L10n::t('Virgin'),
			'Deviant'       => L10n::t('Deviant'),
			'Fetish'        => L10n::t('Fetish'),
			'Oodles'        => L10n::t('Oodles'),
			'Nonsexual'     => L10n::t('Nonsexual'),
		];

		Hook::callAll('sexpref_selector', $select);

		$o .= "<select name=\"sexual$suffix\" id=\"sexual-select$suffix\" size=\"1\" >";
		foreach ($select as $neutral => $selection) {
			if ($selection !== 'NOTRANSLATION') {
				$selected = (($neutral == $current) ? ' selected="selected" ' : '');
				$o .= "<option value=\"$neutral\" $selected >$selection</option>";
			}
		}
		$o .= '</select>';
		return $o;
	}

	/**
	 * @param string $current optional, default empty
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function maritalStatus($current = "")
	{
		$o = '';
		$select = [
			'EMPTY'                => '',
			'Single'               => L10n::t('Single'),
			'Lonely'               => L10n::t('Lonely'),
			'Available'            => L10n::t('Available'),
			'Unavailable'          => L10n::t('Unavailable'),
			'Has crush'            => L10n::t('Has crush'),
			'Infatuated'           => L10n::t('Infatuated'),
			'Dating'               => L10n::t('Dating'),
			'Unfaithful'           => L10n::t('Unfaithful'),
			'Sex Addict'           => L10n::t('Sex Addict'),
			'Friends'              => L10n::t('Friends'),
			'Friends/Benefits'     => L10n::t('Friends/Benefits'),
			'Casual'               => L10n::t('Casual'),
			'Engaged'              => L10n::t('Engaged'),
			'Married'              => L10n::t('Married'),
			'Imaginarily married'  => L10n::t('Imaginarily married'),
			'Partners'             => L10n::t('Partners'),
			'Cohabiting'           => L10n::t('Cohabiting'),
			'Common law'           => L10n::t('Common law'),
			'Happy'                => L10n::t('Happy'),
			'Not looking'          => L10n::t('Not looking'),
			'Swinger'              => L10n::t('Swinger'),
			'Betrayed'             => L10n::t('Betrayed'),
			'Separated'            => L10n::t('Separated'),
			'Unstable'             => L10n::t('Unstable'),
			'Divorced'             => L10n::t('Divorced'),
			'Imaginarily divorced' => L10n::t('Imaginarily divorced'),
			'Widowed'              => L10n::t('Widowed'),
			'Uncertain'            => L10n::t('Uncertain'),
			'It\'s complicated'    => L10n::t('It\'s complicated'),
			'Don\'t care'          => L10n::t('Don\'t care'),
			'Ask me'               => L10n::t('Ask me'),
		];

		Hook::callAll('marital_selector', $select);

		$o .= '<select name="marital" id="marital-select" size="1" >';
		foreach ($select as $neutral => $selection) {
			if ($selection !== 'NOTRANSLATION') {
				$selected = (($neutral == $current) ? ' selected="selected" ' : '');
				$o .= "<option value=\"$neutral\" $selected >$selection</option>";
			}
		}
		$o .= '</select>';
		return $o;
	}
}
