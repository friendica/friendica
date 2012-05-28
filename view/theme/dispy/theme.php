<?php

/*
 * Name: Dispy
 * Description: Dispy family (light, dark): Sleek and Functional Themes 
 * Version: 1.2.1
 * Author: Simon <http://simon.kisikew.org/>
 * Maintainer: Simon <http://simon.kisikew.org/>
 * Screenshot: <a href="screenshot.jpg">Screenshot</a>
 */

/* If you borrow any of these functions, make sure to
 * RENAME your functions, otherwise both themes get conflicts,
 * and the friendica instance will get HTTP 500 errors.
 * To paraphrase Mike: "Might wish to wrap
 * function_name with "if(! function_exists('function_name')) ... "
 * or rename to prefix1_function_name (prefix2_function_name), etc.
 */

$a = get_app();
$a->theme_info = array(
    'family' => 'dispy',
	'version' => '1.2.2'
);

function dispy_init(&$a) {

    /** @purpose set some theme defaults
    */
    $cssFile = null;
    $colour = get_pconfig(local_user(), "dispy", "colour");
	$baseurl = $a->get_baseurl($ssl_state);

    if ($colour === false) { $colour = "light"; }
    if ($colour == "light") {
		$colour_path = "/light/";
		require_once ('light/theme.php');
	}
    if ($colour == "dark") {
		$colour_path = "/dark/";
		require_once ('dark/theme.php');
	}

    /** @purpose aside on profile page
    */
	if (($a->argv[0] . $a->argv[1]) === ("profile" . $a->user['nickname'])) {
		dispy_community_info();
	}

	$a->page['htmlhead'] .= <<<EOT
	<script type="text/javascript" src="$baseurl/view/theme/dispy/js/modernizr.custom.2.5.3.min.js"></script>
	<script type="text/javascript">
	$(document).ready(function() {
		// Select all links with lightbox class
		$("a.lightbox").fancybox();

    	$('.group-edit-icon').hover(
			function() {
				$(this).addClass('icon');
				$(this).removeClass('iconspacer'); },

			function() {
				$(this).removeClass('icon');
				$(this).addClass('iconspacer'); }
		);

		$('.sidebar-group-element').hover(
			function() {
				id = $(this).attr('id');
				$('#edit-' + id).addClass('icon');
				$('#edit-' + id).removeClass('iconspacer'); },

			function() {
				id = $(this).attr('id');
				$('#edit-' + id).removeClass('icon');
				$('#edit-' + id).addClass('iconspacer'); }
		);

		$('.savedsearchdrop').hover(
			function() {
				$(this).addClass('drop');
				$(this).addClass('icon');
				$(this).removeClass('iconspacer'); },

			function() {
				$(this).removeClass('drop');
				$(this).removeClass('icon');
				$(this).addClass('iconspacer'); }
		);

		$('.savedsearchterm').hover(
			function() {
				id = $(this).attr('id');
				$('#drop-' + id).addClass('icon');
				$('#drop-' + id).addClass('drophide');
				$('#drop-' + id).removeClass('iconspacer'); },

			function() {
				id = $(this).attr('id');
				$('#drop-' + id).removeClass('icon');
				$('#drop-' + id).removeClass('drophide');
				$('#drop-' + id).addClass('iconspacer'); }
			);

		// click outside notifications menu closes it
		$('html').click(function() {
			$('#nav-notifications-linkmenu').removeClass('selected');
			$('#nav-notifications-menu').css({display: 'none'});
		});

		$('#nav-notifications-linkmenu').click(function(event) {
			event.stopPropagation();
		});
		// click outside profiles menu closes it
		$('html').click(function() {
			$('#profiles-menu-trigger').removeClass('selected');
			$('#profiles-menu').css({display: 'none'});
		});

		$('#profiles-menu').click(function(event) {
			event.stopPropagation();
		});

		// main function in toolbar functioning
		function toggleToolbar() {
			if ( $('#nav-floater').is(':visible') ) {
				$('#nav-floater').slideUp('fast');
				$('.floaterflip').css({
					backgroundPosition: '-210px -60px' 
				});
				$('.search-box').slideUp('fast');
			} else {
				$('#nav-floater').slideDown('fast');
				$('.floaterflip').css({
					backgroundPosition: '-190px -60px'
				});
				$('.search-box').slideDown('fast');
			}
		};
		// our trigger for the toolbar button
		$('.floaterflip').click(function() {
			toggleToolbar();
			return false;
		});

		// (attempt to) change the text colour in a top post
		$('#profile-jot-text').focusin(function() {
			$(this).css({color: '#eec'});
		});

		$('a[href=#top]').click(function() {
			$('html, body').animate({scrollTop:0}, 'slow');
			return false;
		});

	});
	// shadowing effect for floating toolbars
	$(document).scroll(function(e) {
		var pageTop = $('html').scrollTop();
		if (pageTop) {
			$('#nav-floater').css({boxShadow: '3px 3px 10px rgba(0, 0, 0, 0.7)'});
			$('.search-box').css({boxShadow: '3px 3px 10px rgba(0, 0, 0, 0.7)'});
		} else {
			$('#nav-floater').css({boxShadow: '0 0 0 0'});
			$('.search-box').css({boxShadow: '0 0 0 0'});
		}
	});
	</script>
EOT;

    // custom css
    if (!is_null($cssFile)) {
        $a->page['htmlhead'] .= sprintf('<link rel="stylesheet" type="text/css" href="%s" />', $cssFile);
    }

	_js_in_foot();
}

function dispy_community_info() {
    /** @purpose some sidebar stuff for new users
    */
	$a = get_app();
	$url = $a->get_baseurl($ssl_state);
	$aside['$url'] = $url;

	$tpl = file_get_contents(dirname(__file__) . '/communityhome.tpl');
	return $a->page['aside_bottom'] = replace_macros($tpl, $aside);
}

if(! function_exists('_js_in_foot')) {
	function _js_in_foot() {
		/** @purpose insert stuff in bottom of page
		*/
		$a = get_app();
		$baseurl = $a->get_baseurl($ssl_state);
		$bottom['$baseurl'] = $baseurl;
		$tpl = file_get_contents(dirname(__file__) . '/bottom.tpl');

		return $a->page['bottom'] = replace_macros($tpl, $bottom);
	}
}
