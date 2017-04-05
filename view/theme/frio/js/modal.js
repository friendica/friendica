/* 
 * @brief contains functions for bootstrap modal handling
 */
$(document).ready(function(){
	// Clear bs modal on close
	// We need this to prevent that the modal displays old content
	$('body, footer').on('hidden.bs.modal', '.modal', function () {
		$(this).removeData('bs.modal');
		$("#modal-title").empty();
		$('#modal-body').empty();
		// Remove the file browser from jot (else we would have problems
		// with AjaxUpload
		$(".fbrowser").remove();
		// Remove the AjaxUpload element
		$("[name=userfile]").parent().remove();
	});

	// Clear bs modal on close
	// We need this to prevent that the modal displays old content
	$('body').on('hidden.bs.modal', '#jot-modal', function () {
		// restore cached jot at its hidden position ("#jot-content")
		$("#jot-content").append(jotcache);
		// clear the jotcache
		jotcache = '';
	});

	// Add Colorbox for viewing Network page images
	//var cBoxClasses = new Array();
	$("body").on("click", ".wall-item-body a img", function(){
		var aElem = $(this).parent();
		var imgHref = aElem.attr("href");

		// We need to make sure we only put a Colorbox on links to Friendica images
		// We'll try to do this by looking for links of the form
		// .../photo/ab803d8eg08daf85023adfec08 (with nothing more following), in hopes
		// that that will be unique enough
		if(imgHref.match(/\/photo\/[a-fA-F0-9]+(-[0-9]\.[\w]+?)?$/)) {

			// Add a unique class to all the images of a certain post, to allow scrolling through
			var cBoxClass = $(this).closest(".wall-item-body").attr("id") + "-lightbox";
			$(this).addClass(cBoxClass);

//			if( $.inArray(cBoxClass, cBoxClasses) < 0 ) {
//				cBoxClasses.push(cBoxClass);
//			}

			aElem.colorbox({
				maxHeight: '90%',
				photo: true, // Colorbox doesn't recognize a URL that don't end in .jpg, etc. as a photo
				rel: cBoxClass //$(this).attr("class").match(/wall-item-body-[\d]+-lightbox/)[0]
			});
		}
	});

	// Navbar login
	$("body").on("click", "#nav-login", function(e){
		e.preventDefault();
		Dialog.show(this.href, this.dataset.originalTitle || this.title);
	});

	// Jot nav menu.
	$("body").on("click", "#jot-modal .jot-nav li .jot-nav-lnk", function(e){
		e.preventDefault();
		toggleJotNav(this);
	});

	// bookmarklet page needs an jot modal which appears automatically
	if(window.location.pathname.indexOf("/bookmarklet") >=0 && $("#jot-modal").length){
		jotShow();
	}

	// Open filebrowser for elements with the class "image-select"
	// The following part handles the filebrowser for field_fileinput.tpl
	$("body").on("click", ".image-select", function(){
		// set a extra attribute to mark the clicked button
		this.setAttribute("image-input", "select");
		Dialog.doImageBrowser("input");
	});

	// Insert filebrowser images into the input field (field_fileinput.tpl)
	$("body").on("fbrowser.image.input", function(e, filename, embedcode, id, img) {
		// select the clicked button by it's attribute
		var elm = $("[image-input='select']");
		// select the input field which belongs to this button
		var input = elm.parent(".input-group").children("input");
		// remove the special indicator attribut from the button
		elm.removeAttr("image-input");
		// inserte the link from the image into the input field
		input.val(img);
		
	});
});

// overwrite Dialog.show from main js to load the filebrowser into a bs modal
Dialog.show = function(url, title) {
	if (typeof(title) === 'undefined') {
		title = "";
	}

	var modal = $('#modal').modal();
	modal.find("#modal-header h4").html(title);
	modal
		.find('#modal-body')
		.load(url, function (responseText, textStatus) {
			if ( textStatus === 'success' || 
				textStatus === 'notmodified') 
			{
				modal.show();

				$(function() {Dialog._load(url);});
			}
		});
};

// overwrite the function _get_url from main.js
Dialog._get_url = function(type, name, id) {
	var hash = name;
	if (id !== undefined) hash = hash + "-" + id;
	return "fbrowser/"+type+"/?mode=none#"+hash;
};

// does load the filebrowser into the jot modal
Dialog.showJot = function() {
	var type = "image";
	var name = "main";

	var url = Dialog._get_url(type, name);
	if(($(".modal-body #jot-fbrowser-wrapper .fbrowser").length) < 1 ) {
		// load new content to fbrowser window
		$("#jot-fbrowser-wrapper").load(url,function(responseText, textStatus){
			if ( textStatus === 'success' || 
				textStatus === 'notmodified') 
			{
				$(function() {Dialog._load(url);});
			}
		});
	}
};

// init the filebrowser after page load
Dialog._load = function(url) {
	// get nickname & filebrowser type from the modal content
	var nickname = $("#fb-nickname").attr("value");
	var type = $("#fb-type").attr("value");

	// try to fetch the hash form the url
	var match = url.match(/fbrowser\/[a-z]+\/\?mode=none(.*)/);
	if (match===null) return; //not fbrowser
	var hash = match[1];

	// initialize the filebrowser
	var jsbrowser = function() {
		FileBrowser.init(nickname, type, hash);
	};
	loadScript("view/theme/frio/js/filebrowser.js", jsbrowser);
};

/**
 * @brief Add first element with the class "heading" as modal title
 * 
 * Note: this should be really done in the template
 * and is the solution where we havent done it until this
 * moment or where it isn't possible because of design
 */
function loadModalTitle() {
	// clear the text of the title
	$("#modal-title").empty();

	// hide the first element with the class "heading" of the modal body
	$("#modal-body .heading").first().hide();

	var title = "";

	// get the text of the first element with "heading" class
	title = $("#modal-body .heading").first().text();

	// for event modals we need some speacial handling
	if($("#modal-body .event-wrapper .event-summary").length) {
		title = '<i class="fa fa-calendar" aria-hidden="true"></i>&nbsp;';
		var eventsum = $("#modal-body .event-wrapper .event-summary").text();
		title = title + eventsum;
	}

	// and append it to modal title
	if (title!=="") {
		$("#modal-title").append(title);
	}
}

// This function loads html content from a friendica page
// into a modal
function addToModal(url) {
	var char = qOrAmp(url);

	url = url + char + 'mode=none';
	var modal = $('#modal').modal();

	modal
		.find('#modal-body')
		.load(url, function (responseText, textStatus) {
			if ( textStatus === 'success' || 
				textStatus === 'notmodified') 
			{
				modal.show();

				//Get first element with the class "heading"
				//and use it as title
				loadModalTitle();
			}
		});
}

// Add a element (by it's id) to a bootstrap modal
function addElmToModal(id) {
	var elm = $(id).html();
	var modal = $('#modal').modal();

	modal
		.find('#modal-body')
		.append(elm)
		.modal.show;

	loadModalTitle();
}

// function to load the html from the edit post page into
// the jot modal
function editpost(url) {
	// next to normel posts the post can be an event post. The event posts don't
	// use the normal Jot modal. For event posts we will use a normal modal
	// But first we have to test if the url links to an event. So we will split up
	// the url in its parts
	var splitURL = parseUrl(url);
	// Test if in the url path containing "events/event". If the path containing this
	// expression then we will call the addToModal function and exit this function at
	// this point
	if (splitURL.path.indexOf('events/event') > -1) {
		addToModal(splitURL.path);
		return;
	}

	var modal = $('#jot-modal').modal();
	url = url + " #jot-sections";

	//var rand_num = random_digits(12);
	$(".jot-nav .jot-perms-lnk").parent("li").addClass("hidden");

	// For editpost we load the modal html of "jot-sections" of the edit page. So we would have two jot forms in
	// the page html. To avoid js conflicts we store the original jot in the variable jotcache.
	// After closing the modal original jot should be restored at its orginal position in the html structure.
	jotcache = $("#jot-content > #jot-sections");

	// remove the original Jot as long as the edit Jot is open
	jotcache.remove();

	// add the class "edit" to the modal to have some kind of identifier to
	// have the possibility to e.g. put special event-listener
	$("#jot-modal").addClass("edit-jot");

	jotreset();

	modal
		.find('#jot-modal-content')
		.load(url, function (responseText, textStatus) {
			if ( textStatus === 'success' || 
				textStatus === 'notmodified') 
			{
				// get the item type and hide the input for title and category if it isn't needed
				var type = $(responseText).find("#profile-jot-form input[name='type']").val();
				if(type === "wall-comment" || type === "remote-comment")
				{
					// hide title and category input fields because we don't
					$("#profile-jot-form #jot-title-wrap").hide();
					$("#profile-jot-form #jot-category-wrap").hide();
				}

				modal.show();
				$("#jot-popup").show();
			}
		});
}

// remove content from the jot modal
function jotreset() {
	// Clear bs modal on close
	// We need this to prevent that the modal displays old content
	$('body').on('hidden.bs.modal', '#jot-modal.edit-jot', function () {
		$(this).removeData('bs.modal');
		$(".jot-nav .jot-perms-lnk").parent("li").removeClass("hidden");
		$("#profile-jot-form #jot-title-wrap").show();
		$("#profile-jot-form #jot-category-wrap").show();

		// the following was commented out because it is needed anymore
		// because we changed the behavior at an other place
	//		var rand_num = random_digits(12);
	//		$('#jot-title, #jot-category, #profile-jot-text').val("");
	//		$( "#profile-jot-form input[name='type']" ).val("wall");
	//		$( "#profile-jot-form input[name='post_id']" ).val("");
	//		$( "#profile-jot-form input[name='post_id_random']" ).val(rand_num);

		// remove the "edit-jot" class so we can the standard behavior on close
		$("#jot-modal.edit-jot").removeClass("edit-jot");
		$("#jot-modal-content").empty();
	});
}

// Give the active "jot-nav" list element the class "active"
function toggleJotNav (elm) {
	// Get the ID of the tab panel which should be activated
	var tabpanel = elm.getAttribute("aria-controls");
	var cls = hasClass(elm, "jot-nav-lnk-mobile");

	// Select all li of jot-nav and remove the active class
	$(elm).parent("li").siblings("li").removeClass("active");
	// Add the active class to the parent of the link which was selected
	$(elm).parent("li").addClass("active");

	// Minimize all tab content wrapper and activate only the selected
	// tab panel
	$('#jot-modal [role=tabpanel]').addClass("minimize").attr("aria-hidden" ,"true");
	$('#jot-modal #' + tabpanel).removeClass("minimize").attr("aria-hidden" ,"false");

	// Set the aria-selected states
	$("#jot-modal .nav-tabs .jot-nav-lnk").attr("aria-selected", "false");
	elm.setAttribute("aria-selected", "true");

	// For some some tab panels we need to execute other js functions
	if (tabpanel === "jot-preview-content") {
		preview_post();
	} else if (tabpanel === "jot-fbrowser-wrapper") {
		$(function() {
			Dialog.showJot();
		});
	}

	// If element is a mobile dropdown nav menu we need to change the botton text
	if (cls) {
		toggleDropdownText(elm);
	}
}

// Wall Message needs a special handling because in some cases
// it redirects you to your own server. In such cases we can't
// load it into a modal
function openWallMessage(url) {
	// split the the url in its parts
	var parts = parseUrl(url);

	// If the host isn't the same we can't load it in a modal.
	// So we will go to to the url directly
	if( ("host" in parts) && (parts.host !== window.location.host)) {
		window.location.href = url;
	} else {
		// otherwise load the wall message into a modal
		addToModal(url);
	}
}
