<?php
$a->theme_info = array();

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
EOT;
