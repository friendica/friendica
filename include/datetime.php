<?php

// two-level sort for timezones.

if(! function_exists('timezone_cmp')) {
function timezone_cmp($a, $b) {
	if(strstr($a,'/') && strstr($b,'/')) {
		if ( t($a) == t($b)) return 0;
		return ( t($a) < t($b)) ? -1 : 1;
	}
	if(strstr($a,'/')) return -1;
	if(strstr($b,'/')) return  1;
	if ( t($a) == t($b)) return 0;
	return ( t($a) < t($b)) ? -1 : 1;
}}

// emit a timezone selector grouped (primarily) by continent
if(! function_exists('select_timezone')) {
function select_timezone($current = 'America/Los_Angeles') {

	$timezone_identifiers = DateTimeZone::listIdentifiers();
	
	$o ='<select id="timezone_select" name="timezone">';

	usort($timezone_identifiers, 'timezone_cmp');
	$continent = '';
	foreach($timezone_identifiers as $value) {
		$ex = explode("/", $value);
		if(count($ex) > 1) {
			if($ex[0] != $continent) {
				if($continent != '')
					$o .= '</optgroup>';
				$continent = $ex[0];
				$o .= '<optgroup label="' . t($continent) . '">';
			}
			if(count($ex) > 2)
				$city = substr($value,strpos($value,'/')+1);
			else
				$city = $ex[1];
		}
		else {
			$city = $ex[0];
			if($continent != t('Miscellaneous')) {
				$o .= '</optgroup>';
				$continent = t('Miscellaneous');
				$o .= '<optgroup label="' . t($continent) . '">';	
			}
		}
		$city = str_replace('_', ' ',  t($city));
		$selected = (($value == $current) ? " selected=\"selected\" " : "");
		$o .= "<option value=\"$value\" $selected >$city</option>";
	}    
	$o .= '</optgroup></select>';
	return $o;
}}

// return a select using 'field_select_raw' template, with timezones 
// groupped (primarily) by continent
// arguments follow convetion as other field_* template array:
// 'name', 'label', $value, 'help'
if (!function_exists('field_timezone')){
function field_timezone($name='timezone', $label='', $current = 'America/Los_Angeles', $help){
	$options = select_timezone($current);
	$options = str_replace('<select id="timezone_select" name="timezone">','', $options);
	$options = str_replace('</select>','', $options);
	
	$tpl = get_markup_template('field_select_raw.tpl');
	return replace_macros($tpl, array(
		'$field' => array($name, $label, $current, $help, $options),
	));
	
}}

// General purpose date parse/convert function.
// $from = source timezone
// $to   = dest timezone
// $s    = some parseable date/time string
// $fmt  = output format

if(! function_exists('datetime_convert')) {
function datetime_convert($from = 'UTC', $to = 'UTC', $s = 'now', $fmt = "Y-m-d H:i:s") {

	// Defaults to UTC if nothing is set, but throws an exception if set to empty string.
	// Provide some sane defaults regardless.

	if($from === '')
		$from = 'UTC';
	if($to === '')
		$to = 'UTC';
	if( ($s === '') || (! is_string($s)) )
		$s = 'now';

	// Slight hackish adjustment so that 'zero' datetime actually returns what is intended
	// otherwise we end up with -0001-11-30 ...
	// add 32 days so that we at least get year 00, and then hack around the fact that 
	// months and days always start with 1. 

	if(substr($s,0,10) == '0000-00-00') {
		$d = new DateTime($s . ' + 32 days', new DateTimeZone('UTC'));
		return str_replace('1','0',$d->format($fmt));
	}

	$d = new DateTime($s, new DateTimeZone($from));
	$d->setTimeZone(new DateTimeZone($to));
	return($d->format($fmt));
}}

// wrapper for date selector, tailored for use in birthday fields

function dob($dob) {
	list($year,$month,$day) = sscanf($dob,'%4d-%2d-%2d');
	$y = datetime_convert('UTC',date_default_timezone_get(),'now','Y');
	$f = get_config('system','birthday_input_format');
	if(! $f)
		$f = 'ymd';
	$o = datesel($f,'',1920,$y,true,$year,$month,$day);
	return $o;
}


function datesel_format($f) {

	$o = '';

	if(strlen($f)) {
		for($x = 0; $x < strlen($f); $x ++) {
			switch($f[$x]) {
				case 'y':
					if(strlen($o))
						$o .= '-';
					$o .= t('year');					
					break;
				case 'm':
					if(strlen($o))
						$o .= '-';
					$o .= t('month');					
					break;
				case 'd':
					if(strlen($o))
						$o .= '-';
					$o .= t('day');
					break;
				default:
					break;
			}
		}
	}
	return $o;
}


// returns a date selector.
// $f           = format string, e.g. 'ymd' or 'mdy'
// $pre         = prefix (if needed) for HTML name and class fields
// $ymin        = first year shown in selector dropdown
// $ymax        = last year shown in selector dropdown
// $allow_blank = allow an empty response on any field
// $y           = already selected year
// $m           = already selected month
// $d           = already selected day

if(! function_exists('datesel')) {
function datesel($f,$pre,$ymin,$ymax,$allow_blank,$y,$m,$d) {

	$o = '';

	if(strlen($f)) {
		for($z = 0; $z < strlen($f); $z ++) {
			if($f[$z] === 'y') {

				$o .= "<select name=\"{$pre}year\" class=\"{$pre}year\" size=\"1\">";
				if($allow_blank) {
					$sel = (($y == '0000') ? " selected=\"selected\" " : "");
					$o .= "<option value=\"0000\" $sel ></option>";
				}

				if($ymax > $ymin) {
					for($x = $ymax; $x >= $ymin; $x --) {
						$sel = (($x == $y) ? " selected=\"selected\" " : "");
						$o .= "<option value=\"$x\" $sel>$x</option>";
					}
				}
				else {
					for($x = $ymax; $x <= $ymin; $x ++) {
						$sel = (($x == $y) ? " selected=\"selected\" " : "");
						$o .= "<option value=\"$x\" $sel>$x</option>";
					}
				}
			}
			elseif($f[$z] == 'm') {
  
				$o .= "</select> <select name=\"{$pre}month\" class=\"{$pre}month\" size=\"1\">";
				for($x = (($allow_blank) ? 0 : 1); $x <= 12; $x ++) {
					$sel = (($x == $m) ? " selected=\"selected\" " : "");
					$y = (($x) ? $x : '');
					$o .= "<option value=\"$x\" $sel>$y</option>";
				}
			}
			elseif($f[$z] == 'd') {

				$o .= "</select> <select name=\"{$pre}day\" class=\"{$pre}day\" size=\"1\">";
				for($x = (($allow_blank) ? 0 : 1); $x <= 31; $x ++) {
					$sel = (($x == $d) ? " selected=\"selected\" " : "");
					$y = (($x) ? $x : '');
					$o .= "<option value=\"$x\" $sel>$y</option>";
				}
			}
		}
	}

	$o .= "</select>";
	return $o;
}}

if(! function_exists('timesel')) {
function timesel($pre,$h,$m) {

	$o = '';
	$o .= "<select name=\"{$pre}hour\" class=\"{$pre}hour\" size=\"1\">";
	for($x = 0; $x < 24; $x ++) {
		$sel = (($x == $h) ? " selected=\"selected\" " : "");
		$o .= "<option value=\"$x\" $sel>$x</option>";
	}
	$o .= "</select> : <select name=\"{$pre}minute\" class=\"{$pre}minute\" size=\"1\">";
	for($x = 0; $x < 60; $x ++) {
		$sel = (($x == $m) ? " selected=\"selected\" " : "");
		$o .= "<option value=\"$x\" $sel>$x</option>";
	}

	$o .= "</select>";
	return $o;
}}








// implements "3 seconds ago" etc.
// based on $posted_date, (UTC).
// Results relative to current timezone
// Limited to range of timestamps

if(! function_exists('relative_date')) {
function relative_date($posted_date,$format = null) {

	$localtime = datetime_convert('UTC',date_default_timezone_get(),$posted_date); 

	$abs = strtotime($localtime);
    
    if (is_null($posted_date) || $posted_date === '0000-00-00 00:00:00' || $abs === False) {
		 return t('never');
	}

	$etime = time() - $abs;
    
	if ($etime < 1) {
		return t('less than a second ago');
	}
    
	$a = array( 12 * 30 * 24 * 60 * 60  =>  array( t('year'),   t('years')),
				30 * 24 * 60 * 60       =>  array( t('month'),  t('months')),
				7  * 24 * 60 * 60       =>  array( t('week'),   t('weeks')),
				24 * 60 * 60            =>  array( t('day'),    t('days')),
				60 * 60                 =>  array( t('hour'),   t('hours')),
				60                      =>  array( t('minute'), t('minutes')),
				1                       =>  array( t('second'), t('seconds'))
	);
    
	foreach ($a as $secs => $str) {
		$d = $etime / $secs;
		if ($d >= 1) {
			$r = round($d);
			// translators - e.g. 22 hours ago, 1 minute ago
			if(! $format)
				$format = t('%1$d %2$s ago');
			return sprintf( $format,$r, (($r == 1) ? $str[0] : $str[1]));
        }
    }
}}



// Returns age in years, given a date of birth,
// the timezone of the person whose date of birth is provided,
// and the timezone of the person viewing the result.
// Why? Bear with me. Let's say I live in Mittagong, Australia, and my 
// birthday is on New Year's. You live in San Bruno, California.
// When exactly are you going to see my age increase?
// A: 5:00 AM Dec 31 San Bruno time. That's precisely when I start 
// celebrating and become a year older. If you wish me happy birthday 
// on January 1 (San Bruno time), you'll be a day late. 
   
function age($dob,$owner_tz = '',$viewer_tz = '') {
	if(! intval($dob))
		return 0;
	if(! $owner_tz)
		$owner_tz = date_default_timezone_get();
	if(! $viewer_tz)
		$viewer_tz = date_default_timezone_get();

	$birthdate = datetime_convert('UTC',$owner_tz,$dob . ' 00:00:00+00:00','Y-m-d');
	list($year,$month,$day) = explode("-",$birthdate);
	$year_diff  = datetime_convert('UTC',$viewer_tz,'now','Y') - $year;
	$curr_month = datetime_convert('UTC',$viewer_tz,'now','m');
	$curr_day   = datetime_convert('UTC',$viewer_tz,'now','d');

	if(($curr_month < $month) || (($curr_month == $month) && ($curr_day < $day)))
		$year_diff--;
	return $year_diff;
}



// Get days in month
// get_dim($year, $month);
// returns number of days.
// $month[1] = 'January'; 
//   to match human usage.

if(! function_exists('get_dim')) {
function get_dim($y,$m) {

  $dim = array( 0,
    31, 28, 31, 30, 31, 30,
    31, 31, 30, 31, 30, 31);
 
  if($m != 2)
    return $dim[$m];
  if(((($y % 4) == 0) && (($y % 100) != 0)) || (($y % 400) == 0))
    return 29;
  return $dim[2];
}}


// Returns the first day in month for a given month, year
// get_first_dim($year,$month)
// returns 0 = Sunday through 6 = Saturday
// Months start at 1.

if(! function_exists('get_first_dim')) {
function get_first_dim($y,$m) {
  $d = sprintf('%04d-%02d-01 00:00', intval($y), intval($m));
  return datetime_convert('UTC','UTC',$d,'w');
}}

// output a calendar for the given month, year.
// if $links are provided (array), e.g. $links[12] => 'http://mylink' , 
// date 12 will be linked appropriately. Today's date is also noted by 
// altering td class.
// Months count from 1.


// TODO: provide (prev,next) links, define class variations for different size calendars


if(! function_exists('cal')) {
function cal($y = 0,$m = 0, $links = false, $class='') {


	// month table - start at 1 to match human usage.

	$mtab = array(' ',
	  'January','February','March',
	  'April','May','June',
	  'July','August','September',
	  'October','November','December'
	); 

	$thisyear = datetime_convert('UTC',date_default_timezone_get(),'now','Y');
	$thismonth = datetime_convert('UTC',date_default_timezone_get(),'now','m');
	if(! $y)
		$y = $thisyear;
	if(! $m)
		$m = intval($thismonth);

  $dn = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
  $f = get_first_dim($y,$m);
  $l = get_dim($y,$m);
  $d = 1;
  $dow = 0;
  $started = false;

  if(($y == $thisyear) && ($m == $thismonth))
    $tddate = intval(datetime_convert('UTC',date_default_timezone_get(),'now','j'));

	$str_month = day_translate($mtab[$m]);
  $o = '<table class="calendar' . $class . '">';
  $o .= "<caption>$str_month $y</caption><tr>";
  for($a = 0; $a < 7; $a ++)
     $o .= '<th>' . mb_substr(day_translate($dn[$a]),0,3,'UTF-8') . '</th>';
  $o .= '</tr><tr>';

  while($d <= $l) {
    if(($dow == $f) && (! $started))
      $started = true;
    $today = (((isset($tddate)) && ($tddate == $d)) ? "class=\"today\" " : '');
    $o .= "<td $today>";
	$day = str_replace(' ','&nbsp;',sprintf('%2.2d', $d));
    if($started) {
      if(is_array($links) && isset($links[$d]))
        $o .=  "<a href=\"{$links[$d]}\">$day</a>";
      else
        $o .= $day;
      $d ++;
    }
    else
      $o .= '&nbsp;';
    $o .= '</td>';
    $dow ++;
    if(($dow == 7) && ($d <= $l)) {
      $dow = 0;
      $o .= '</tr><tr>';
    }
  }
  if($dow)
    for($a = $dow; $a < 7; $a ++)
       $o .= '<td>&nbsp;</td>';
  $o .= '</tr></table>'."\r\n";  
  
  return $o;
}}


function update_contact_birthdays() {

	// This only handles foreign or alien networks where a birthday has been provided.
	// In-network birthdays are handled within local_delivery

	$r = q("SELECT * FROM contact WHERE `bd` != '' AND `bd` != '0000-00-00' AND SUBSTRING(`bd`,1,4) != `bdyear` ");
	if(count($r)) {
		foreach($r as $rr) {

			logger('update_contact_birthday: ' . $rr['bd']);

			$nextbd = datetime_convert('UTC','UTC','now','Y') . substr($rr['bd'],4);

			/**
			 *
			 * Add new birthday event for this person
			 *
			 * $bdtext is just a readable placeholder in case the event is shared
			 * with others. We will replace it during presentation to our $importer
			 * to contain a sparkle link and perhaps a photo. 
			 *
			 */
			 
			$bdtext = t('Birthday:') . ' [url=' . $rr['url'] . ']' . $rr['name'] . '[/url]' ;


			$r = q("INSERT INTO `event` (`uid`,`cid`,`created`,`edited`,`start`,`finish`,`desc`,`type`,`adjust`)
				VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%d' ) ",
				intval($rr['uid']),
			 	intval($rr['id']),
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				dbesc(datetime_convert('UTC','UTC', $nextbd)),
				dbesc(datetime_convert('UTC','UTC', $nextbd . ' + 1 day ')),
				dbesc($bdtext),
				dbesc('birthday'),
				intval(0)
			);
			

			// update bdyear

			q("UPDATE `contact` SET `bdyear` = '%s', `bd` = '%s' WHERE `uid` = %d AND `id` = %d LIMIT 1",
				dbesc(substr($nextbd,0,4)),
				dbesc($nextbd),
				intval($rr['uid']),
				intval($rr['id'])
			);

		}
	}
}
