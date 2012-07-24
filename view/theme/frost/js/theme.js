$(document).ready(function() {

	window.navMenuTimeout = {
		'#network-menu-list-timeout': null,
		'#contacts-menu-list-timeout': null,
		'#system-menu-list-timeout': null,
		'#network-menu-list-opening': false,
		'#contacts-menu-list-opening': false,
		'#system-menu-list-opening': false,
		'#network-menu-list-closing': false,
		'#contacts-menu-list-closing': false,
		'#system-menu-list-closing': false
	};

    $.ajaxSetup({ 
        cache: false 
    });


	$('.nav-menu-link').hover(function() {
		showNavMenu($(this).attr('rel'));
	}, function() {
		hideNavMenu($(this).attr('rel'));
	});

/*	$('html').click(function() { $("#nav-notifications-menu" ).hide(); });*/

	$('.group-edit-icon').hover(
		function() {
			$(this).addClass('icon'); $(this).removeClass('iconspacer');},
		function() {
			$(this).removeClass('icon'); $(this).addClass('iconspacer');}
		);

	$('.sidebar-group-element').hover(
		function() {
			id = $(this).attr('id');
			$('#edit-' + id).addClass('icon'); $('#edit-' + id).removeClass('iconspacer');},

		function() {
			id = $(this).attr('id');
			$('#edit-' + id).removeClass('icon');$('#edit-' + id).addClass('iconspacer');}
		);


	$('.savedsearchdrop').hover(
		function() {
			$(this).addClass('drop'); $(this).addClass('icon'); $(this).removeClass('iconspacer');},
		function() {
			$(this).removeClass('drop'); $(this).removeClass('icon'); $(this).addClass('iconspacer');}
	);

	$('.savedsearchterm').hover(
		function() {
			id = $(this).attr('id');
			$('#drop-' + id).addClass('icon'); 	$('#drop-' + id).addClass('drophide'); $('#drop-' + id).removeClass('iconspacer');},

		function() {
			id = $(this).attr('id');
			$('#drop-' + id).removeClass('icon');$('#drop-' + id).removeClass('drophide'); $('#drop-' + id).addClass('iconspacer');}
	);

/*	$('.nav-load-page-link').click(function() {
		getPageContent( $(this).attr('href') );
		hideNavMenu( '#' + $(this).closest('ul').attr('id') );
		return false;
	});*/

});


function insertFormatting(comment,BBcode,id) {
	
		var tmpStr = $("#comment-edit-text-" + id).val();
		if(tmpStr == comment) {
			tmpStr = "";
			$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
			openMenu("comment-edit-submit-wrapper-" + id);
			$("#comment-edit-text-" + id).val(tmpStr);
		}

	textarea = document.getElementById("comment-edit-text-" +id);
	if (document.selection) {
		textarea.focus();
		selected = document.selection.createRange();
		if (BBcode == "url"){
			selected.text = "["+BBcode+"]" + "http://" +  selected.text + "[/"+BBcode+"]";
			} else			
		selected.text = "["+BBcode+"]" + selected.text + "[/"+BBcode+"]";
	} else if (textarea.selectionStart || textarea.selectionStart == "0") {
		var start = textarea.selectionStart;
		var end = textarea.selectionEnd;
		if (BBcode == "url"){
			textarea.value = textarea.value.substring(0, start) + "["+BBcode+"]" + "http://" + textarea.value.substring(start, end) + "[/"+BBcode+"]" + textarea.value.substring(end, textarea.value.length);
			} else
		textarea.value = textarea.value.substring(0, start) + "["+BBcode+"]" + textarea.value.substring(start, end) + "[/"+BBcode+"]" + textarea.value.substring(end, textarea.value.length);
	}
	return true;
}

function cmtBbOpen(id) {
	$(".comment-edit-bb-" + id).show();
}
function cmtBbClose(id) {
	$(".comment-edit-bb-" + id).hide();
}

/*
$(document).mouseup(function (clickPos) {

	var sysMenu = $("#system-menu-list");
	var sysMenuLink = $(".system-menu-link");
	var contactsMenu = $("#contacts-menu-list");
	var contactsMenuLink = $(".contacts-menu-link");
	var networkMenu = $("#network-menu-list");
	var networkMenuLink = $(".network-menu-link");

	if( !sysMenu.is(clickPos.target) && !sysMenuLink.is(clickPos.target) && sysMenu.has(clickPos.target).length === 0) {
		hideNavMenu("#system-menu-list");
	}
	if( !contactsMenu.is(clickPos.target) && !contactsMenuLink.is(clickPos.target) && contactsMenu.has(clickPos.target).length === 0) {
		hideNavMenu("#contacts-menu-list");
	}
	if( !networkMenu.is(clickPos.target) && !networkMenuLink.is(clickPos.target) && networkMenu.has(clickPos.target).length === 0) {
		hideNavMenu("#network-menu-list");
	}
});


function getPageContent(url) {

	var pos = $('.main-container').position();

	$('.main-container').css('margin-left', pos.left);	
	$('.main-content-container').hide(0, function () {
		$('.main-content-loading').show(0);
	});

	$.get(url, function(html) {
		console.log($('.main-content-container').html());
		$('.main-content-container').html( $('.main-content-container', html).html() );
		console.log($('.main-content-container').html());
		$('.main-content-loading').hide(function() {
			$('.main-content-container').fadeIn(800,function() {
				$('.main-container').css('margin-left', 'auto'); // This sucks -- if the CSS specification changes, this will be wrong
			});
		});
	});
}
*/

function showNavMenu(menuID) {

	if(window.navMenuTimeout[menuID + '-closing']) {
		window.navMenuTimeout[menuID + '-closing'] = false;
		clearTimeout(window.navMenuTimeout[menuID + '-timeout']);
	}
	else {
		window.navMenuTimeout[menuID + '-opening'] = true;
		
		window.navMenuTimeout[menuID + '-timeout'] = setTimeout( function () {
			$(menuID).slideDown('fast').show();
			window.navMenuTimeout[menuID + '-opening'] = false;
		}, 200);
	}
}

function hideNavMenu(menuID) {

	if(window.navMenuTimeout[menuID + '-opening']) {
		window.navMenuTimeout[menuID + '-opening'] = false;
		clearTimeout(window.navMenuTimeout[menuID + '-timeout']);
	}
	else {
		window.navMenuTimeout[menuID + '-closing'] = true;
		
		window.navMenuTimeout[menuID + '-timeout'] = setTimeout( function () {
			$(menuID).slideUp('fast');
			window.navMenuTimeout[menuID + '-closing'] = false;
		}, 500);
	}
}

