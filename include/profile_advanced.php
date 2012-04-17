<?php

function advanced_profile(&$a) {

	$o = '';

	$o .= '<h2>' . t('Profile') . '</h2>';

	if($a->profile['name']) {

		$tpl = get_markup_template('profile_advanced.tpl');
		
		$profile = array();
		
		$profile['fullname'] = array( t('Full Name:'), $a->profile['name'] ) ;
		
		if($a->profile['gender']) $profile['gender'] = array( t('Gender:'),  $a->profile['gender'] );
		

		if(($a->profile['dob']) && ($a->profile['dob'] != '0000-00-00')) {
		
			$year_bd_format = t('j F, Y');
			$short_bd_format = t('j F');

		
			$val = ((intval($a->profile['dob'])) 
				? day_translate(datetime_convert('UTC','UTC',$a->profile['dob'] . ' 00:00 +00:00',$year_bd_format))
				: day_translate(datetime_convert('UTC','UTC','2001-' . substr($a->profile['dob'],6) . ' 00:00 +00:00',$short_bd_format)));
			
			$profile['birthday'] = array( t('Birthday:'), $val);

		}

		if($age = age($a->profile['dob'],$a->profile['timezone'],''))  $profile['age'] = array( t('Age:'), $age );
			

		if($a->profile['marital']) $profile['marital'] = array( t('Status:'), $a->profile['marital']);


		if($a->profile['with']) $profile['marital']['with'] = $a->profile['with'];


		if($a->profile['sexual']) $profile['sexual'] = array( t('Sexual Preference:'), $a->profile['sexual'] );

		if($a->profile['homepage']) $profile['homepage'] = array( t('Homepage:'), linkify($a->profile['homepage']) );

		if($a->profile['pub_keywords']) $profile['pub_keywords'] = array( t('Tags:'), $a->profile['pub_keywords']);

		if($a->profile['politic']) $profile['politic'] = array( t('Political Views:'), $a->profile['politic']);

		if($a->profile['religion']) $profile['religion'] = array( t('Religion:'), $a->profile['religion']);

		if($txt = prepare_text($a->profile['about'])) $profile['about'] = array( t('About:'), $txt );

		if($txt = prepare_text($a->profile['interest'])) $profile['interest'] = array( t('Hobbies/Interests:'), $txt);

		if($txt = prepare_text($a->profile['contact'])) $profile['contact'] = array( t('Contact information and Social Networks:'), $txt);

		if($txt = prepare_text($a->profile['music'])) $profile['music'] = array( t('Musical interests:'), $txt);
		
		if($txt = prepare_text($a->profile['book'])) $profile['book'] = array( t('Books, literature:'), $txt);

		if($txt = prepare_text($a->profile['tv'])) $profile['tv'] = array( t('Television:'), $txt);

		if($txt = prepare_text($a->profile['film'])) $profile['film'] = array( t('Film/dance/culture/entertainment:'), $txt);

		if($txt = prepare_text($a->profile['romance'])) $profile['romance'] = array( t('Love/Romance:'), $txt);
		
		if($txt = prepare_text($a->profile['work'])) $profile['work'] = array( t('Work/employment:'), $txt);

		if($txt = prepare_text($a->profile['education'])) $profile['education'] = array( t('School/education:'), $txt );

        return replace_macros($tpl, array(
            '$title' => t('Profile'),
            '$profile' => $profile,
        ));
    }

	return '';
}
