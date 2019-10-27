<?php

namespace Friendica\Module\Settings\Profile;

use Friendica\Content\ContactSelector;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\GContact;
use Friendica\Model\Profile as ProfileModel;
use Friendica\Model\User;
use Friendica\Module\BaseSettingsModule;
use Friendica\Module\Security\Login;
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
			notice(DI::l10n()->t('Profile Name is required.'));
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
				$contact_url = '';
				$lookup = $with;
				if (strpos($lookup, '@') === 0) {
					$lookup = substr($lookup, 1);
				}
				$lookup = str_replace('_', ' ', $lookup);
				if (strpos($lookup, '@') || (strpos($lookup, 'http://'))) {
					$contact_name = $lookup;
					$links = @Probe::lrdd($lookup);
					if (count($links)) {
						foreach ($links as $link) {
							if ($link['@attributes']['rel'] === 'http://webfinger.net/rel/profile-page') {
								$contact_url = $link['@attributes']['href'];
							}
						}
					}
				} else {
					$contact_name = $lookup;

					$contact = Contact::selectFirst(
						['url', 'name'],
						['? IN (`name`, `nick`) AND `uid` = ?', $lookup, local_user()]
					);

					if (DBA::isResult($contact)) {
						$contact_url = $contact['url'];
						$contact_name = $contact['name'];
					}
				}

				if ($contact_url) {
					$with = str_replace($lookup, '<a href="' . $contact_url . '">' . $contact_name . '</a>', $with);
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

		DI::pConfig()->set(local_user(), 'system', 'detailed_profile', intval(!empty($_POST['detailed_profile'])));

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
			info(DI::l10n()->t('Profile updated.'));
		} else {
			notice(DI::l10n()->t('Profile couldn\'t be updated.'));
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
			notice(DI::l10n()->t('You must be logged in to use this module'));
			return Login::form();
		}

		parent::content();

		$o = '';

		$profile = ProfileModel::getByUID(local_user());
		if (!DBA::isResult($profile)) {
			throw new HTTPException\NotFoundException();
		}

		$a = DI::app();

		DI::page()['htmlhead'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('settings/profile/index_head.tpl'), [
			'$baseurl' => DI::baseUrl()->get(true),
		]);

		$opt_tpl = Renderer::getMarkupTemplate('settings/profile/hide-friends.tpl');
		$hide_friends = Renderer::replaceMacros($opt_tpl, [
			'$yesno' => [
				'hide-friends', //Name
				DI::l10n()->t('Hide contacts and friends:'), //Label
				!!$profile['hide-friends'], //Value
				'', //Help string
				[DI::l10n()->t('No'), DI::l10n()->t('Yes')] //Off - On strings
			],
			'$desc' => DI::l10n()->t('Hide your contact/friend list from viewers of this profile?'),
			'$yes_str' => DI::l10n()->t('Yes'),
			'$no_str' => DI::l10n()->t('No'),
			'$yes_selected' => (($profile['hide-friends']) ? ' checked="checked"' : ''),
			'$no_selected' => (($profile['hide-friends'] == 0) ? ' checked="checked"' : '')
		]);

		$personal_account = !in_array($a->user['page-flags'], [User::PAGE_FLAGS_COMMUNITY, User::PAGE_FLAGS_PRVGROUP]);

		$detailed_profile =
			$personal_account
			&& DI::pConfig()->get(local_user(), 'system', 'detailed_profile',
				DI::pConfig()->get(local_user(), 'system', 'detailled_profile')
			)
		;

		$tpl = Renderer::getMarkupTemplate('settings/profile/index.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$personal_account' => $personal_account,
			'$detailed_profile' => $detailed_profile,

			'$details' => [
				'detailed_profile', //Name
				DI::l10n()->t('Show more profile fields:'), //Label
				$detailed_profile, //Value
				'', //Help string
				[DI::l10n()->t('No'), DI::l10n()->t('Yes')] //Off - On strings
			],

			'$form_security_token' => self::getFormSecurityToken('settings_profile'),
			'$form_security_token_photo' => self::getFormSecurityToken('settings_profile_photo'),

			'$profile_action' => DI::l10n()->t('Profile Actions'),
			'$banner' => DI::l10n()->t('Edit Profile Details'),
			'$submit' => DI::l10n()->t('Submit'),
			'$profpic' => DI::l10n()->t('Change Profile Photo'),
			'$profpiclink' => '/photos/' . $a->user['nickname'],
			'$viewprof' => DI::l10n()->t('View this profile'),
			'$viewallprof' => DI::l10n()->t('View all profiles'),
			'$editvis' => DI::l10n()->t('Edit visibility'),
			'$cr_prof' => DI::l10n()->t('Create a new profile using these settings'),
			'$cl_prof' => DI::l10n()->t('Clone this profile'),
			'$del_prof' => DI::l10n()->t('Delete this profile'),

			'$lbl_basic_section' => DI::l10n()->t('Basic information'),
			'$lbl_picture_section' => DI::l10n()->t('Profile picture'),
			'$lbl_location_section' => DI::l10n()->t('Location'),
			'$lbl_preferences_section' => DI::l10n()->t('Preferences'),
			'$lbl_status_section' => DI::l10n()->t('Status information'),
			'$lbl_about_section' => DI::l10n()->t('Additional information'),
			'$lbl_interests_section' => DI::l10n()->t('Interests'),
			'$lbl_personal_section' => DI::l10n()->t('Personal'),
			'$lbl_relation_section' => DI::l10n()->t('Relation'),
			'$lbl_miscellaneous_section' => DI::l10n()->t('Miscellaneous'),

			'$lbl_profile_photo' => DI::l10n()->t('Upload Profile Photo'),
			'$lbl_gender' => DI::l10n()->t('Your Gender:'),
			'$lbl_marital' => DI::l10n()->t('<span class="heart">&hearts;</span> Marital Status:'),
			'$lbl_sexual' => DI::l10n()->t('Sexual Preference:'),
			'$lbl_ex2' => DI::l10n()->t('Example: fishing photography software'),

			'$default' => '<p id="profile-edit-default-desc">' . DI::l10n()->t('This is your <strong>public</strong> profile.<br />It <strong>may</strong> be visible to anybody using the internet.') . '</p>',
			'$baseurl' => DI::baseUrl()->get(true),
			'$nickname' => $a->user['nickname'],
			'$name' => ['name', DI::l10n()->t('Display name:'), $profile['name']],
			'$pdesc' => ['pdesc', DI::l10n()->t('Title/Description:'), $profile['pdesc']],
			'$dob' => Temporal::getDateofBirthField($profile['dob'], $a->user['timezone']),
			'$hide_friends' => $hide_friends,
			'$address' => ['address', DI::l10n()->t('Street Address:'), $profile['address']],
			'$locality' => ['locality', DI::l10n()->t('Locality/City:'), $profile['locality']],
			'$region' => ['region', DI::l10n()->t('Region/State:'), $profile['region']],
			'$postal_code' => ['postal_code', DI::l10n()->t('Postal/Zip Code:'), $profile['postal-code']],
			'$country_name' => ['country_name', DI::l10n()->t('Country:'), $profile['country-name']],
			'$age' => ((intval($profile['dob'])) ? '(' . DI::l10n()->t('Age: ') . Temporal::getAgeByTimezone($profile['dob'], $a->user['timezone'], $a->user['timezone']) . ')' : ''),
			'$gender' => DI::l10n()->t(ContactSelector::gender($profile['gender'])),
			'$marital' => ['selector' => ContactSelector::maritalStatus($profile['marital']), 'value' => DI::l10n()->t($profile['marital'])],
			'$with' => ['with', DI::l10n()->t('Who: (if applicable)'), strip_tags($profile['with']), DI::l10n()->t('Examples: cathy123, Cathy Williams, cathy@example.com')],
			'$howlong' => ['howlong', DI::l10n()->t('Since [date]:'), ($profile['howlong'] <= DBA::NULL_DATETIME ? '' : DateTimeFormat::local($profile['howlong']))],
			'$sexual' => ['selector' => ContactSelector::sexualPreference($profile['sexual']), 'value' => DI::l10n()->t($profile['sexual'])],
			'$about' => ['about', DI::l10n()->t('Tell us about yourself...'), $profile['about']],
			'$xmpp' => ['xmpp', DI::l10n()->t('XMPP (Jabber) address:'), $profile['xmpp'], DI::l10n()->t('The XMPP address will be propagated to your contacts so that they can follow you.')],
			'$homepage' => ['homepage', DI::l10n()->t('Homepage URL:'), $profile['homepage']],
			'$hometown' => ['hometown', DI::l10n()->t('Hometown:'), $profile['hometown']],
			'$politic' => ['politic', DI::l10n()->t('Political Views:'), $profile['politic']],
			'$religion' => ['religion', DI::l10n()->t('Religious Views:'), $profile['religion']],
			'$pub_keywords' => ['pub_keywords', DI::l10n()->t('Public Keywords:'), $profile['pub_keywords'], DI::l10n()->t('(Used for suggesting potential friends, can be seen by others)')],
			'$prv_keywords' => ['prv_keywords', DI::l10n()->t('Private Keywords:'), $profile['prv_keywords'], DI::l10n()->t('(Used for searching profiles, never shown to others)')],
			'$likes' => ['likes', DI::l10n()->t('Likes:'), $profile['likes']],
			'$dislikes' => ['dislikes', DI::l10n()->t('Dislikes:'), $profile['dislikes']],
			'$music' => ['music', DI::l10n()->t('Musical interests'), $profile['music']],
			'$book' => ['book', DI::l10n()->t('Books, literature'), $profile['book']],
			'$tv' => ['tv', DI::l10n()->t('Television'), $profile['tv']],
			'$film' => ['film', DI::l10n()->t('Film/dance/culture/entertainment'), $profile['film']],
			'$interest' => ['interest', DI::l10n()->t('Hobbies/Interests'), $profile['interest']],
			'$romance' => ['romance', DI::l10n()->t('Love/romance'), $profile['romance']],
			'$work' => ['work', DI::l10n()->t('Work/employment'), $profile['work']],
			'$education' => ['education', DI::l10n()->t('School/education'), $profile['education']],
			'$contact' => ['contact', DI::l10n()->t('Contact information and Social Networks'), $profile['contact']],
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
