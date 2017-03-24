
var jotcache = ''; //The jot cache. We use it as cache to restore old/original jot content

$(document).ready(function(){
	//fade in/out based on scrollTop value
	$(window).scroll(function () {
		if ($(this).scrollTop() > 1000) {
			$("#back-to-top").fadeIn();
		} else {
			$("#back-to-top").fadeOut();
		}
	});

	// scroll body to 0px on click
	$("#back-to-top").click(function () {
		$("body,html").animate({
			scrollTop: 0
		}, 400);
		return false;
	});

	// add the class "selected" to group widges li if li > a does have the class group-selected
	if( $("#sidebar-group-ul li a").hasClass("group-selected")) {
		$("#sidebar-group-ul li a.group-selected").parent("li").addClass("selected");
	}

	// add the class "selected" to forums widges li if li > a does have the class forum-selected
	if( $("#forumlist-sidbar-ul li a").hasClass("forum-selected")) {
		$("#forumlist-sidbar-ul li a.forum-selected").parent("li").addClass("selected");
	}

	// add the class "active" to tabmenuli if li > a does have the class active
	if( $("#tabmenu ul li a").hasClass("active")) {
		$("#tabmenu ul li a.active").parent("li").addClass("active");
	}

	// give select fields an boostrap classes
	// @todo: this needs to be changed in friendica core
	$(".field.select, .field.custom").addClass("form-group");
	$(".field.select > select, .field.custom > select").addClass("form-control");

	// move the tabbar to the second nav bar
	$("section .tabbar-wrapper").first().appendTo("#topbar-second > .container > #tabmenu");

	// add mask css url to the logo-img container
	//
	// This is for firefox - we use a mask which looks like the friendica logo to apply user collers
	// to the friendica logo (the mask is in nav.tpl at the botom). To make it work we need to apply the
	// correct url. The only way which comes to my mind was to do this with js
	// So we apply the correct url (with the link to the id of the mask) after the page is loaded.
	if($("#logo-img").length ) {
		var pageurl = "url('" + window.location.href + "#logo-mask')";
		$("#logo-img").css({"mask": pageurl});
	}

	// make responsive tabmenu with flexmenu.js
	// the menupoints which doesn't fit in the second nav bar will moved to a
	// dropdown menu. Look at common_tabs.tpl
	$("ul.tabs.flex-nav").flexMenu({
		'cutoff': 2,
		'popupClass': "dropdown-menu pull-right",
		'popupAbsolute': false,
		'target': ".flex-target"
	});

	// add Jot botton to the scecond navbar
	if( $("section #jotOpen").length ) {
		$("section #jotOpen").appendTo("#topbar-second > .container > #navbar-button");
		if( $("#jot-popup").is(":hidden")) $("#topbar-second > .container > #navbar-button #jotOpen").hide();
	}

	// show bulk deletion button at network page if checkbox is checked
	$("body").change("input.item-select", function(){
		var checked = false;

		// We need to get all checked items, so it would close the delete button
		// if we uncheck one item and others are still checked.
		// So return checked = true if there is any checked item
		$('input.item-select').each( function() {
			if($(this).is(':checked')) {
				checked = true;
				return false;
			}
		});

		if(checked == true) {
			$("a#item-delete-selected").fadeTo(400, 1);
			$("a#item-delete-selected").show();
		} else {
			$("a#item-delete-selected").fadeTo(400, 0, function(){
				$("a#item-delete-selected").hide();
			});
		}
	});

	//$('ul.flex-nav').flexMenu();

	// initialize the bootstrap tooltips
	$('body').tooltip({
		selector: '[data-toggle="tooltip"]',
		container: 'body',
		animation: true,
		html: true,
		placement: 'auto',
		trigger: 'hover',
		delay: {
			show: 500,
			hide: 100
		}
	});

	// initialize the bootstrap-select
	$('.selectpicker').selectpicker();

	// add search-heading to the seccond navbar
	if( $(".search-heading").length) {
		$(".search-heading").appendTo("#topbar-second > .container > #tabmenu");
	}

	// add search results heading to the second navbar
	// and insert the search value to the top nav search input
	if( $(".search-content-wrapper").length ) {
		// get the text of the heading (we catch the plain text because we don't
		// want to have a h4 heading in the navbar
		var searchText = $(".section-title-wrapper > h2").text();
		// insert the plain text in a <h4> heading and give it a class
		var newText = '<h4 class="search-heading">'+searchText+'</h4>';
		// append the new heading to the navbar
		$("#topbar-second > .container > #tabmenu").append(newText);

		// try to get the value of the original search input to insert it
		// as value in the nav-search-input
		var searchValue = $("#search-wrapper .form-group-search input").val();

		// if the orignal search value isn't available use the location path as value
		if( typeof searchValue === "undefined") {
			// get the location path
			var urlPath = window.location.search
			// and split it up in its parts
			var splitPath = urlPath.split(/(\?search?=)(.*$)/);

			if(typeof splitPath[2] !== 'undefined') {
				// decode the path (e.g to decode %40 to the character @)
				var searchValue = decodeURIComponent(splitPath[2]);
			}
		}

		if( typeof searchValue !== "undefined") {
			$("#nav-search-input-field").val(searchValue);
		}
	}

	// move the "Save the search" button to the second navbar
	$(".search-content-wrapper #search-save-form ").appendTo("#topbar-second > .container > #navbar-button");

	// append the vcard-short-info to the second nav after passing the element
	// with .fn (vcard username). Use scrollspy to get the scroll position.
	if( $("aside .vcard .fn").length) {
		$(".vcard .fn").scrollspy({
			min: $(".vcard .fn").position().top - 50,
			onLeaveTop: function onLeave(element) {
				$("#vcard-short-info").fadeOut(500, function () {
					$("#vcard-short-info").appendTo("#vcard-short-info-wrapper");
				});
			},
			onEnter: function(element) {
				$("#vcard-short-info").appendTo("#nav-short-info");
				$("#vcard-short-info").fadeIn(500);
			},
		});
	}

	// move the forum contact information of the network page into the second navbar
	if( $(".network-content-wrapper > #viewcontact_wrapper-network").length) {
		// get the contact-wrapper element and append it to the second nav bar
		// Note: We need the first() element with this class since at the present time we
		// store also the js template information in the html code and thats why
		// there are two elements with this class but we don't want the js template
		$(".network-content-wrapper > #viewcontact_wrapper-network .contact-wrapper").first().appendTo("#nav-short-info");
	}

	// move heading from network stream to the second navbar nav-short-info section
	if( $(".network-content-wrapper > .section-title-wrapper").length) {
		// get the heading element
		var heading = $(".network-content-wrapper > .section-title-wrapper > h2");
		// get the text of the heading
		var headingContent = heading.text();
		// create a new element with the content of the heading
		var newText = '<h4 class="heading" data-toggle="tooltip" title="'+headingContent+'">'+headingContent+'</h4>';
		// remove the old heading element
		heading.remove(),
		// put the new element to the second nav bar
		$("#topbar-second #nav-short-info").append(newText);
	}

	if( $(".community-content-wrapper").length) {
		// get the heading element
		var heading = $(".community-content-wrapper > h3").first();
		// get the text of the heading
		var headingContent = heading.text();
		// create a new element with the content of the heading
		var newText = '<h4 class="heading">'+headingContent+'</h4>';
		// remove the old heading element
		heading.remove(),
		// put the new element to the second nav bar
		$("#topbar-second > .container > #tabmenu").append(newText);
	}

	// Dropdown menus with the class "dropdown-head" will display the active tab
	// as button text
	$("body").on('click', '.dropdown-head .dropdown-menu li a', function(){
		$(this).closest(".dropdown").find('.btn').html($(this).text() + ' <span class="caret"></span>');
		$(this).closest(".dropdown").find('.btn').val($(this).data('value'));
		$(this).closest("ul").children("li").show();
		$(this).parent("li").hide();
	});

	/* setup onoff widgets */
	// Add the correct class to the switcher according to the input
	// value (On/Off)
	$(".toggle input").each(function(){
		// Get the value of the input element
		val = $(this).val();
		id = $(this).attr("id");

		// The css classes for "on" and "off"
		onstyle = "btn-primary";
		offstyle = "btn-default off";

		// Add the correct class in dependence of input value (On/Off)
		toggleclass = (val == 0 ? offstyle : onstyle);
		$("#"+id+"_onoff").addClass(toggleclass);

	});

	// Change the css class while clicking on the switcher elements
	$(".toggle label, .toggle .toggle-handle").click(function(event){
		event.preventDefault();
		// Get the value of the input element
		var input = $(this).siblings("input");
		var val = 1-input.val();
		var id = input.attr("id");

		// The css classes for "on" and "off"
		var onstyle = "btn-primary";
		var offstyle = "btn-default off";

		// According to the value of the input element we need to decide
		// which class need to be added and removed when changing the switch
		var removedclass = (val == 0 ? onstyle : offstyle);
		var addedclass = (val == 0 ? offstyle : onstyle)
		$("#"+id+"_onoff").addClass(addedclass).removeClass(removedclass);

		// After changing the switch the input element is getting
		// the newvalue
		input.val(val);
	});

	// Set the padding for input elements with inline buttons
	//
	// In Frio we use some input elements where the submit button is visually
	// inside the the input field (through css). We need to set a padding-right
	// to the input element where the padding value would be at least the width
	// of the button. Otherwise long user input would be invisible because it is
	// behind the button.
	$("body").on('click', '.form-group-search > input', function() {
		// Get the width of the button (if the button isn't available
		// buttonWidth will be null
		var buttonWidth = $(this).next('.form-button-search').outerWidth();

		if (buttonWidth) {
			// Take the width of the button and ad 5px
			var newWidth = buttonWidth + 5;
			// Set the padding of the input element according
			// to the width of the button
			$(this).css('padding-right', newWidth);
		}

	});


});

function openClose(theID) {
	var elem = document.getElementById(theID);

	if( $(elem).is(':visible')) {
		$(elem).slideUp(200);
	}
	else {
		$(elem).slideDown(200);
	}
}

function showHide(theID) {
	if(document.getElementById(theID).style.display == "block") {
		document.getElementById(theID).style.display = "none"
	}
	else {
		document.getElementById(theID).style.display = "block"
	}
}


function showHideComments(id) {
	if( $('#collapsed-comments-' + id).is(':visible')) {
		$('#collapsed-comments-' + id).slideUp();
		$('#hide-comments-' + id).html(window.showMore);
		$('#hide-comments-total-' + id).show();
	}
	else {
		$('#collapsed-comments-' + id).slideDown();
		$('#hide-comments-' + id).html(window.showFewer);
		$('#hide-comments-total-' + id).hide();
	}
}


function justifyPhotos() {
	justifiedGalleryActive = true;
	$('#photo-album-contents').justifiedGallery({
		margins: 3,
		border: 0,
		sizeRangeSuffixes: {
			'lt100': '-2',
			'lt240': '-2',
			'lt320': '-2',
			'lt500': '',
			'lt640': '-1',
			'lt1024': '-0'
		}
	}).on('jg.complete', function(e){ justifiedGalleryActive = false; });
}

function justifyPhotosAjax() {
	justifiedGalleryActive = true;
	$('#photo-album-contents').justifiedGallery('norewind').on('jg.complete', function(e){ justifiedGalleryActive = false; });
}

function loadScript(url, callback) {
	// Adding the script tag to the head as suggested before
	var head = document.getElementsByTagName('head')[0];
	var script = document.createElement('script');
	script.type = 'text/javascript';
	script.src = url;

	// Then bind the event to the callback function.
	// There are several events for cross browser compatibility.
	script.onreadystatechange = callback;
	script.onload = callback;

	// Fire the loading
	head.appendChild(script);
}

function random_digits(digits) {
	var rn = "";
	var rnd = "";

	for(var i = 0; i < digits; i++) {
		var rn = Math.round(Math.random() * (9));
		rnd += rn;
	}

	return rnd;
}

// Does we need a ? or a & to append values to a url
function qOrAmp(url) {
	if(url.search('\\?') < 0) {
		return '?';
	} else {
		return '&';
	}
}

function contact_filter(item) {
	// get the html content from the js template of the contact-wrapper
	contact_tpl = unescape($(".javascript-template[rel=contact-template]").html());

	var variables = {
			id:		item.id,
			name:		item.name,
			username:	item.username,
			thumb:		item.thumb,
			img_hover:	item.img_hover,
			edit_hover:	item.edit_hover,
			account_type:	item.account_type,
			photo_menu:	item.photo_menu,
			alt_text:	item.alt_text,
			dir_icon:	item.dir_icon,
			sparkle:	item.sparkle,
			itemurl:	item.itemurl,
			url:		item.url,
			network:	item.network,
			tags:		item.tags,
			details:	item.details,
	};

	// open a new jSmart instance with the template
	var tpl = new jSmart (contact_tpl);

	// replace the variable with the values
	var html = tpl.fetch(variables);

	return html;
}

function filter_replace(item) {

	return item.name;
}

(function( $ ) {
	$.fn.contact_filter = function(backend_url, typ, autosubmit, onselect) {
		if(typeof typ === 'undefined') typ = '';
		if(typeof autosubmit === 'undefined') autosubmit = false;

		// Autocomplete contacts
		contacts = {
			match: /(^)([^\n]+)$/,
			index: 2,
			search: function(term, callback) { contact_search(term, callback, backend_url, typ); },
			replace: filter_replace,
			template: contact_filter,
		};

		this.attr('autocomplete','off');
		var a = this.textcomplete([contacts], {className:'accontacts', appendTo: '#contact-list'});

		a.on('textComplete:select', function(e, value, strategy) { $(".dropdown-menu.textcomplete-dropdown.media-list").show(); });
	};
})( jQuery );


// current time in milliseconds, to send each request to make sure
// we 're not getting 304 response
function timeNow() {
	return new Date().getTime();
}

String.prototype.normalizeLink = function () {
	var ret = this.replace('https:', 'http:');
	var ret = ret.replace('//www', '//');
	return ret.rtrim();
};

function cleanContactUrl(url) {
	var parts = parseUrl(url);

	if(! ("scheme" in parts) || ! ("host" in parts)) {
		return url;
	}

	var newUrl =parts["scheme"] + "://" + parts["host"];

	if("port" in parts) {
		newUrl += ":" + parts["port"];
	}

	if("path" in parts) {
		newUrl += parts["path"];
	}

//	if(url != newUrl) {
//		console.log("Cleaned contact url " + url + " to " + newUrl);
//	}

	return newUrl;
}

function parseUrl (str, component) { // eslint-disable-line camelcase
	//       discuss at: http://locutusjs.io/php/parse_url/
	//      original by: Steven Levithan (http://blog.stevenlevithan.com)
	// reimplemented by: Brett Zamir (http://brett-zamir.me)
	//         input by: Lorenzo Pisani
	//         input by: Tony
	//      improved by: Brett Zamir (http://brett-zamir.me)
	//           note 1: original by http://stevenlevithan.com/demo/parseuri/js/assets/parseuri.js
	//           note 1: blog post at http://blog.stevenlevithan.com/archives/parseuri
	//           note 1: demo at http://stevenlevithan.com/demo/parseuri/js/assets/parseuri.js
	//           note 1: Does not replace invalid characters with '_' as in PHP,
	//           note 1: nor does it return false with
	//           note 1: a seriously malformed URL.
	//           note 1: Besides function name, is essentially the same as parseUri as
	//           note 1: well as our allowing
	//           note 1: an extra slash after the scheme/protocol (to allow file:/// as in PHP)
	//        example 1: parse_url('http://user:pass@host/path?a=v#a')
	//        returns 1: {scheme: 'http', host: 'host', user: 'user', pass: 'pass', path: '/path', query: 'a=v', fragment: 'a'}
	//        example 2: parse_url('http://en.wikipedia.org/wiki/%22@%22_%28album%29')
	//        returns 2: {scheme: 'http', host: 'en.wikipedia.org', path: '/wiki/%22@%22_%28album%29'}
	//        example 3: parse_url('https://host.domain.tld/a@b.c/folder')
	//        returns 3: {scheme: 'https', host: 'host.domain.tld', path: '/a@b.c/folder'}
	//        example 4: parse_url('https://gooduser:secretpassword@www.example.com/a@b.c/folder?foo=bar')
	//        returns 4: { scheme: 'https', host: 'www.example.com', path: '/a@b.c/folder', query: 'foo=bar', user: 'gooduser', pass: 'secretpassword' }

	var query

	var mode = (typeof require !== 'undefined' ? require('../info/ini_get')('locutus.parse_url.mode') : undefined) || 'php'

	var key = [
		'source',
		'scheme',
		'authority',
		'userInfo',
		'user',
		'pass',
		'host',
		'port',
		'relative',
		'path',
		'directory',
		'file',
		'query',
		'fragment'
	]

	// For loose we added one optional slash to post-scheme to catch file:/// (should restrict this)
	var parser = {
		php: new RegExp([
			'(?:([^:\\/?#]+):)?',
			'(?:\\/\\/()(?:(?:()(?:([^:@\\/]*):?([^:@\\/]*))?@)?([^:\\/?#]*)(?::(\\d*))?))?',
			'()',
			'(?:(()(?:(?:[^?#\\/]*\\/)*)()(?:[^?#]*))(?:\\?([^#]*))?(?:#(.*))?)'
		].join('')),
		strict: new RegExp([
			'(?:([^:\\/?#]+):)?',
			'(?:\\/\\/((?:(([^:@\\/]*):?([^:@\\/]*))?@)?([^:\\/?#]*)(?::(\\d*))?))?',
			'((((?:[^?#\\/]*\\/)*)([^?#]*))(?:\\?([^#]*))?(?:#(.*))?)'
		].join('')),
		loose: new RegExp([
			'(?:(?![^:@]+:[^:@\\/]*@)([^:\\/?#.]+):)?',
			'(?:\\/\\/\\/?)?',
			'((?:(([^:@\\/]*):?([^:@\\/]*))?@)?([^:\\/?#]*)(?::(\\d*))?)',
			'(((\\/(?:[^?#](?![^?#\\/]*\\.[^?#\\/.]+(?:[?#]|$)))*\\/?)?([^?#\\/]*))',
			'(?:\\?([^#]*))?(?:#(.*))?)'
		].join(''))
	}

	var m = parser[mode].exec(str)
	var uri = {}
	var i = 14

	while (i--) {
		if (m[i]) {
			uri[key[i]] = m[i]
		}
	}

	if (component) {
		return uri[component.replace('PHP_URL_', '').toLowerCase()]
	}

	if (mode !== 'php') {
		var name = (typeof require !== 'undefined' ? require('../info/ini_get')('locutus.parse_url.queryKey') : undefined) || 'queryKey'
		parser = /(?:^|&)([^&=]*)=?([^&]*)/g
		uri[name] = {}
		query = uri[key[12]] || ''
		query.replace(parser, function ($0, $1, $2) {
			if ($1) {
				uri[name][$1] = $2
			}
		})
	}

	delete uri.source
	return uri
}

// trim function to replace whithespace after the string
String.prototype.rtrim = function() {
	var trimmed = this.replace(/\s+$/g, '');
	return trimmed;
};

/**
 * Scroll the screen to the item element whose id is provided, then highlights it
 *
 * Note: jquery.color.js is required
 *
 * @param {string} elementId The item element id
 * @returns {undefined}
 */
function scrollToItem(elementId) {
	if (typeof elementId === "undefined") {
		return;
	}

	var $el = $(document.getElementById(elementId));
	// Test if the Item exists
	if (!$el.length) {
		return;
	}

	// Define the colors which are used for highlighting
	var colWhite = {backgroundColor:'#F5F5F5'};
	var colShiny = {backgroundColor:'#FFF176'};

	// Get the Item Position (we need to substract 100 to match correct position
	var itemPos = $el.offset().top - 100;

	// Scroll to the DIV with the ID (GUID)
	$('html, body').animate({
		scrollTop: itemPos
	}, 400, function() {
		// Highlight post/commenent with ID  (GUID)
		$el.animate(colWhite, 1000).animate(colShiny).animate(colWhite, 600);
	});
}

// format a html string to pure text
function htmlToText(htmlString) {
	// Replace line breaks with spaces
	var text = htmlString.replace(/<br>/g, ' ');
	// Strip the text out of the html string
	text = text.replace(/<[^>]*>/g, '');

	return text;
}
