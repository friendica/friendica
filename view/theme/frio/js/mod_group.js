
/**
 * @file view/theme/frio/js/mod_group.js
 * @brief The javascript for the group module
 */


$(document).ready(function(){
	// Add an event listeners on buttons for switching the contact list view
	$("body").on("click", ".group-list-switcher", function(){
		switchGroupViewMode(this);
	});
});

/**
 * @brief Change the group membership of the contacts and fetch the new grup list
 * as html
 * 
 * @param {int} gid The group ID
 * @param {int} cid The contact ID
 * @param {string} sec_token The security token
 * 
 * @returns {undefined}
 */
function groupChangeMember(gid, cid, sec_token) {
	$(".tooltip").tooltip("hide");
	$("body").css("cursor", "wait");
	$.get('group/' + gid + '/' + cid + "?t=" + sec_token, function(data) {
			// Insert the new group member list
			$("#group-update-wrapper").html(data);

			// Apply the actual gropu list view mode to the new
			// group list html
			var activeMode = $(".group-list-switcher.active");
			switchGroupViewMode(activeMode[0]);

			$("body").css("cursor", "auto");
	});
}

/**
 * @brief Change the group list view mode
 * 
 * @param {object} elm The button element of the view mode switcher
 * @returns {undefined}
 */
function switchGroupViewMode(elm) {
		// Remove the active class from group list switcher buttons
		$(".group-list-switcher").removeClass("active");
		// And add it to the active button element
		$(elm).addClass("active");

		// Add or remove the css classes for the group list with regard to the active view mode
		if (elm.id === "group-list-small") {			
			$("#contact-group-list > li").addClass("shortmode col-lg-6 col-md-6 col-sm-6 col-xs-12");
		} else {
			$("#contact-group-list > li").removeClass("shortmode col-lg-6 col-md-6 col-sm-6 col-xs-12");
		}
}

/**
 * @brief Filter the group member list for contacts
 * 
 * @returns {undefined}
 */
function filterList() {
	// Declare variables
	var input, filter, ul, li, a, i;
	input = document.getElementById("contacts-search");
	filter = input.value.toUpperCase();
	li = document.querySelectorAll("#contact-group-list>li");

	// Loop through all list items, and hide those who don't match the search query
	for (i = 0; i < li.length; i++) {
		// Get the heading element
		var mh = li[i].getElementsByClassName("media-heading")[0];
		// The first child of the heading element should contain
		// the text which we want to filter
		a = mh.firstChild;
		if (a.innerHTML.toUpperCase().indexOf(filter) > -1) {
			li[i].style.display = "";
		} else {
			li[i].style.display = "none";
		}
	}
}
