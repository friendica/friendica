<?php
require_once("include/Contact.php");
require_once('include/Probe.php');

function profiles_init(App &$a) {

	nav_set_selected('profiles');

	if (! local_user()) {
		return;
	}

	if(($a->argc > 2) && ($a->argv[1] === "drop") && intval($a->argv[2])) {
		$r = q("SELECT * FROM `profile` WHERE `id` = %d AND `uid` = %d AND `is-default` = 0 LIMIT 1",
			intval($a->argv[2]),
			intval(local_user())
		);
		if (! dbm::is_result($r)) {
			notice( t('Profile not found.') . EOL);
			goaway('profiles');
			return; // NOTREACHED
		}

		check_form_security_token_redirectOnErr('/profiles', 'profile_drop', 't');

		// move every contact using this profile as their default to the user default

		$r = q("UPDATE `contact` SET `profile-id` = (SELECT `profile`.`id` AS `profile-id` FROM `profile` WHERE `profile`.`is-default` = 1 AND `profile`.`uid` = %d LIMIT 1) WHERE `profile-id` = %d AND `uid` = %d ",
			intval(local_user()),
			intval($a->argv[2]),
			intval(local_user())
		);
		$r = q("DELETE FROM `profile` WHERE `id` = %d AND `uid` = %d",
			intval($a->argv[2]),
			intval(local_user())
		);
		if($r)
			info(t('Profile deleted.').EOL);

		goaway('profiles');
		return; // NOTREACHED
	}





	if(($a->argc > 1) && ($a->argv[1] === 'new')) {

		check_form_security_token_redirectOnErr('/profiles', 'profile_new', 't');

		$r0 = q("SELECT `id` FROM `profile` WHERE `uid` = %d",
			intval(local_user()));
		$num_profiles = count($r0);

		$name = t('Profile-') . ($num_profiles + 1);

		$r1 = q("SELECT `name`, `photo`, `thumb` FROM `profile` WHERE `uid` = %d AND `is-default` = 1 LIMIT 1",
			intval(local_user()));

		$r2 = q("INSERT INTO `profile` (`uid` , `profile-name` , `name`, `photo`, `thumb`)
			VALUES ( %d, '%s', '%s', '%s', '%s' )",
			intval(local_user()),
			dbesc($name),
			dbesc($r1[0]['name']),
			dbesc($r1[0]['photo']),
			dbesc($r1[0]['thumb'])
		);

		$r3 = q("SELECT `id` FROM `profile` WHERE `uid` = %d AND `profile-name` = '%s' LIMIT 1",
			intval(local_user()),
			dbesc($name)
		);

		info( t('New profile created.') . EOL);
		if(count($r3) == 1)
			goaway('profiles/'.$r3[0]['id']);

		goaway('profiles');
	}

	if(($a->argc > 2) && ($a->argv[1] === 'clone')) {

		check_form_security_token_redirectOnErr('/profiles', 'profile_clone', 't');

		$r0 = q("SELECT `id` FROM `profile` WHERE `uid` = %d",
			intval(local_user()));
		$num_profiles = count($r0);

		$name = t('Profile-') . ($num_profiles + 1);
		$r1 = q("SELECT * FROM `profile` WHERE `uid` = %d AND `id` = %d LIMIT 1",
			intval(local_user()),
			intval($a->argv[2])
		);
		if(! dbm::is_result($r1)) {
			notice( t('Profile unavailable to clone.') . EOL);
			killme();
			return;
		}
		unset($r1[0]['id']);
		$r1[0]['is-default'] = 0;
		$r1[0]['publish'] = 0;
		$r1[0]['net-publish'] = 0;
		$r1[0]['profile-name'] = dbesc($name);

		dbesc_array($r1[0]);

		$r2 = dbq("INSERT INTO `profile` (`"
			. implode("`, `", array_keys($r1[0]))
			. "`) VALUES ('"
			. implode("', '", array_values($r1[0]))
			. "')" );

		$r3 = q("SELECT `id` FROM `profile` WHERE `uid` = %d AND `profile-name` = '%s' LIMIT 1",
			intval(local_user()),
			dbesc($name)
		);
		info( t('New profile created.') . EOL);
		if ((dbm::is_result($r3)) && (count($r3) == 1))
			goaway('profiles/'.$r3[0]['id']);

		goaway('profiles');

		return; // NOTREACHED
	}


	if(($a->argc > 1) && (intval($a->argv[1]))) {
		$r = q("SELECT id FROM `profile` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($a->argv[1]),
			intval(local_user())
		);
		if (! dbm::is_result($r)) {
			notice( t('Profile not found.') . EOL);
			killme();
			return;
		}

		profile_load($a,$a->user['nickname'],$r[0]['id']);
	}



}

function profile_clean_keywords($keywords) {
	$keywords = str_replace(","," ",$keywords);
	$keywords = explode(" ", $keywords);

	$cleaned = array();
	foreach ($keywords as $keyword) {
		$keyword = trim(strtolower($keyword));
		$keyword = trim($keyword, "#");
		if ($keyword != "")
			$cleaned[] = $keyword;
	}

	$keywords = implode(", ", $cleaned);

	return $keywords;
}

function profiles_post(App &$a) {

	if (! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$namechanged = false;

	call_hooks('profile_post', $_POST);

	if(($a->argc > 1) && ($a->argv[1] !== "new") && intval($a->argv[1])) {
		$orig = q("SELECT * FROM `profile` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($a->argv[1]),
			intval(local_user())
		);
		if(! count($orig)) {
			notice( t('Profile not found.') . EOL);
			return;
		}

		check_form_security_token_redirectOnErr('/profiles', 'profile_edit');

		$is_default = (($orig[0]['is-default']) ? 1 : 0);

		$profile_name = notags(trim($_POST['profile_name']));
		if(! strlen($profile_name)) {
			notice( t('Profile Name is required.') . EOL);
			return;
		}

		$dob = $_POST['dob'] ? escape_tags(trim($_POST['dob'])) : '0000-00-00'; // FIXME: Needs to be validated?

		$y = substr($dob,0,4);
		if((! ctype_digit($y)) || ($y < 1900))
			$ignore_year = true;
		else
			$ignore_year = false;
		if($dob != '0000-00-00') {
			if(strpos($dob,'0000-') === 0) {
				$ignore_year = true;
				$dob = substr($dob,5);
			}
			$dob = datetime_convert('UTC','UTC',(($ignore_year) ? '1900-' . $dob : $dob),(($ignore_year) ? 'm-d' : 'Y-m-d'));
			if($ignore_year)
				$dob = '0000-' . $dob;
		}

		$name = notags(trim($_POST['name']));

		if(! strlen($name)) {
			$name = '[No Name]';
		}

		if($orig[0]['name'] != $name)
			$namechanged = true;



		$pdesc = notags(trim($_POST['pdesc']));
		$gender = notags(trim($_POST['gender']));
		$address = notags(trim($_POST['address']));
		$locality = notags(trim($_POST['locality']));
		$region = notags(trim($_POST['region']));
		$postal_code = notags(trim($_POST['postal_code']));
		$country_name = notags(trim($_POST['country_name']));
		$pub_keywords = profile_clean_keywords(notags(trim($_POST['pub_keywords'])));
		$prv_keywords = profile_clean_keywords(notags(trim($_POST['prv_keywords'])));
		$marital = notags(trim($_POST['marital']));
		$howlong = notags(trim($_POST['howlong']));

		$with = ((x($_POST,'with')) ? notags(trim($_POST['with'])) : '');

		if(! strlen($howlong))
			$howlong = '0000-00-00 00:00:00';
		else
			$howlong = datetime_convert(date_default_timezone_get(),'UTC',$howlong);

		// linkify the relationship target if applicable

		$withchanged = false;

		if(strlen($with)) {
			if($with != strip_tags($orig[0]['with'])) {
				$withchanged = true;
				$prf = '';
				$lookup = $with;
				if(strpos($lookup,'@') === 0)
					$lookup = substr($lookup,1);
				$lookup = str_replace('_',' ', $lookup);
				if(strpos($lookup,'@') || (strpos($lookup,'http://'))) {
					$newname = $lookup;
					$links = @Probe::lrdd($lookup);
					if(count($links)) {
						foreach($links as $link) {
							if($link['@attributes']['rel'] === 'http://webfinger.net/rel/profile-page') {
								$prf = $link['@attributes']['href'];
							}
						}
					}
				}
				else {
					$newname = $lookup;
/*					if(strstr($lookup,' ')) {
						$r = q("SELECT * FROM `contact` WHERE `name` = '%s' AND `uid` = %d LIMIT 1",
							dbesc($newname),
							intval(local_user())
						);
					}
					else {
						$r = q("SELECT * FROM `contact` WHERE `nick` = '%s' AND `uid` = %d LIMIT 1",
							dbesc($lookup),
							intval(local_user())
						);
					}*/

					$r = q("SELECT * FROM `contact` WHERE `name` = '%s' AND `uid` = %d LIMIT 1",
						dbesc($newname),
						intval(local_user())
					);
					if(! $r) {
						$r = q("SELECT * FROM `contact` WHERE `nick` = '%s' AND `uid` = %d LIMIT 1",
							dbesc($lookup),
							intval(local_user())
						);
					}
					if (dbm::is_result($r)) {
						$prf = $r[0]['url'];
						$newname = $r[0]['name'];
					}
				}

				if($prf) {
					$with = str_replace($lookup,'<a href="' . $prf . '">' . $newname	. '</a>', $with);
					if(strpos($with,'@') === 0)
						$with = substr($with,1);
				}
			}
			else
				$with = $orig[0]['with'];
		}

		$sexual = notags(trim($_POST['sexual']));
		$xmpp = notags(trim($_POST['xmpp']));
		$homepage = notags(trim($_POST['homepage']));
		if ((strpos($homepage, 'http') !== 0) && (strlen($homepage))) {
			// neither http nor https in URL, add them
			$homepage = 'http://'.$homepage;
		}
		$hometown = notags(trim($_POST['hometown']));
		$politic = notags(trim($_POST['politic']));
		$religion = notags(trim($_POST['religion']));

		$likes = fix_mce_lf(escape_tags(trim($_POST['likes'])));
		$dislikes = fix_mce_lf(escape_tags(trim($_POST['dislikes'])));

		$about = fix_mce_lf(escape_tags(trim($_POST['about'])));
		$interest = fix_mce_lf(escape_tags(trim($_POST['interest'])));
		$contact = fix_mce_lf(escape_tags(trim($_POST['contact'])));
		$music = fix_mce_lf(escape_tags(trim($_POST['music'])));
		$book = fix_mce_lf(escape_tags(trim($_POST['book'])));
		$tv = fix_mce_lf(escape_tags(trim($_POST['tv'])));
		$film = fix_mce_lf(escape_tags(trim($_POST['film'])));
		$romance = fix_mce_lf(escape_tags(trim($_POST['romance'])));
		$work = fix_mce_lf(escape_tags(trim($_POST['work'])));
		$education = fix_mce_lf(escape_tags(trim($_POST['education'])));

		$hide_friends = (($_POST['hide-friends'] == 1) ? 1: 0);

		set_pconfig(local_user(),'system','detailled_profile', (($_POST['detailled_profile'] == 1) ? 1: 0));

		$changes = array();
		$value = '';
		if($is_default) {
			if($marital != $orig[0]['marital']) {
				$changes[] = '[color=#ff0000]&hearts;[/color] ' . t('Marital Status');
				$value = $marital;
			}
			if($withchanged) {
				$changes[] = '[color=#ff0000]&hearts;[/color] ' . t('Romantic Partner');
				$value = strip_tags($with);
			}
			if($likes != $orig[0]['likes']) {
				$changes[] = t('Likes');
				$value = $likes;
			}
			if($dislikes != $orig[0]['dislikes']) {
				$changes[] = t('Dislikes');
				$value = $dislikes;
			}
			if($work != $orig[0]['work']) {
				$changes[] = t('Work/Employment');
			}
			if($religion != $orig[0]['religion']) {
				$changes[] = t('Religion');
				$value = $religion;
			}
			if($politic != $orig[0]['politic']) {
				$changes[] = t('Political Views');
				$value = $politic;
			}
			if($gender != $orig[0]['gender']) {
				$changes[] = t('Gender');
				$value = $gender;
			}
			if($sexual != $orig[0]['sexual']) {
				$changes[] = t('Sexual Preference');
				$value = $sexual;
			}
			if($xmpp != $orig[0]['xmpp']) {
				$changes[] = t('XMPP');
				$value = $xmpp;
			}
			if($homepage != $orig[0]['homepage']) {
				$changes[] = t('Homepage');
				$value = $homepage;
			}
			if($interest != $orig[0]['interest']) {
				$changes[] = t('Interests');
				$value = $interest;
			}
			if($address != $orig[0]['address']) {
				$changes[] = t('Address');
				// New address not sent in notifications, potential privacy issues
				// in case this leaks to unintended recipients. Yes, it's in the public
				// profile but that doesn't mean we have to broadcast it to everybody.
			}
			if($locality != $orig[0]['locality'] || $region != $orig[0]['region']
				|| $country_name != $orig[0]['country-name']) {
 				$changes[] = t('Location');
				$comma1 = ((($locality) && ($region || $country_name)) ? ', ' : ' ');
				$comma2 = (($region && $country_name) ? ', ' : '');
				$value = $locality . $comma1 . $region . $comma2 . $country_name;
			}

			profile_activity($changes,$value);

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
			dbesc($profile_name),
			dbesc($name),
			dbesc($pdesc),
			dbesc($gender),
			dbesc($dob),
			dbesc($address),
			dbesc($locality),
			dbesc($region),
			dbesc($postal_code),
			dbesc($country_name),
			dbesc($marital),
			dbesc($with),
			dbesc($howlong),
			dbesc($sexual),
			dbesc($xmpp),
			dbesc($homepage),
			dbesc($hometown),
			dbesc($politic),
			dbesc($religion),
			dbesc($pub_keywords),
			dbesc($prv_keywords),
			dbesc($likes),
			dbesc($dislikes),
			dbesc($about),
			dbesc($interest),
			dbesc($contact),
			dbesc($music),
			dbesc($book),
			dbesc($tv),
			dbesc($film),
			dbesc($romance),
			dbesc($work),
			dbesc($education),
			intval($hide_friends),
			intval($a->argv[1]),
			intval(local_user())
		);

		if($r)
			info( t('Profile updated.') . EOL);


		if($namechanged && $is_default) {
			$r = q("UPDATE `contact` SET `name` = '%s', `name-date` = '%s' WHERE `self` = 1 AND `uid` = %d",
				dbesc($name),
				dbesc(datetime_convert()),
				intval(local_user())
			);
			$r = q("UPDATE `user` set `username` = '%s' where `uid` = %d",
				dbesc($name),
				intval(local_user())
			);
		}

		if($is_default) {
			$location = formatted_location(array("locality" => $locality, "region" => $region, "country-name" => $country_name));

			q("UPDATE `contact` SET `about` = '%s', `location` = '%s', `keywords` = '%s', `gender` = '%s' WHERE `self` AND `uid` = %d",
				dbesc($about),
				dbesc($location),
				dbesc($pub_keywords),
				dbesc($gender),
				intval(local_user())
			);

			// Update global directory in background
			$url = $_SESSION['my_url'];
			if ($url && strlen(get_config('system','directory'))) {
				proc_run(PRIORITY_LOW, "include/directory.php", $url);
			}

			require_once('include/profile_update.php');
			profile_change();

			// Update the global contact for the user
			update_gcontact_for_user(local_user());
		}
	}
}


function profile_activity($changed, $value) {
	$a = get_app();

	if(! local_user() || ! is_array($changed) || ! count($changed))
		return;

	if($a->user['hidewall'] || get_config('system','block_public'))
		return;

	if(! get_pconfig(local_user(),'system','post_profilechange'))
		return;

	require_once('include/items.php');

	$self = q("SELECT * FROM `contact` WHERE `self` = 1 AND `uid` = %d LIMIT 1",
		intval(local_user())
	);

	if(! count($self))
		return;

	$arr = array();

	$arr['guid'] = get_guid(32);
	$arr['uri'] = $arr['parent-uri'] = item_new_uri($a->get_hostname(), local_user());
	$arr['uid'] = local_user();
	$arr['contact-id'] = $self[0]['id'];
	$arr['wall'] = 1;
	$arr['type'] = 'wall';
	$arr['gravity'] = 0;
	$arr['origin'] = 1;
	$arr['author-name'] = $arr['owner-name'] = $self[0]['name'];
	$arr['author-link'] = $arr['owner-link'] = $self[0]['url'];
	$arr['author-avatar'] = $arr['owner-avatar'] = $self[0]['thumb'];
	$arr['verb'] = ACTIVITY_UPDATE;
	$arr['object-type'] = ACTIVITY_OBJ_PROFILE;

	$A = '[url=' . $self[0]['url'] . ']' . $self[0]['name'] . '[/url]';


	$changes = '';
	$t = count($changed);
	$z = 0;
	foreach($changed as $ch) {
		if(strlen($changes)) {
			if ($z == ($t - 1))
				$changes .= t(' and ');
			else
				$changes .= ', ';
		}
		$z ++;
		$changes .= $ch;
	}

	$prof = '[url=' . $self[0]['url'] . '?tab=profile' . ']' . t('public profile') . '[/url]';

	if($t == 1 && strlen($value)) {
		$message = sprintf( t('%1$s changed %2$s to &ldquo;%3$s&rdquo;'), $A, $changes, $value);
		$message .= "\n\n" . sprintf( t(' - Visit %1$s\'s %2$s'), $A, $prof);
	}
	else
		$message = 	sprintf( t('%1$s has an updated %2$s, changing %3$s.'), $A, $prof, $changes);


	$arr['body'] = $message;

	$arr['object'] = '<object><type>' . ACTIVITY_OBJ_PROFILE . '</type><title>' . $self[0]['name'] . '</title>'
	. '<id>' . $self[0]['url'] . '/' . $self[0]['name'] . '</id>';
	$arr['object'] .= '<link>' . xmlify('<link rel="alternate" type="text/html" href="' . $self[0]['url'] . '?tab=profile' . '" />' . "\n");
	$arr['object'] .= xmlify('<link rel="photo" type="image/jpeg" href="' . $self[0]['thumb'] . '" />' . "\n");
	$arr['object'] .= '</link></object>' . "\n";
	$arr['last-child'] = 1;

	$arr['allow_cid'] = $a->user['allow_cid'];
	$arr['allow_gid'] = $a->user['allow_gid'];
	$arr['deny_cid']  = $a->user['deny_cid'];
	$arr['deny_gid']  = $a->user['deny_gid'];

	$i = item_store($arr);
	if ($i) {
		proc_run(PRIORITY_HIGH, "include/notifier.php", "activity", $i);
	}
}


function profiles_content(App &$a) {

	if (! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$o = '';

	if(($a->argc > 1) && (intval($a->argv[1]))) {
		$r = q("SELECT * FROM `profile` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($a->argv[1]),
			intval(local_user())
		);
		if (! dbm::is_result($r)) {
			notice( t('Profile not found.') . EOL);
			return;
		}

		require_once('include/profile_selectors.php');


/*		$editselect = 'textareas';
		if( intval(get_pconfig(local_user(),'system','plaintext')) || !feature_enabled(local_user(),'richtext') )
			$editselect = 'none';*/
		$editselect = 'none';
		if( feature_enabled(local_user(),'richtext') )
			$editselect = 'textareas';

		$a->page['htmlhead'] .= replace_macros(get_markup_template('profed_head.tpl'), array(
			'$baseurl' => App::get_baseurl(true),
			'$editselect' => $editselect,
		));
		$a->page['end'] .= replace_macros(get_markup_template('profed_end.tpl'), array(
			'$baseurl' => App::get_baseurl(true),
			'$editselect' => $editselect,
		));


		$opt_tpl = get_markup_template("profile-hide-friends.tpl");
		$hide_friends = replace_macros($opt_tpl,array(
			'$yesno' => array(
				'hide-friends', //Name
				t('Hide contacts and friends:'), //Label
				!!$r[0]['hide-friends'], //Value
				'', //Help string
				array(t('No'),t('Yes')) //Off - On strings
			),
			'$desc' => t('Hide your contact/friend list from viewers of this profile?'),
			'$yes_str' => t('Yes'),
			'$no_str' => t('No'),
			'$yes_selected' => (($r[0]['hide-friends']) ? " checked=\"checked\" " : ""),
			'$no_selected' => (($r[0]['hide-friends'] == 0) ? " checked=\"checked\" " : "")
		));

		$personal_account = !(in_array($a->user["page-flags"],
					array(PAGE_COMMUNITY, PAGE_PRVGROUP)));

		$detailled_profile = (get_pconfig(local_user(),'system','detailled_profile') AND $personal_account);

		$f = get_config('system','birthday_input_format');
		if(! $f)
			$f = 'ymd';

		$is_default = (($r[0]['is-default']) ? 1 : 0);
		$tpl = get_markup_template("profile_edit.tpl");
		$o .= replace_macros($tpl,array(
			'$personal_account' => $personal_account,
			'$detailled_profile' => $detailled_profile,

			'$details' => array(
				'detailled_profile', //Name
				t('Show more profile fields:'), //Label
				$detailled_profile, //Value
				'', //Help string
				array(t('No'),t('Yes')) //Off - On strings
			),

			'$multi_profiles'		=> feature_enabled(local_user(),'multi_profiles'),
			'$form_security_token'		=> get_form_security_token("profile_edit"),
			'$form_security_token_photo'	=> get_form_security_token("profile_photo"),
			'$profile_clone_link'		=> ((feature_enabled(local_user(),'multi_profiles')) ? 'profiles/clone/' . $r[0]['id'] . '?t=' . get_form_security_token("profile_clone") : ""),
			'$profile_drop_link'		=> 'profiles/drop/' . $r[0]['id'] . '?t=' . get_form_security_token("profile_drop"),

			'$profile_action' => t('Profile Actions'),
			'$banner'	=> t('Edit Profile Details'),
			'$submit'	=> t('Submit'),
			'$profpic'	=> t('Change Profile Photo'),
			'$viewprof'	=> t('View this profile'),
			'$editvis' 	=> t('Edit visibility'),
			'$cr_prof'	=> t('Create a new profile using these settings'),
			'$cl_prof'	=> t('Clone this profile'),
			'$del_prof'	=> t('Delete this profile'),

			'$lbl_basic_section' => t('Basic information'),
			'$lbl_picture_section' => t('Profile picture'),
			'$lbl_location_section' => t('Location'),
			'$lbl_preferences_section' => t('Preferences'),
			'$lbl_status_section' => t('Status information'),
			'$lbl_about_section' => t('Additional information'),
			'$lbl_interests_section' => t('Interests'),
			'$lbl_personal_section' => t('Personal'),
			'$lbl_relation_section' => t('Relation'),
			'$lbl_miscellaneous_section' => t('Miscellaneous'),

			'$lbl_profile_photo' => t('Upload Profile Photo'),
			'$lbl_gender' => t('Your Gender:'),
			'$lbl_marital' => t('<span class="heart">&hearts;</span> Marital Status:'),
			'$lbl_sexual' => t('Sexual Preference:'),
			'$lbl_ex2' => t('Example: fishing photography software'),

			'$disabled' => (($is_default) ? 'onclick="return false;" style="color: #BBBBFF;"' : ''),
			'$baseurl' => App::get_baseurl(true),
			'$profile_id' => $r[0]['id'],
			'$profile_name' => array('profile_name', t('Profile Name:'), $r[0]['profile-name'], t('Required'), '*'),
			'$is_default'   => $is_default,
			'$default' => (($is_default) ? '<p id="profile-edit-default-desc">' . t('This is your <strong>public</strong> profile.<br />It <strong>may</strong> be visible to anybody using the internet.') . '</p>' : ""),
			'$name' => array('name', t('Your Full Name:'), $r[0]['name']),
			'$pdesc' => array('pdesc', t('Title/Description:'), $r[0]['pdesc']),
			'$dob' => dob($r[0]['dob']),
			'$hide_friends' => $hide_friends,
			'$address' => array('address', t('Street Address:'), $r[0]['address']),
			'$locality' => array('locality', t('Locality/City:'), $r[0]['locality']),
			'$region' => array('region', t('Region/State:'), $r[0]['region']),
			'$postal_code' => array('postal_code', t('Postal/Zip Code:'), $r[0]['postal-code']),
			'$country_name' => array('country_name', t('Country:'), $r[0]['country-name']),
			'$age' => ((intval($r[0]['dob'])) ? '(' . t('Age: ') . age($r[0]['dob'],$a->user['timezone'],$a->user['timezone']) . ')' : ''),
			'$gender' => gender_selector($r[0]['gender']),
			'$marital' => marital_selector($r[0]['marital']),
			'$with' => array('with', t("Who: \x28if applicable\x29"), strip_tags($r[0]['with']), t('Examples: cathy123, Cathy Williams, cathy@example.com')),
			'$howlong' => array('howlong', t('Since [date]:'), ($r[0]['howlong'] === '0000-00-00 00:00:00' ? '' : datetime_convert('UTC',date_default_timezone_get(),$r[0]['howlong']))),
			'$sexual' => sexpref_selector($r[0]['sexual']),
			'$about' => array('about', t('Tell us about yourself...'), $r[0]['about']),
			'$xmpp' => array('xmpp', t('XMPP (Jabber) address:'), $r[0]['xmpp'], t("The XMPP address will be propagated to your contacts so that they can follow you.")),
			'$homepage' => array('homepage', t('Homepage URL:'), $r[0]['homepage']),
			'$hometown' => array('hometown', t('Hometown:'), $r[0]['hometown']),
			'$politic' => array('politic', t('Political Views:'), $r[0]['politic']),
			'$religion' => array('religion', t('Religious Views:'), $r[0]['religion']),
			'$pub_keywords' => array('pub_keywords', t('Public Keywords:'), $r[0]['pub_keywords'], t("\x28Used for suggesting potential friends, can be seen by others\x29")),
			'$prv_keywords' => array('prv_keywords', t('Private Keywords:'), $r[0]['prv_keywords'], t("\x28Used for searching profiles, never shown to others\x29")),
			'$likes' => array('likes', t('Likes:'), $r[0]['likes']),
			'$dislikes' => array('dislikes', t('Dislikes:'), $r[0]['dislikes']),
			'$music' => array('music', t('Musical interests'), $r[0]['music']),
			'$book' => array('book', t('Books, literature'), $r[0]['book']),
			'$tv' => array('tv', t('Television'), $r[0]['tv']),
			'$film' => array('film', t('Film/dance/culture/entertainment'), $r[0]['film']),
			'$interest' => array('interest', t('Hobbies/Interests'), $r[0]['interest']),
			'$romance' => array('romance',t('Love/romance'), $r[0]['romance']),
			'$work' => array('work', t('Work/employment'), $r[0]['work']),
			'$education' => array('education', t('School/education'), $r[0]['education']),
			'$contact' => array('contact', t('Contact information and Social Networks'), $r[0]['contact']),
		));

		$arr = array('profile' => $r[0], 'entry' => $o);
		call_hooks('profile_edit', $arr);

		return $o;
	}

	//Profiles list.
	else {

		//If we don't support multi profiles, don't display this list.
		if(!feature_enabled(local_user(),'multi_profiles')){
			$r = q(
				"SELECT * FROM `profile` WHERE `uid` = %d AND `is-default`=1",
				local_user()
			);
			if (dbm::is_result($r)){
				//Go to the default profile.
				goaway('profiles/'.$r[0]['id']);
			}
		}

		$r = q("SELECT * FROM `profile` WHERE `uid` = %d",
			local_user());
		if (dbm::is_result($r)) {

			$tpl = get_markup_template('profile_entry.tpl');

			$profiles = '';
			foreach ($r as $rr) {
				$profiles .= replace_macros($tpl, array(
					'$photo'        => $a->remove_baseurl($rr['thumb']),
					'$id'           => $rr['id'],
					'$alt'          => t('Profile Image'),
					'$profile_name' => $rr['profile-name'],
					'$visible'      => (($rr['is-default']) ? '<strong>' . t('visible to everybody') . '</strong>'
						: '<a href="'.'profperm/'.$rr['id'].'" />' . t('Edit visibility') . '</a>')
				));
			}

			$tpl_header = get_markup_template('profile_listing_header.tpl');
			$o .= replace_macros($tpl_header,array(
				'$header'      => t('Edit/Manage Profiles'),
				'$chg_photo'   => t('Change profile photo'),
				'$cr_new'      => t('Create New Profile'),
				'$cr_new_link' => 'profiles/new?t=' . get_form_security_token("profile_new"),
				'$profiles'    => $profiles
			));
		}
		return $o;
	}

}
