<?php

/*
 * Name: Dispy
 * Description: Dispy, Friendica theme
 * Version: 0.9
 * Author: unknown
 * Maintainer: Simon <http://simon.kisikew.org/>
 */


$a->theme_info = array(
	'extends' => 'dispy'
);

$a->page['htmlhead'] .= <<< EOT
<script>
$(document).ready(function() {
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
		document.getElementById("nav-notifications-menu").style.display = "none";
	});

	$('#nav-notifications-linkmenu').click(function(event) {
		event.stopPropagation();
	});

	// main function in toolbar functioning
    function toggleToolbar() {
        if ( $('#nav-floater').is(':visible') ) {
            $('#nav-floater').slideUp('fast');
            $('.floaterflip').css({
                backgroundPosition: '-210px -60px' 
            });
        } else {
            $('#nav-floater').slideDown('fast');
            $('.floaterflip').css({
                backgroundPosition: '-190px -60px'
            });
        }
    };
	// our trigger for the toolbar button
    $('.floaterflip').click(function() {
        toggleToolbar();
        return false;
    });

	// (attempt) to change the text colour in a top post
	$('#profile-jot-text').focusin(function() {
		$(this).css({color: '#eec'});
	});

	// make auto-complete work in more places
	$(".wall-item-comment-wrapper textarea").contact_autocomplete(baseurl+"/acl");

/*	$('#profile-photo-wrapper').mouseover(function() {
		$('.profile-edit-side-div').css({display: 'block'});
	}).mouseout(function() {
		$('.profile-edit-side-div').css({display: 'none'});
		return false;
	});

	$('img.photo').mouseover(function() {
		$('.profile-edit-side-div').css({display: 'block'});
	}).mouseout(function() {
		$('.profile-edit-side-div').css({display: 'none'});
		return false;
	});*/

});
</script>
EOT;

