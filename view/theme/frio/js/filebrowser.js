/**
 * Filebrowser - Friendica Communications Server
 *
 * Copyright (c) 2010-2015 the Friendica Project
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This code handle user interaction for image/file upload/browser dialog.
 * Is loaded from filebrowser_plain.tpl
 *
 * To load filebrowser in colorbox, call
 *
 *      Dialog.doImageBrowser(eventname, id);
 *
 * or
 *
 *      Dialog.doFileBrowser(eventname, id);
 *
 * where:
 *
 * 		eventname: event name to catch return value
 * 		id: id returned to event handler
 *
 * When user select an item, an event in fired in parent page, on body element
 * The event is named
 *
 * 		fbrowser.<type>.[<eventname>]
 *
 * <type> will be one of "image" or "file", and the event handler will
 * get the following params:
 *
 * 		filemane: filename of item choosed by user
 * 		embed: bbcode to embed element into posts
 * 		id: id from caller code
 *
 * example:
 *
 * 		// open dialog for select an image for a textarea with id "myeditor"
 * 		var id="myeditor";
 * 		Dialog.doImageBrowser("example", id);
 *
 *		// setup event handler to get user selection
 *		$("body").on("fbrowser.image.example", function(event, filename, bbcode, id) {
 *			// close colorbox
 *			$.colorbox.close();
 *			// replace textxarea text with bbcode
 *			$(id).value = bbcode;
 *		});
 **/


/*
 * IMPORTANT
 *
 *  This is a modified version to work with
 *  the frio theme.and bootstrap modals
 *
 *  The origninal file is under:
 *  js/filebrowser.js
 *
 */


var FileBrowser = {
	nickname : "",
	type : "",
	event: "",
	folder: "",
	id : null,

	init: function(nickname, type, hash) {
		FileBrowser.nickname = nickname;
		FileBrowser.type = type;
		FileBrowser.event = "fbrowser."+type;

		if (hash!=="") {
			var h = hash.replace("#","");
			var destination = h.split("-")[0];
			FileBrowser.id = h.split("-")[1];
			FileBrowser.event = FileBrowser.event + "." + destination;
			if (destination === "comment") {
				// Get the comment textimput field
				var commentElm = document.getElementById("comment-edit-text-" + FileBrowser.id);
			}
		};

		console.log("FileBrowser: " + nickname,  type, FileBrowser.event, FileBrowser.id);

		FileBrowser.postLoad();

		$(".error .close").on("click", function(e) {
			e.preventDefault();
			$(".error").addClass("hidden");
		});

		// Click on album link
		$(".fbrowser").on("click", ".folders a, .path a", function(e) {
			e.preventDefault();
			var url = baseurl + "/fbrowser/" + FileBrowser.type + "/" + this.dataset.folder + "?mode=none";
			FileBrowser.folder = this.dataset.folder;

			FileBrowser.loadContent(url);
		});

		//Embed on click
		$(".fbrowser").on('click', ".photo-album-photo-link", function(e) {
			e.preventDefault();

			var embed = "";
			if (FileBrowser.type === "image") {
				embed = "[url=" + this.dataset.link + "][img]" + this.dataset.img + "[/img][/url]";
			}
			if (FileBrowser.type === "file") {
				// attachment links are "baseurl/attach/id"; we need id
				embed = "[attachment]" + this.dataset.link.split("/").pop() + "[/attachment]";
			}

			// Delete prefilled Text of the comment input
			// Note: not the best solution but function commentOpenUI don't
			// work as expected (we need a way to wait until commentOpenUI would be finished).
			// As for now we insert pieces of this function here
			if ((commentElm !== null) && (typeof commentElm !== "undefined")) {
				if (commentElm.value === "") {
					$("#comment-edit-text-" + FileBrowser.id).addClass("comment-edit-text-full").removeClass("comment-edit-text-empty");
					$("#comment-edit-submit-wrapper-" + FileBrowser.id).show();
					$("#comment-edit-text-" + FileBrowser.id).attr('tabindex','9');
					$("#comment-edit-submit-" + FileBrowser.id).attr('tabindex','10');
				}

			}

			console.log(FileBrowser.event, this.dataset.filename, embed, FileBrowser.id);

			parent.$("body").trigger(FileBrowser.event, [
				this.dataset.filename,
				embed,
				FileBrowser.id,
				this.dataset.img
			]);

			// Close model
			$('#modal').modal('hide');
			// Update autosize for this textarea
			autosize.update($(".text-autosize"));
		});

		// EventListener for switching between image and file mode
		$(".fbrowser").on('click', ".fbswitcher .btn", function(e) {
			e.preventDefault();
			FileBrowser.type = this.getAttribute("data-mode");
			$(".fbrowser").removeClass().addClass("fbrowser " + FileBrowser.type);
			url = baseurl + "/fbrowser/" + FileBrowser.type + "?mode=none";

			FileBrowser.loadContent(url);
		});
	},

	// Initialize the AjaxUpload for the upload buttons
	uploadButtons: function() {
		if ($("#upload-image").length) {
			// To get the albumname we need to convert it from hex
			var albumname = hex2bin(FileBrowser.folder);
			//AjaxUpload for images
			var image_uploader = new window.AjaxUpload(
				'upload-image',
				{	action: 'wall_upload/' + FileBrowser.nickname + '?response=json&album=' + albumname,
					name: 'userfile',
					responseType: 'json',
					onSubmit: function(file, ext) {
						$(".fbrowser-content").hide();
						$(".fbrowser .profile-rotator-wrapper").show();
						$(".error").addClass('hidden');
					},
					onComplete: function(file,response) {
						if (response['error'] != undefined) {
							$(".error span").html(response['error']);
							$(".error").removeClass('hidden');
							$(".fbrowser .profile-rotator-wrapper").hide();
							return;
						}

//						location = baseurl + "/fbrowser/image/?mode=none"+location['hash'];
//						location.reload(true);

						var url = baseurl + "/fbrowser/" + FileBrowser.type + "/" + FileBrowser.folder + "?mode=none";
						// load new content to fbrowser window
						FileBrowser.loadContent(url);
					}
				}
			);
		}

		if ($("#upload-file").length) {
			//AjaxUpload for files
			var file_uploader = new window.AjaxUpload(
				'upload-file',
				{	action: 'wall_attach/' + FileBrowser.nickname + '?response=json',
					name: 'userfile',
					onSubmit: function(file, ext) {
						$(".fbrowser-content").hide();
						$(".fbrowser .profile-rotator-wrapper").show();
						$(".error").addClass('hidden');
					},
					onComplete: function(file,response) {
						if (response['error']!= undefined) {
							$(".error span").html(response['error']);
							$(".error").removeClass('hidden');
							$('#profile-rotator').hide();
							return;
						}

//						location = baseurl + "/fbrowser/file/?mode=none"+location['hash'];
//						location.reload(true);

						var url = baseurl + "/fbrowser/" + FileBrowser.type + "?mode=none";
						// Load new content to fbrowser window
						FileBrowser.loadContent(url);
					}
				}
			);
		}
	},

	// Stuff which should be executed if ne content was loaded
	postLoad: function() {
		FileBrowser.initGallery();
		$(".fbrowser .fbswitcher .btn").removeClass("active");
		$(".fbrowser .fbswitcher [data-mode=" + FileBrowser.type + "]").addClass("active");
		// We need to add the AjaxUpload to the button
		FileBrowser.uploadButtons();
		
	},

	// Load new content (e.g. change photo album)
	loadContent: function(url) {
		$(".fbrowser-content").hide();
		$(".fbrowser .profile-rotator-wrapper").show();

		// load new content to fbrowser window
		$(".fbrowser").load(url, function(responseText, textStatus) {
			$(".profile-rotator-wrapper").hide();
			if (textStatus === 'success') {
				$(".fbrowser_content").show();
				FileBrowser.postLoad();
			}
		});
	},

	// Initialize justified Gallery
	initGallery: function() {
		$(".fbrowser.image .fbrowser-content-container").justifiedGallery({
			'rowHeight': 80,
			'margins': 4,
			'border': 0
		});
	}
};
