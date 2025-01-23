// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

Dropzone.autoDiscover = false;
var DzFactory = function (max_imagesize) {
	this.createDropzone = function(dropSelector, textareaElementId) {
		return new Dropzone(dropSelector, {
			paramName: 'userfile', // The name that will be used to transfer the file
			maxFilesize: max_imagesize, // MB
			url: '/media/photo/upload?album=',
			acceptedFiles: 'image/*,video/*,audio/*,application/*',
			clickable: true,
			dictDefaultMessage: dzStrings.dictDefaultMessage,
			dictFallbackMessage: dzStrings.dictFallbackMessage,
			dictFallbackText: dzStrings.dictFallbackText,
			dictFileTooBig: dzStrings.dictFileTooBig,
			dictInvalidFileType: dzStrings.dictInvalidFileType,
			dictResponseError: dzStrings.dictResponseError,
			dictCancelUpload: dzStrings.dictCancelUpload,
			dictUploadCanceled: dzStrings.dictUploadCanceled,
			dictCancelUploadConfirmation: dzStrings.dictCancelUploadConfirmation,
			dictRemoveFile: dzStrings.dictRemoveFile,
			dictMaxFilesExceeded: dzStrings.dictMaxFilesExceeded,
			accept: function(file, done) {
					const targetTextarea = document.getElementById(textareaElementId);
					if (targetTextarea.setRangeText) {
						targetTextarea.setRangeText("\n[!upload-" + file.name + "]\n", targetTextarea.selectionStart, targetTextarea.selectionEnd, "end");
					}
				done();
			},
			init: function() {
				this.on("processing", function(file) {
					switch(file.type) {
						case String(file.type.match(/image\/.*/)):
							this.options.url = "/media/photo/upload?album=";
							break;
						default:
							this.options.url = "/media/attachment/upload?response=json";
					}
				});
				this.on('success', function(file, serverResponse) {
					const targetTextarea = document.getElementById(textareaElementId);
					if (targetTextarea.setRangeText) {
						//if setRangeText function is supported by current browser
						let u = "[!upload-" + file.name + "]";
						let srp = serverResponse;
						if (typeof serverResponse === 'object' &&
						serverResponse.constructor === Object) {
							if (serverResponse.ok) {
								srp = "[attachment]" +
									window.location.protocol +
									"//" +
									window.location.host +
									"/attach/" +
									serverResponse.id +
									"[/attachment]";
							} else {
								srp = "Upload failed";
							}
						}
						let c = targetTextarea.selectionStart;
						if (c > targetTextarea.value.indexOf(u)) {
							c = c + serverResponse.length - u.length;
						}
						targetTextarea.setRangeText(srp, targetTextarea.value.indexOf(u), targetTextarea.value.indexOf(u) + u.length);
						targetTextarea.selectionStart = c;
						targetTextarea.selectionEnd = c;
					} else {
						targetTextarea.focus();
						document.execCommand('insertText', false /*no UI*/, serverResponse);
					}
				});
				this.on('complete', function(file) {
					const dz = this;
					// Remove just uploaded file from dropzone, makes interface more clear.
					// Image can be seen in posting-preview
					// We need preview to get optical feedback about upload-progress.
					// you see success, when the bb-code link for image is inserted
					setTimeout(function(){
						dz.removeFile(file);
					},5000);
				});
			},
			paste: function(event){
				const items = (event.clipboardData || event.originalEvent.clipboardData).items;
				items.forEach((item) => {
					if (item.kind === 'file') {
						// adds the file to your dropzone instance
						dz.addFile(item.getAsFile());
					}
				})
			},
		});
	};

	this.copyPaste = function(event, dz) {
		const items = (event.clipboardData || event.originalEvent.clipboardData).items;
		items.forEach((item) => {
			if (item.kind === 'file') {
				// adds the file to your dropzone instance
				dz.addFile(item.getAsFile());
			}
		})
	};

	this.setupDropzone = function(dropSelector, textareaElementId) {
		const self = this;
		var dropzone = this.createDropzone(dropSelector, textareaElementId);
		$(dropSelector).on('paste', function(event) {
			self.copyPaste(event, dropzone);
		})
	};
}

