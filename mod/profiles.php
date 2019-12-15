<?php
/**
 * @file mod/profiles.php
 */

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\ContactSelector;
use Friendica\Content\Feature;
use Friendica\Content\Nav;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\GContact;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Module\Login;
use Friendica\Network\Probe;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;
use Friendica\Util\Temporal;

function profiles_init(App $a) {

	Nav::setSelected('profiles');

	if (! local_user()) {
		return;
	}

	if (($a->argc > 2) && ($a->argv[1] === "drop") && intval($a->argv[2])) {
		$r = q("SELECT * FROM `profile` WHERE `id` = %d AND `uid` = %d AND `is-default` = 0 LIMIT 1",
			intval($a->argv[2]),
			intval(local_user())
		);
		if (! DBA::isResult($r)) {
			notice(L10n::t('Profile not found.') . EOL);
			DI::baseUrl()->redirect('profiles');
			return; // NOTREACHED
		}

		BaseModule::checkFormSecurityTokenRedirectOnError('/profiles', 'profile_drop', 't');

		// move every contact using this profile as their default to the user default

		q("UPDATE `contact` SET `profile-id` = (SELECT `profile`.`id` AS `profile-id` FROM `profile` WHERE `profile`.`is-default` = 1 AND `profile`.`uid` = %d LIMIT 1) WHERE `profile-id` = %d AND `uid` = %d ",
			intval(local_user()),
			intval($a->argv[2]),
			intval(local_user())
		);
		q("DELETE FROM `profile` WHERE `id` = %d AND `uid` = %d",
			intval($a->argv[2]),
			intval(local_user())
		);
		if (DBA::isResult($r)) {
			info(L10n::t('Profile deleted.').EOL);
		}

		DI::baseUrl()->redirect('profiles');
		return; // NOTREACHED
	}

	if (($a->argc > 1) && ($a->argv[1] === 'new')) {

		BaseModule::checkFormSecurityTokenRedirectOnError('/profiles', 'profile_new', 't');

		$r0 = q("SELECT `id` FROM `profile` WHERE `uid` = %d",
			intval(local_user()));

		$num_profiles = (DBA::isResult($r0) ? count($r0) : 0);

		$name = L10n::t('Profile-') . ($num_profiles + 1);

		$r1 = q("SELECT `name`, `photo`, `thumb` FROM `profile` WHERE `uid` = %d AND `is-default` = 1 LIMIT 1",
			intval(local_user()));

		q("INSERT INTO `profile` (`uid` , `profile-name` , `name`, `photo`, `thumb`)
			VALUES ( %d, '%s', '%s', '%s', '%s' )",
			intval(local_user()),
			DBA::escape($name),
			DBA::escape($r1[0]['name']),
			DBA::escape($r1[0]['photo']),
			DBA::escape($r1[0]['thumb'])
		);

		$r3 = q("SELECT `id` FROM `profile` WHERE `uid` = %d AND `profile-name` = '%s' LIMIT 1",
			intval(local_user()),
			DBA::escape($name)
		);

		info(L10n::t('New profile created.') . EOL);
		if (DBA::isResult($r3) && count($r3) == 1) {
			DI::baseUrl()->redirect('profiles/' . $r3[0]['id']);
		}

		DI::baseUrl()->redirect('profiles');
	}

	if (($a->argc > 2) && ($a->argv[1] === 'clone')) {

		BaseModule::checkFormSecurityTokenRedirectOnError('/profiles', 'profile_clone', 't');

		$r0 = q("SELECT `id` FROM `profile` WHERE `uid` = %d",
			intval(local_user()));

		$num_profiles = (DBA::isResult($r0) ? count($r0) : 0);

		$name = L10n::t('Profile-') . ($num_profiles + 1);
		$r1 = q("SELECT * FROM `profile` WHERE `uid` = %d AND `id` = %d LIMIT 1",
			intval(local_user()),
			intval($a->argv[2])
		);
		if(! DBA::isResult($r1)) {
			notice(L10n::t('Profile unavailable to clone.') . EOL);
			exit();
		}
		unset($r1[0]['id']);
		$r1[0]['is-default'] = 0;
		$r1[0]['publish'] = 0;
		$r1[0]['net-publish'] = 0;
		$r1[0]['profile-name'] = DBA::escape($name);

		DBA::insert('profile', $r1[0]);

		$r3 = q("SELECT `id` FROM `profile` WHERE `uid` = %d AND `profile-name` = '%s' LIMIT 1",
			intval(local_user()),
			DBA::escape($name)
		);
		info(L10n::t('New profile created.') . EOL);
		if ((DBA::isResult($r3)) && (count($r3) == 1)) {
			DI::baseUrl()->redirect('profiles/'.$r3[0]['id']);
		}

		DI::baseUrl()->redirect('profiles');

		return; // NOTREACHED
	}


	if (($a->argc > 1) && (intval($a->argv[1]))) {
		$r = q("SELECT id FROM `profile` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($a->argv[1]),
			intval(local_user())
		);
		if (! DBA::isResult($r)) {
			notice(L10n::t('Profile not found.') . EOL);
			exit();
		}

		Profile::load($a, $a->user['nickname'], $r[0]['id']);
	}
}

function profile_clean_keywords($keywords)
{
	$keywords = str_replace(",", " ", $keywords);
	$keywords = explode(" ", $keywords);

	$cleaned = [];
	foreach ($keywords as $keyword) {
		$keyword = trim(strtolower($keyword));
		$keyword = trim($keyword, "#");
		if ($keyword != "") {
			$cleaned[] = $keyword;
		}
	}

	$keywords = implode(", ", $cleaned);

	return $keywords;
}

function profiles_post(App $a) {

	if (! local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	$namechanged = false;

	Hook::callAll('profile_post', $_POST);

	if (($a->argc > 1) && ($a->argv[1] !== "new") && intval($a->argv[1])) {
		$orig = q("SELECT * FROM `profile` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($a->argv[1]),
			intval(local_user())
		);
		if (! DBA::isResult($orig)) {
			notice(L10n::t('Profile not found.') . EOL);
			return;
		}

		BaseModule::checkFormSecurityTokenRedirectOnError('/profiles', 'profile_edit');

		$is_default = (($orig[0]['is-default']) ? 1 : 0);

		$profile_name = Strings::escapeTags(trim($_POST['profile_name']));
		if (! strlen($profile_name)) {
			notice(L10n::t('Profile Name is required.') . EOL);
			return;
		}

		$dob = $_POST['dob'] ? Strings::escapeHtml(trim($_POST['dob'])) : '0000-00-00';

		$y = substr($dob, 0, 4);
		if ((! ctype_digit($y)) || ($y < 1900)) {
			$ignore_year = true;
		} else {
			$ignore_year = false;
		}
		if (!in_array($dob, ['0000-00-00', DBA::NULL_DATE])) {
			if (strpos($dob, '0000-') === 0 || strpos($dob, '0001-') === 0) {
				$ignore_year = true;
				$dob = substr($dob, 5);
			}

			if ($ignore_year) {
				$dob = '0000-' . DateTimeFormat::utc('1900-' . $dob, 'm-d');
			} else {
				$dob = DateTimeFormat::utc($dob, 'Y-m-d');
			}
		}

		$name = Strings::escapeTags(trim($_POST['name']));

		if (! strlen($name)) {
			$name = '[No Name]';
		}

		if ($orig[0]['name'] != $name) {
			$namechanged = true;
		}

		$pdesc = Strings::escapeTags(trim($_POST['pdesc']));
		$gender = Strings::escapeTags(trim($_POST['gender']));
		$address = Strings::escapeTags(trim($_POST['address']));
		$locality = Strings::escapeTags(trim($_POST['locality']));
		$region = Strings::escapeTags(trim($_POST['region']));
		$postal_code = Strings::escapeTags(trim($_POST['postal_code']));
		$country_name = Strings::escapeTags(trim($_POST['country_name']));
		$pub_keywords = profile_clean_keywords(Strings::escapeTags(trim($_POST['pub_keywords'])));
		$prv_keywords = profile_clean_keywords(Strings::escapeTags(trim($_POST['prv_keywords'])));
		$marital = Strings::escapeTags(trim($_POST['marital']));
		$howlong = Strings::escapeTags(trim($_POST['howlong']));

		$with = (!empty($_POST['with']) ? Strings::escapeTags(trim($_POST['with'])) : '');

		if (! strlen($howlong)) {
			$howlong = DBA::NULL_DATETIME;
		} else {
			$howlong = DateTimeFormat::convert($howlong, 'UTC', date_default_timezone_get());
		}
		// linkify the relationship target if applicable

		$withchanged = false;

		if (strlen($with)) {
			if ($with != strip_tags($orig[0]['with'])) {
				$withchanged = true;
				$prf = '';
				$lookup = $with;
				if (strpos($lookup, '@') === 0) {
					$lookup = substr($lookup, 1);
				}
				$lookup = str_replace('_',' ', $lookup);
				if (strpos($lookup, '@') || (strpos($lookup, 'http://'))) {
					$newname = $lookup;
					$links = @Probe::lrdd($lookup);
					if (count($links)) {
						foreach ($links as $link) {
							if ($link['@attributes']['rel'] === 'http://webfinger.net/rel/profile-page') {
								$prf = $link['@attributes']['href'];
							}
						}
					}
				} else {
					$newname = $lookup;

					$r = q("SELECT * FROM `contact` WHERE `name` = '%s' AND `uid` = %d LIMIT 1",
						DBA::escape($newname),
						intval(local_user())
					);
					if (! DBA::isResult($r)) {
						$r = q("SELECT * FROM `contact` WHERE `nick` = '%s' AND `uid` = %d LIMIT 1",
							DBA::escape($lookup),
							intval(local_user())
						);
					}
					if (DBA::isResult($r)) {
						$prf = $r[0]['url'];
						$newname = $r[0]['name'];
					}
				}

				if ($prf) {
					$with = str_replace($lookup, '<a href="' . $prf . '">' . $newname . '</a>', $with);
					if (strpos($with, '@') === 0) {
						$with = substr($with, 1);
					}
				}
			} else {
				$with = $orig[0]['with'];
			}
		}

		/// @TODO Not flexible enough for later expansion, let's have more OOP here
		$sexual = Strings::escapeTags(trim($_POST['sexual']));
		$xmpp = Strings::escapeTags(trim($_POST['xmpp']));
		$homepage = Strings::escapeTags(trim($_POST['homepage']));
		if ((strpos($homepage, 'http') !== 0) && (strlen($homepage))) {
			// neither http nor https in URL, add them
			$homepage = 'http://'.$homepage;
		}
		$hometown = Strings::escapeTags(trim($_POST['hometown']));
		$politic = Strings::escapeTags(trim($_POST['politic']));
		$religion = Strings::escapeTags(trim($_POST['religion']));

		$likes = Strings::escapeHtml(trim($_POST['likes']));
		$dislikes = Strings::escapeHtml(trim($_POST['dislikes']));

		$about = Strings::escapeHtml(trim($_POST['about']));
		$interest = Strings::escapeHtml(trim($_POST['interest']));
		$contact = Strings::escapeHtml(trim($_POST['contact']));
		$music = Strings::escapeHtml(trim($_POST['music']));
		$book = Strings::escapeHtml(trim($_POST['book']));
		$tv = Strings::escapeHtml(trim($_POST['tv']));
		$film = Strings::escapeHtml(trim($_POST['film']));
		$romance = Strings::escapeHtml(trim($_POST['romance']));
		$work = Strings::escapeHtml(trim($_POST['work']));
		$education = Strings::escapeHtml(trim($_POST['education']));

		$hide_friends = (($_POST['hide-friends'] == 1) ? 1: 0);

		PConfig::set(local_user(), 'system', 'detailled_profile', (($_POST['detailed_profile'] == 1) ? 1: 0));

		$changes = [];
		if ($is_default) {
			if ($marital != $orig[0]['marital']) {
				$changes[] = '[color=#ff0000]&hearts;[/color] ' . L10n::t('Marital Status');
			}
			if ($withchanged) {
				$changes[] = '[color=#ff0000]&hearts;[/color] ' . L10n::t('Romantic Partner');
			}
			if ($likes != $orig[0]['likes']) {
				$changes[] = L10n::t('Likes');
			}
			if ($dislikes != $orig[0]['dislikes']) {
				$changes[] = L10n::t('Dislikes');
			}
			if ($work != $orig[0]['work']) {
				$changes[] = L10n::t('Work/Employment');
			}
			if ($religion != $orig[0]['religion']) {
				$changes[] = L10n::t('Religion');
			}
			if ($politic != $orig[0]['politic']) {
				$changes[] = L10n::t('Political Views');
			}
			if ($gender != $orig[0]['gender']) {
				$changes[] = L10n::t('Gender');
			}
			if ($sexual != $orig[0]['sexual']) {
				$changes[] = L10n::t('Sexual Preference');
			}
			if ($xmpp != $orig[0]['xmpp']) {
				$changes[] = L10n::t('XMPP');
			}
			if ($homepage != $orig[0]['homepage']) {
				$changes[] = L10n::t('Homepage');
			}
			if ($interest != $orig[0]['interest']) {
				$changes[] = L10n::t('Interests');
			}
			if ($address != $orig[0]['address']) {
				$changes[] = L10n::t('Address');
				// New address not sent in notifications, potential privacy issues
				// in case this leaks to unintended recipients. Yes, it's in the public
				// profile but that doesn't mean we have to broadcast it to everybody.
			}
			if ($locality != $orig[0]['locality'] || $region != $orig[0]['region']
				|| $country_name != $orig[0]['country-name']) {
 				$changes[] = L10n::t('Location');
			}
		}

		$r = q("UPDATE `profile`
			SET `profile-name` = '%s',
			`name` = '%s',
			`pdesc` = '%s',
			`gender` = '%s',
			`dob` = '%s',
			`address` = '%s',
			`locality` = '%s',
			`region` = '%s',
			`postal-code` = '%s',
			`country-name` = '%s',
			`marital` = '%s',
			`with` = '%s',
			`howlong` = '%s',
			`sexual` = '%s',
			`xmpp` = '%s',
			`homepage` = '%s',
			`hometown` = '%s',
			`politic` = '%s',
			`religion` = '%s',
			`pub_keywords` = '%s',
			`prv_keywords` = '%s',
			`likes` = '%s',
			`dislikes` = '%s',
			`about` = '%s',
			`interest` = '%s',
			`contact` = '%s',
			`music` = '%s',
			`book` = '%s',
			`tv` = '%s',
			`film` = '%s',
			`romance` = '%s',
			`work` = '%s',
			`education` = '%s',
			`hide-friends` = %d
			WHERE `id` = %d AND `uid` = %d",
			DBA::escape($profile_name),
			DBA::escape($name),
			DBA::escape($pdesc),
			DBA::escape($gender),
			DBA::escape($dob),
			DBA::escape($address),
			DBA::escape($locality),
			DBA::escape($region),
			DBA::escape($postal_code),
			DBA::escape($country_name),
			DBA::escape($marital),
			DBA::escape($with),
			DBA::escape($howlong),
			DBA::escape($sexual),
			DBA::escape($xmpp),
			DBA::escape($homepage),
			DBA::escape($hometown),
			DBA::escape($politic),
			DBA::escape($religion),
			DBA::escape($pub_keywords),
			DBA::escape($prv_keywords),
			DBA::escape($likes),
			DBA::escape($dislikes),
			DBA::escape($about),
			DBA::escape($interest),
			DBA::escape($contact),
			DBA::escape($music),
			DBA::escape($book),
			DBA::escape($tv),
			DBA::escape($film),
			DBA::escape($romance),
			DBA::escape($work),
			DBA::escape($education),
			intval($hide_friends),
			intval($a->argv[1]),
			intval(local_user())
		);

		/// @TODO decide to use DBA::isResult() here and check $r
		if ($r) {
			info(L10n::t('Profile updated.') . EOL);
		}

		if ($is_default) {
			if ($namechanged) {
				q("UPDATE `user` set `username` = '%s' where `uid` = %d",
					DBA::escape($name),
					intval(local_user())
				);
			}

			Contact::updateSelfFromUserID(local_user());

			// Update global directory in background
			$url = $_SESSION['my_url'];
			if ($url && strlen(Config::get('system', 'directory'))) {
				Worker::add(PRIORITY_LOW, "Directory", $url);
			}

			Worker::add(PRIORITY_LOW, 'ProfileUpdate', local_user());

			// Update the global contact for the user
			GContact::updateForUser(local_user());
		}
	}
}

function profiles_content(App $a) {

	if (! local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return Login::form();
	}

	$o = '';

	if (($a->argc > 1) && (intval($a->argv[1]))) {
		$r = q("SELECT * FROM `profile` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($a->argv[1]),
			intval(local_user())
		);
		if (! DBA::isResult($r)) {
			notice(L10n::t('Profile not found.') . EOL);
			return;
		}

		$a->page['htmlhead'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('profed_head.tpl'), [
			'$baseurl' => System::baseUrl(true),
		]);

		$opt_tpl = Renderer::getMarkupTemplate("profile-hide-friends.tpl");
		$hide_friends = Renderer::replaceMacros($opt_tpl,[
			'$yesno' => [
				'hide-friends', //Name
				L10n::t('Hide contacts and friends:'), //Label
				!!$r[0]['hide-friends'], //Value
				'', //Help string
				[L10n::t('No'), L10n::t('Yes')] //Off - On strings
			],
			'$desc' => L10n::t('Hide your contact/friend list from viewers of this profile?'),
			'$yes_str' => L10n::t('Yes'),
			'$no_str' => L10n::t('No'),
			'$yes_selected' => (($r[0]['hide-friends']) ? " checked=\"checked\" " : ""),
			'$no_selected' => (($r[0]['hide-friends'] == 0) ? " checked=\"checked\" " : "")
		]);

		$personal_account = !(in_array($a->user["page-flags"],
					[User::PAGE_FLAGS_COMMUNITY, User::PAGE_FLAGS_PRVGROUP]));

		$detailed_profile = (PConfig::get(local_user(), 'system', 'detailled_profile') AND $personal_account);

		$is_default = (($r[0]['is-default']) ? 1 : 0);
		$tpl = Renderer::getMarkupTemplate("profile_edit.tpl");
		$o .= Renderer::replaceMacros($tpl, [
			'$personal_account' => $personal_account,
			'$detailled_profile' => $detailed_profile,

			'$details' => [
				'detailed_profile', //Name
				L10n::t('Show more profile fields:'), //Label
				$detailed_profile, //Value
				'', //Help string
				[L10n::t('No'), L10n::t('Yes')] //Off - On strings
			],

			'$multi_profiles'		=> Feature::isEnabled(local_user(), 'multi_profiles'),
			'$form_security_token'		=> BaseModule::getFormSecurityToken("profile_edit"),
			'$form_security_token_photo'	=> BaseModule::getFormSecurityToken("profile_photo"),
			'$profile_clone_link'		=> ((Feature::isEnabled(local_user(), 'multi_profiles')) ? 'profiles/clone/' . $r[0]['id'] . '?t=' . BaseModule::getFormSecurityToken("profile_clone") : ""),
			'$profile_drop_link'		=> 'profiles/drop/' . $r[0]['id'] . '?t=' . BaseModule::getFormSecurityToken("profile_drop"),

			'$profile_action' => L10n::t('Profile Actions'),
			'$banner'	=> L10n::t('Edit Profile Details'),
			'$submit'	=> L10n::t('Submit'),
			'$profpic'	=> L10n::t('Change Profile Photo'),
			'$profpiclink'	=> '/photos/' . $a->user['nickname'],
			'$viewprof'	=> L10n::t('View this profile'),
			'$viewallprof'	=> L10n::t('View all profiles'),
			'$editvis' 	=> L10n::t('Edit visibility'),
			'$cr_prof'	=> L10n::t('Create a new profile using these settings'),
			'$cl_prof'	=> L10n::t('Clone this profile'),
			'$del_prof'	=> L10n::t('Delete this profile'),

			'$lbl_basic_section' => L10n::t('Basic information'),
			'$lbl_picture_section' => L10n::t('Profile picture'),
			'$lbl_location_section' => L10n::t('Location'),
			'$lbl_preferences_section' => L10n::t('Preferences'),
			'$lbl_status_section' => L10n::t('Status information'),
			'$lbl_about_section' => L10n::t('Additional information'),
			'$lbl_interests_section' => L10n::t('Interests'),
			'$lbl_personal_section' => L10n::t('Personal'),
			'$lbl_relation_section' => L10n::t('Relation'),
			'$lbl_miscellaneous_section' => L10n::t('Miscellaneous'),

			'$lbl_profile_photo' => L10n::t('Upload Profile Photo'),
			'$lbl_gender' => L10n::t('Your Gender:'),
			'$lbl_marital' => L10n::t('<span class="heart">&hearts;</span> Marital Status:'),
			'$lbl_sexual' => L10n::t('Sexual Preference:'),
			'$lbl_ex2' => L10n::t('Example: fishing photography software'),

			'$disabled' => (($is_default) ? 'onclick="return false;" style="color: #BBBBFF;"' : ''),
			'$baseurl' => System::baseUrl(true),
			'$profile_id' => $r[0]['id'],
			'$profile_name' => ['profile_name', L10n::t('Profile Name:'), $r[0]['profile-name'], L10n::t('Required'), '*'],
			'$is_default'   => $is_default,
			'$default' => (($is_default) ? '<p id="profile-edit-default-desc">' . L10n::t('This is your <strong>public</strong> profile.<br />It <strong>may</strong> be visible to anybody using the internet.') . '</p>' : ""),
			'$name' => ['name', L10n::t('Your Full Name:'), $r[0]['name']],
			'$pdesc' => ['pdesc', L10n::t('Title/Description:'), $r[0]['pdesc']],
			'$dob' => Temporal::getDateofBirthField($r[0]['dob'], $a->user['timezone']),
			'$hide_friends' => $hide_friends,
			'$address' => ['address', L10n::t('Street Address:'), $r[0]['address']],
			'$locality' => ['locality', L10n::t('Locality/City:'), $r[0]['locality']],
			'$region' => ['region', L10n::t('Region/State:'), $r[0]['region']],
			'$postal_code' => ['postal_code', L10n::t('Postal/Zip Code:'), $r[0]['postal-code']],
			'$country_name' => ['country_name', L10n::t('Country:'), $r[0]['country-name']],
			'$age' => ((intval($r[0]['dob'])) ? '(' . L10n::t('Age: ') . Temporal::getAgeByTimezone($r[0]['dob'],$a->user['timezone'],$a->user['timezone']) . ')' : ''),
			'$gender' => L10n::t(ContactSelector::gender($r[0]['gender'])),
			'$marital' => ['selector' => ContactSelector::maritalStatus($r[0]['marital']), 'value' => L10n::t($r[0]['marital'])],
			'$with' => ['with', L10n::t("Who: \x28if applicable\x29"), strip_tags($r[0]['with']), L10n::t('Examples: cathy123, Cathy Williams, cathy@example.com')],
			'$howlong' => ['howlong', L10n::t('Since [date]:'), ($r[0]['howlong'] <= DBA::NULL_DATETIME ? '' : DateTimeFormat::local($r[0]['howlong']))],
			'$sexual' => ['selector' => ContactSelector::sexualPreference($r[0]['sexual']), 'value' => L10n::t($r[0]['sexual'])],
			'$about' => ['about', L10n::t('Tell us about yourself...'), $r[0]['about']],
			'$xmpp' => ['xmpp', L10n::t("XMPP \x28Jabber\x29 address:"), $r[0]['xmpp'], L10n::t("The XMPP address will be propagated to your contacts so that they can follow you.")],
			'$homepage' => ['homepage', L10n::t('Homepage URL:'), $r[0]['homepage']],
			'$hometown' => ['hometown', L10n::t('Hometown:'), $r[0]['hometown']],
			'$politic' => ['politic', L10n::t('Political Views:'), $r[0]['politic']],
			'$religion' => ['religion', L10n::t('Religious Views:'), $r[0]['religion']],
			'$pub_keywords' => ['pub_keywords', L10n::t('Public Keywords:'), $r[0]['pub_keywords'], L10n::t("\x28Used for suggesting potential friends, can be seen by others\x29")],
			'$prv_keywords' => ['prv_keywords', L10n::t('Private Keywords:'), $r[0]['prv_keywords'], L10n::t("\x28Used for searching profiles, never shown to others\x29")],
			'$likes' => ['likes', L10n::t('Likes:'), $r[0]['likes']],
			'$dislikes' => ['dislikes', L10n::t('Dislikes:'), $r[0]['dislikes']],
			'$music' => ['music', L10n::t('Musical interests'), $r[0]['music']],
			'$book' => ['book', L10n::t('Books, literature'), $r[0]['book']],
			'$tv' => ['tv', L10n::t('Television'), $r[0]['tv']],
			'$film' => ['film', L10n::t('Film/dance/culture/entertainment'), $r[0]['film']],
			'$interest' => ['interest', L10n::t('Hobbies/Interests'), $r[0]['interest']],
			'$romance' => ['romance', L10n::t('Love/romance'), $r[0]['romance']],
			'$work' => ['work', L10n::t('Work/employment'), $r[0]['work']],
			'$education' => ['education', L10n::t('School/education'), $r[0]['education']],
			'$contact' => ['contact', L10n::t('Contact information and Social Networks'), $r[0]['contact']],
		]);

		$arr = ['profile' => $r[0], 'entry' => $o];
		Hook::callAll('profile_edit', $arr);

		return $o;
	} else {
		// If we don't support multi profiles, don't display this list.
		if (!Feature::isEnabled(local_user(), 'multi_profiles')) {
			$r = q("SELECT * FROM `profile` WHERE `uid` = %d AND `is-default`=1",
				local_user()
			);
			if (DBA::isResult($r)) {
				//Go to the default profile.
				DI::baseUrl()->redirect('profiles/' . $r[0]['id']);
			}
		}

		$r = q("SELECT * FROM `profile` WHERE `uid` = %d",
			local_user());

		if (DBA::isResult($r)) {

			$tpl = Renderer::getMarkupTemplate('profile_entry.tpl');

			$profiles = '';
			foreach ($r as $rr) {
				$profiles .= Renderer::replaceMacros($tpl, [
					'$photo'        => $a->removeBaseURL($rr['thumb']),
					'$id'           => $rr['id'],
					'$alt'          => L10n::t('Profile Image'),
					'$profile_name' => $rr['profile-name'],
					'$visible'      => (($rr['is-default']) ? '<strong>' . L10n::t('visible to everybody') . '</strong>'
						: '<a href="'.'profperm/'.$rr['id'].'" />' . L10n::t('Edit visibility') . '</a>')
				]);
			}

			$tpl_header = Renderer::getMarkupTemplate('profile_listing_header.tpl');
			$o .= Renderer::replaceMacros($tpl_header,[
				'$header'      => L10n::t('Edit/Manage Profiles'),
				'$chg_photo'   => L10n::t('Change profile photo'),
				'$cr_new'      => L10n::t('Create New Profile'),
				'$cr_new_link' => 'profiles/new?t=' . BaseModule::getFormSecurityToken("profile_new"),
				'$profiles'    => $profiles
			]);
		}
		return $o;
	}

}
