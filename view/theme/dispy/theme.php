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

	// notifications
	$('html').click(function() {
		$('#nav-notifications-linkmenu').removeClass('selected');
		document.getElementById("nav-notifications-menu").style.display = "none";
	});

	$('#nav-notifications-linkmenu').click(function(event) {
		event.stopPropagation();
	});

	// usermenu
	$('html').click(function() {
		$('#nav-user-linkmenu').removeClass('selected');
		document.getElementById("nav-user-menu").style.display = "none";
	});

	$('#nav-user-linkmenu').click(function(event) {
		event.stopPropagation();
	});

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
    $('.floaterflip').click(function() {
        toggleToolbar();
        return false;
    });
});
</script>
<script>
$(document).ready(function() {
	$('#profile-jot-text').focusin(function() {
		$(this).css('color: #eec;');
	});
});
</script>
EOT;

$a->page['footer'] .= <<<EOFooter
EOFooter;
