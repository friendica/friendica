// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later

$(document).ready(function () {
	$("#contact_allow, #contact_deny, #circle_allow, #circle_deny")
		.change(function () {
			var selstr;
			$(
				"#contact_allow option:selected, #contact_deny option:selected, #circle_allow option:selected, #circle_deny option:selected",
			).each(function () {
				selstr = $(this).html();
				$("#jot-perms-icon").removeClass("unlock").addClass("lock");
				$("#jot-public").hide();
			});
			if (selstr == null) {
				$("#jot-perms-icon").removeClass("lock").addClass("unlock");
				$("#jot-public").show();
			}
		})
		.trigger("change");

	// Click event listener for the album edit link/button.
	$("body").on("click", "#album-edit-link", function () {
		var modalUrl = $(this).attr("data-modal-url");

		if (typeof modalUrl !== "undefined") {
			addToModal(modalUrl, "photo-album-edit-wrapper");
		}
	});

	// Click event listener for the album drop link/button.
	$("body").on("click", "#album-drop-link", function () {
		var modalUrl = $(this).attr("data-modal-url");

		if (typeof modalUrl !== "undefined") {
			addToModal(modalUrl);
		}
	});
});

$(window).load(function () {
	// Get picture dimensions
	var pheight = $("#photo-photo img").height();
	var pwidth = $("#photo-photo img").width();

	// Append the dimensions of the picture to the css of the photo-photo div
	// we do this to make it possible to have overlay navigation buttons for the photo
	$("#photo-photo").css({
		width: pwidth,
		height: pheight,
	});
});
// @license-end
