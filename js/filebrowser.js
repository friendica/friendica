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

var FileBrowser = {
	nickname : "",
	type : "",
	event: "",
	id : null,

	init: function(nickname, type) {
		FileBrowser.nickname = nickname;
		FileBrowser.type = type;
		FileBrowser.event = "fbrowser."+type;
		if (location['hash']!=="") {
			var h = location['hash'].replace("#","");
			FileBrowser.event = FileBrowser.event + "." + h.split("-")[0];
			FileBrowser.id = h.split("-")[1];
		}

		console.log("FileBrowser:", nickname, type,FileBrowser.event, FileBrowser.id );

		$(".error a.close").on("click", function(e) {
			e.preventDefault();
			$(".error").addClass("hidden");
		});

		$(".folders a, .path a").on("click", function(e){
			e.preventDefault();
			var url = baseurl + "/fbrowser/" + FileBrowser.type + "/" + this.dataset.folder + "?mode=minimal" + location['hash'];
			location.href = url;
		});

		$(".photo-album-photo-link").on('click', function(e){
			e.preventDefault();

			var embed = "";
			if (FileBrowser.type == "image") {
				embed = "[url="+this.dataset.link+"][img]"+this.dataset.img+"[/img][/url]";
			}
			if (FileBrowser.type=="file") {
				// attachment links are "baseurl/attach/id"; we need id
				embed = "[attachment]"+this.dataset.link.split("/").pop()+"[/attachment]";
			}
			console.log(FileBrowser.event, this.dataset.filename, embed, FileBrowser.id);
			parent.$("body").trigger(FileBrowser.event, [
				this.dataset.filename,
				embed,
				FileBrowser.id
			]);

		});

		if ($("#upload-image").length)
			var image_uploader = new window.AjaxUpload(
				'upload-image',
				{ action: 'wall_upload/'+FileBrowser.nickname+'?response=json',
					name: 'userfile',
					responseType: 'json',
					onSubmit: function(file,ext) { $('#profile-rotator').show(); $(".error").addClass('hidden'); },
					onComplete: function(file,response) {
						if (response['error']!= undefined) {
							$(".error span").html(response['error']);
							$(".error").removeClass('hidden');
							$('#profile-rotator').hide();
							return;
						}
						location = baseurl + "/fbrowser/image/?mode=minimal"+location['hash'];
						location.reload(true);
					}
				}
			);

		if ($("#upload-file").length)
			var file_uploader = new window.AjaxUpload(
				'upload-file',
				{ action: 'wall_attach/'+FileBrowser.nickname+'?response=json',
					name: 'userfile',
					onSubmit: function(file,ext) { $('#profile-rotator').show(); $(".error").addClass('hidden'); },
					onComplete: function(file,response) {
						if (response['error']!= undefined) {
							$(".error span").html(response['error']);
							$(".error").removeClass('hidden');
							$('#profile-rotator').hide();
							return;
						}
						location = baseurl + "/fbrowser/file/?mode=minimal"+location['hash'];
						location.reload(true);
					}
				}
		);
	}
};

