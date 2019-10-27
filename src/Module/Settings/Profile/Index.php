<?php

namespace Friendica\Module\Settings\Profile;

use Friendica\Content\ContactSelector;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\GContact;
use Friendica\Model\Profile as ProfileModel;
use Friendica\Model\User;
use Friendica\Module\BaseSettingsModule;
use Friendica\Module\Login;
use Friendica\Network\HTTPException;
use Friendica\Network\Probe;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;
use Friendica\Util\Temporal;

class Index extends BaseSettingsModule
{
	public static function post(array $parameters = [])
	{
		if (!local_user()) {
			return;
		}

		$profile = ProfileModel::getByUID(local_user());
		if (!DBA::isResult($profile)) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('/settings/profile', 'settings_profile');

		Hook::callAll('profile_post', $_POST);

		$dob = Strings::escapeHtml(trim($_POST['dob'] ?? '0000-00-00'));

		$y = substr($dob, 0, 4);
		if ((!ctype_digit($y)) || ($y < 1900)) {
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

		$name = Strings::escapeTags(trim($_POST['name'] ?? ''));
		if (!strlen($name)) {
			notice(L10n::t('Profile Name is required.'));
			return;
		}

		$namechanged = $profile['username'] != $name;

		$pdesc = Strings::escapeTags(trim($_POST['pdesc']));
		$gender = Strings::escapeTags(trim($_POST['gender']));
		$address = Strings::escapeTags(trim($_POST['address']));
		$locality = Strings::escapeTags(trim($_POST['locality']));
		$region = Strings::escapeTags(trim($_POST['region']));
		$postal_code = Strings::escapeTags(trim($_POST['postal_code']));
		$country_name = Strings::escapeTags(trim($_POST['country_name']));
		$pub_keywords = self::cleanKeywords(Strings::escapeTags(trim($_POST['pub_keywords'])));
		$prv_keywords = self::cleanKeywords(Strings::escapeTags(trim($_POST['prv_keywords'])));
		$marital = Strings::escapeTags(trim($_POST['marital']));
		$howlong = Strings::escapeTags(trim($_POST['howlong']));

		$with = (!empty($_POST['with']) ? Strings::escapeTags(trim($_POST['with'])) : '');

		if (!strlen($howlong)) {
			$howlong = DBA::NULL_DATETIME;
		} else {
			$howlong = DateTimeFormat::convert($howlong, 'UTC', date_default_timezone_get());
		}

		// linkify the relationship target if applicable
		if (strlen($with)) {
			if ($with != strip_tags($profile['with'])) {
				$prf = '';
				$lookup = $with;
				if (strpos($lookup, '@') === 0) {
					$lookup = substr($lookup, 1);
				}
				$lookup = str_replace('_', ' ', $lookup);
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

					$result = q("SELECT * FROM `contact` WHERE `name` = '%s' AND `uid` = %d LIMIT 1",
						DBA::escape($newname),
						intval(local_user())
					);
					if (!DBA::isResult($result)) {
						$result = q("SELECT * FROM `contact` WHERE `nick` = '%s' AND `uid` = %d LIMIT 1",
							DBA::escape($lookup),
							intval(local_user())
						);
					}
					if (DBA::isResult($result)) {
						$prf = $result[0]['url'];
						$newname = $result[0]['name'];
					}
				}

				if ($prf) {
					$with = str_replace($lookup, '<a href="' . $prf . '">' . $newname . '</a>', $with);
					if (strpos($with, '@') === 0) {
						$with = substr($with, 1);
					}
				}
			} else {
				$with = $profile['with'];
			}
		}

		/// @TODO Not flexible enough for later expansion, let's have more OOP here
		$sexual = Strings::escapeTags(trim($_POST['sexual']));
		$xmpp = Strings::escapeTags(trim($_POST['xmpp']));
		$homepage = Strings::escapeTags(trim($_POST['homepage']));
		if ((strpos($homepage, 'http') !== 0) && (strlen($homepage))) {
			// neither http nor https in URL, add them
			$homepage = 'http://' . $homepage;
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

		$hide_friends = intval(!empty($_POST['hide-friends']));

		PConfig::set(local_user(), 'system', 'detailed_profile', intval(!empty($_POST['detailed_profile'])));

		$result = DBA::update(
			'profile',
			[
				'name'         => $name,
				'pdesc'        => $pdesc,
				'gender'       => $gender,
				'dob'          => $dob,
				'address'      => $address,
				'locality'     => $locality,
				'region'       => $region,
				'postal-code'  => $postal_code,
				'country-name' => $country_name,
				'marital'      => $marital,
				'with'         => $with,
				'howlong'      => $howlong,
				'sexual'       => $sexual,
				'xmpp'         => $xmpp,
				'homepage'     => $homepage,
				'hometown'     => $hometown,
				'politic'      => $politic,
				'religion'     => $religion,
				'pub_keywords' => $pub_keywords,
				'prv_keywords' => $prv_keywords,
				'likes'        => $likes,
				'dislikes'     => $dislikes,
				'about'        => $about,
				'interest'     => $interest,
				'contact'      => $contact,
				'music'        => $music,
				'book'         => $book,
				'tv'           => $tv,
				'film'         => $film,
				'romance'      => $romance,
				'work'         => $work,
				'education'    => $education,
				'hide-friends' => $hide_friends,
			],
			[
				'uid' => local_user(),
				'is-default' => true,
			]
		);
		
		if ($result) {
			info(L10n::t('Profile updated.'));
		} else {
			notice(L10n::t('Profile couldn\'t be updated.'));
			return;
		}

		if ($namechanged) {
			DBA::update('user', ['username' => $name], ['uid' => local_user()]);
		}

		Contact::updateSelfFromUserID(local_user());

		// Update global directory in background
		if (Session::get('my_url') && strlen(Config::get('system', 'directory'))) {
			Worker::add(PRIORITY_LOW, 'Directory', Session::get('my_url'));
		}

		Worker::add(PRIORITY_LOW, 'ProfileUpdate', local_user());

		// Update the global contact for the user
		GContact::updateForUser(local_user());
	}

	public static function content(array $parameters = [])
	{
		if (!local_user()) {
			notice(L10n::t('You must be logged in to use this module'));
			return Login::form();
		}

		parent::content();

		$o = '';

		$profile = ProfileModel::getByUID(local_user());
		if (!DBA::isResult($profile)) {
			throw new HTTPException\NotFoundException();
		}

		$a = self::getApp();

		$a->page['htmlhead'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('settings/profile/index_head.tpl'), [
			'$baseurl' => System::baseUrl(true),
		]);

		$opt_tpl = Renderer::getMarkupTemplate('settings/profile/hide-friends.tpl');
		$hide_friends = Renderer::replaceMacros($opt_tpl, [
			'$yesno' => [
				'hide-friends', //Name
				L10n::t('Hide contacts and friends:'), //Label
				!!$profile['hide-friends'], //Value
				'', //Help string
				[L10n::t('No'), L10n::t('Yes')] //Off - On strings
			],
			'$desc' => L10n::t('Hide your contact/friend list from viewers of this profile?'),
			'$yes_str' => L10n::t('Yes'),
			'$no_str' => L10n::t('No'),
			'$yes_selected' => (($profile['hide-friends']) ? ' checked="checked"' : ''),
			'$no_selected' => (($profile['hide-friends'] == 0) ? ' checked="checked"' : '')
		]);

		$personal_account = !in_array($a->user['page-flags'], [User::PAGE_FLAGS_COMMUNITY, User::PAGE_FLAGS_PRVGROUP]);

		$detailed_profile =
			$personal_account
			&& PConfig::get(local_user(), 'system', 'detailed_profile',
				PConfig::get(local_user(), 'system', 'detailled_profile')
			)
		;

		$tpl = Renderer::getMarkupTemplate('settings/profile/index.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$personal_account' => $personal_account,
			'$detailed_profile' => $detailed_profile,

			'$details' => [
				'detailed_profile', //Name
				L10n::t('Show more profile fields:'), //Label
				$detailed_profile, //Value
				'', //Help string
				[L10n::t('No'), L10n::t('Yes')] //Off - On strings
			],

			'$form_security_token' => self::getFormSecurityToken('settings_profile'),
			'$form_security_token_photo' => self::getFormSecurityToken('settings_profile_photo'),

			'$profile_action' => L10n::t('Profile Actions'),
			'$banner' => L10n::t('Edit Profile Details'),
			'$submit' => L10n::t('Submit'),
			'$profpic' => L10n::t('Change Profile Photo'),
			'$profpiclink' => '/photos/' . $a->user['nickname'],
			'$viewprof' => L10n::t('View this profile'),
			'$viewallprof' => L10n::t('View all profiles'),
			'$editvis' => L10n::t('Edit visibility'),
			'$cr_prof' => L10n::t('Create a new profile using these settings'),
			'$cl_prof' => L10n::t('Clone this profile'),
			'$del_prof' => L10n::t('Delete this profile'),

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

			'$default' => '<p id="profile-edit-default-desc">' . L10n::t('This is your <strong>public</strong> profile.<br />It <strong>may</strong> be visible to anybody using the internet.') . '</p>',
			'$baseurl' => System::baseUrl(true),
			'$nickname' => self::getApp()->user['nickname'],
			'$name' => ['name', L10n::t('Display name:'), $profile['name']],
			'$pdesc' => ['pdesc', L10n::t('Title/Description:'), $profile['pdesc']],
			'$dob' => Temporal::getDateofBirthField($profile['dob'], $a->user['timezone']),
			'$hide_friends' => $hide_friends,
			'$address' => ['address', L10n::t('Street Address:'), $profile['address']],
			'$locality' => ['locality', L10n::t('Locality/City:'), $profile['locality']],
			'$region' => ['region', L10n::t('Region/State:'), $profile['region']],
			'$postal_code' => ['postal_code', L10n::t('Postal/Zip Code:'), $profile['postal-code']],
			'$country_name' => ['country_name', L10n::t('Country:'), $profile['country-name']],
			'$age' => ((intval($profile['dob'])) ? '(' . L10n::t('Age: ') . Temporal::getAgeByTimezone($profile['dob'], $a->user['timezone'], $a->user['timezone']) . ')' : ''),
			'$gender' => L10n::t(ContactSelector::gender($profile['gender'])),
			'$marital' => ['selector' => ContactSelector::maritalStatus($profile['marital']), 'value' => L10n::t($profile['marital'])],
			'$with' => ['with', L10n::t('Who: (if applicable)'), strip_tags($profile['with']), L10n::t('Examples: cathy123, Cathy Williams, cathy@example.com')],
			'$howlong' => ['howlong', L10n::t('Since [date]:'), ($profile['howlong'] <= DBA::NULL_DATETIME ? '' : DateTimeFormat::local($profile['howlong']))],
			'$sexual' => ['selector' => ContactSelector::sexualPreference($profile['sexual']), 'value' => L10n::t($profile['sexual'])],
			'$about' => ['about', L10n::t('Tell us about yourself...'), $profile['about']],
			'$xmpp' => ['xmpp', L10n::t('XMPP (Jabber) address:'), $profile['xmpp'], L10n::t('The XMPP address will be propagated to your contacts so that they can follow you.')],
			'$homepage' => ['homepage', L10n::t('Homepage URL:'), $profile['homepage']],
			'$hometown' => ['hometown', L10n::t('Hometown:'), $profile['hometown']],
			'$politic' => ['politic', L10n::t('Political Views:'), $profile['politic']],
			'$religion' => ['religion', L10n::t('Religious Views:'), $profile['religion']],
			'$pub_keywords' => ['pub_keywords', L10n::t('Public Keywords:'), $profile['pub_keywords'], L10n::t('(Used for suggesting potential friends, can be seen by others)')],
			'$prv_keywords' => ['prv_keywords', L10n::t('Private Keywords:'), $profile['prv_keywords'], L10n::t('(Used for searching profiles, never shown to others)')],
			'$likes' => ['likes', L10n::t('Likes:'), $profile['likes']],
			'$dislikes' => ['dislikes', L10n::t('Dislikes:'), $profile['dislikes']],
			'$music' => ['music', L10n::t('Musical interests'), $profile['music']],
			'$book' => ['book', L10n::t('Books, literature'), $profile['book']],
			'$tv' => ['tv', L10n::t('Television'), $profile['tv']],
			'$film' => ['film', L10n::t('Film/dance/culture/entertainment'), $profile['film']],
			'$interest' => ['interest', L10n::t('Hobbies/Interests'), $profile['interest']],
			'$romance' => ['romance', L10n::t('Love/romance'), $profile['romance']],
			'$work' => ['work', L10n::t('Work/employment'), $profile['work']],
			'$education' => ['education', L10n::t('School/education'), $profile['education']],
			'$contact' => ['contact', L10n::t('Contact information and Social Networks'), $profile['contact']],
		]);

		$arr = ['profile' => $profile, 'entry' => $o];
		Hook::callAll('profile_edit', $arr);

		return $o;
	}

	private static function cleanKeywords($keywords)
	{
		$keywords = str_replace(',', ' ', $keywords);
		$keywords = explode(' ', $keywords);

		$cleaned = [];
		foreach ($keywords as $keyword) {
			$keyword = trim(strtolower($keyword));
			$keyword = trim($keyword, '#');
			if ($keyword != '') {
				$cleaned[] = $keyword;
			}
		}

		$keywords = implode(', ', $cleaned);

		return $keywords;
	}
}
