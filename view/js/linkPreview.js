// @license magnet:?xt=urn:btih:d3d9a9a6595521f9666a5e94cc830dab83b65699&dn=expat.txt Expat
// @license magnet:?xt=urn:btih:cf05388f2679ee054f2beb29a391d25f4e673ac3&dn=gpl-2.0.txt GPL
/**
 * SPDX-FileCopyrightText: 2014 Leonardo Cardoso (http://leocardz.com), Rabuzarus to use it in the decentralized social network Friendica (https://friendi.ca).
 *
 * SPDX-License-Identifier: MIT OR GPL-2.0-only
 *
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 *
 * Restructured by Rabzuarus (https://friendica.kommune4.de/profile/rabuzarus)
 * to use it in the decentralized social network Friendica (https://friendi.ca).
 *
 * Version: 1.4.0
 */
(function ($) {
	$.fn.linkPreview = function (options) {
		var opts = jQuery.extend({}, $.fn.linkPreview.defaults, options);

		var id = $(this).attr('id');

		var previewTpl = '\
			<div id="preview_' + id + '" class="preview {0}">\
				{1}\
				<input type="hidden" name="has_attachment" id="hasAttachment_' + id + '" value="{2}" />\
				<input type="hidden" name="attachment_url" id="attachmentUrl_' + id + '" value="{3}" />\
				<input type="hidden" name="attachment_type" id="attachmentType_' + id + '" value="{4}" />\
			</div>';

		var attachmentTpl = '\
			<hr class="previewseparator">\
			<div id="closePreview_' + id + '" title="Remove" class="closePreview" >\
				<button type="button" class="previewActionBtn">×</button>\
			</div>\
			<div id="previewImages_' + id + '" class="previewImages">\
				<div id="previewImgBtn_' + id + '" class="previewImgBtn">\
					<button type="button" id="previewChangeImg_' + id + '" class="buttonChangeDeactivate previewActionBtn" style="display: none">\
						<i class="fa fa-exchange" aria-hidden="true"></i>\
					</button>\
				</div>\
				<div id="previewImage_' + id + '" class="previewImage">\
				</div>\
				<input type="hidden" id="photoNumber_' + id + '" class="photoNumber" value="0" />\
				<input type="hidden" name="attachment_img_src" id="attachmentImageSrc_' + id + '" value="" />\
				<input type="hidden" name="attachment_img_width" id="attachmentImageWidth_' + id + '" value="0" />\
				<input type="hidden" name="attachment_img_height" id="attachmentImageHeight_' + id + '" value="0" />\
			</div>\
			<div id="previewContent_' + id + '" class="previewContent">\
				<h4 id="previewTitle_' + id + '" class="previewTitle"></h4>\
				<blockquote id="previewDescription_' + id + '" class="previewDescription"></blockquote>\
				<div id="hiddenDescription_' + id + '" class="hiddenDescription"></div>\
				<sup id="previewUrl_' + id + '" class="previewUrl"></sup>\
			</div>\
			<div class="clear"></div>\
			<hr class="previewseparator">';
		var text;
		var binurl;
		var block = false;
		var blockTitle = false;
		var blockDescription = false;
		var cache = {};
		var images = "";
		var isExtern = false;
		var photoNumber = 0;
		var firstPosted = false;
		var isActive = false;
		var isCrawling = false;
		var defaultTitle = opts.defaultTitle;
		var defaultDescription = opts.defaultDescription;

		/**
		 * Initialize the plugin
		 *
		 * @returns {void}
		 */
		var init = function() {
			$('#' + id).bind({
				paste: function () {
					setTimeout(function () {
						crawlText();
					}, 100);
				},
				keyup: function (e) {
					// on enter, space, ctrl
					if ((e.which === 13 || e.which === 32 || e.which === 17)) {
						crawlText();
					}
				}
			});

			// Check if we have already attachment bbcode in the textarea
			// and add it to the attachment preview.
			var content = $('#' + id).val();
			addBBCodeToPreview(content);
		};

		/**
		 * Reset some values.
		 *
		 * @returns {void}
		 */
		var resetPreview = function() {
			$('#hasAttachment_' + id).val(0);
			photoNumber = 0;
			images = "";
		};

		/**
		 * Crawl a text string if it contains an url and try
		 * to attach it.
		 *
		 * If no text is passed to crawlText() we take
		 * the previous word before the cursor of the textarea.
		 *
		 * @param {string} text (optional)
		 * @returns {void}
		 */
		var crawlText = function (text) {
			block = false;
			images = '';
			isExtern = false;

			// If no text is passed to crawlText() we
			// take the previous word before the cursor.
			if (typeof text === 'undefined') {
				text = getPrevWord(id);
			} else {
				isExtern = true;
			}

			if (trim(text) !== "" && block === false && urlRegex.test(text)) {
				binurl = bin2hex(text);
				block = true;

				isCrawling = true;
				$('#profile-rotator').show();

				if (binurl in cache) {
					isCrawling = false;
					processContentData(cache[binurl]);
				} else {
					getContentData(binurl, processContentData);
				}
			}
		};

		/**
		 * Process the attachment data according to
		 * its content type (image, audio, video, attachment)
		 *
		 * @param {object} result
		 * @returns {void}
		 */
		var processContentData = function(result) {
			if (result.contentType === 'image') {
				insertImage(result.data);
			} else if (result.contentType === 'audio') {
				insertAudio(result.data);
			} else if (result.contentType === 'video') {
				insertVideo(result.data);
			} else if ((result.contentType === 'attachment' || result.contentType === 'embed') && !isActive) {
				insertAttachment(result.data);
			} else if (result.contentType === 'embed') {
				insertEmbed(result.data);
			} else {
				insertUrl(result.data);
			}
			$('#profile-rotator').hide();
		};

		/**
		 * Fetch the content of link which should be attached.
		 *
		 * @param {string} binurl Link which should be attached as hexadecimal string.
		 * @param {type} callback
		 * @returns {void}
		 */
		var getContentData = function(binurl, callback, attach) {
			$.get('parseurl?binurl='+ binurl + '&format=json', function (answer) {
				obj = sanitizeInputData(answer);

				// Put the data into a cache
				cache[binurl] = obj;

				callback(obj, attach);

				isCrawling = false;
			});
		};

		/*
		 * Add a [img] bbtag with the image url to the jot editor.
		 *
		 * @param {type} data
		 * @returns {void}
		 */
		var insertImage = function(data) {
			if (!isExtern) {
				return;
			}
			var bbcode = '[img=' + data.url + '][/img]';
			addeditortext(bbcode);
		};

		/*
		 * Add a [audio] bbtag with the audio url to the jot editor.
		 *
		 * @param {type} data
		 * @returns {void}
		 */
		var insertAudio = function(data) {
			if (!isExtern) {
				return;
			}
			var bbcode = '[audio]' + data.url + '[/audio]';
			addeditortext(bbcode);
		};

		/*
		 * Add a [video] bbtag with the video url to the jot editor.
		 *
		 * @param {type} data
		 * @returns {void}
		 */
		var insertVideo = function(data) {
			if (!isExtern) {
				return;
			}
			var bbcode = '[video]' + data.url + '[/video]';
			addeditortext(bbcode);
		};

		/*
		 * Add a [embed] bbtag with the embed url to the jot editor.
		 *
		 * @param {type} data
		 * @returns {void}
		 */
		var insertEmbed = function(data) {
			if (!isExtern) {
				return;
			}
			var bbcode = '[embed]' + data.url + '[/embed]';
			addeditortext(bbcode);
		};

		/*
		 * Add a [url] bbtag with the url to the jot editor.
		 *
		 * @param {type} data
		 * @returns {void}
		 */
		var insertUrl = function(data) {
			if (!isExtern) {
				return;
			}
			var bbcode = '[url=' + data.url + ']' + data.title + '[/url]';
			addeditortext(bbcode);
		};

		/**
		 * Process all attachment data and show up a html
		 * attachment preview.
		 *
		 * @param {obj} data Attachment data.
		 * @returns {void}
		 */
		var insertAttachment = function(data) {
			// If we have already a preview, leaver here.
			// Note: if we finish the Preview of other media content type,
			// we can move this condition to the beginning of crawlText();
			if (isActive) {
				$('#profile-rotator').hide();
				return;
			}

			if (data.type !== 'link' && data.type !== 'video' && data.type !== 'photo' || data.url === data.title) {
				$('#profile-rotator').hide();
				return;
			}

			$('#photoNumber_' + id).val(0);
			resetPreview();

			processAttachmentTpl(data, 'type-' + data.type);
			addTitleDescription(data);
			addHostToAttachment(data.url);
			addImagesToAttachment(data.images);

			processEventListener();
			$('#profile-rotator').hide();
		};

		/**
		 * Construct the attachment html from the attachment template and
		 * add it to the DOM.
		 *
		 * @param {object} data Attachment data.
		 * @returns {void}
		 */
		var processAttachmentTpl = function(data) {
			// Load and add the template if it isn't already loaded.
			if ($('#preview_' + id).length === 0) {
				var tpl = previewTpl.format(
					'type-' + data.type,
					attachmentTpl,
					1,
					bin2hex(data.url),
					data.type
				);
				$('#' + id).after(tpl);
			}

			isActive = true;
		};

		/**
		 * Add the attachment title and the description
		 * to the attachment preview.
		 *
		 * @param {object} data Attachment data.
		 * @returns {void}
		 */
		var addTitleDescription = function(data) {
			var description = data.text;

			if (description === '') {
				description = defaultDescription;
			}

			$('#previewTitle_' + id).html("\
				<span id='previewSpanTitle_" + id + "' class='previewSpanTitle' >" + escapeHTML(data.title) + "</span>\
				<input type='text' name='attachment_title' value='" + escapeHTML(data.title) + "' id='previewInputTitle_" + id + "' class='previewInputTitle inputPreview' style='display: none;'/>"
			);

			$('#previewDescription_' + id).html("\
				<span id='previewSpanDescription_" + id + "' class='previewSpanDescription' >" + escapeHTML(description) + "</span>\n\
				<textarea id='previewInputDescription_" + id + "' name='attachment_text' class='previewInputDescription' style='display: none;' class='inputPreview' >" + escapeHTML(data.text) + "</textarea>"
			);
		};

		/**
		 * Add the host to the attachment preview.
		 *
		 * @param {string} url The url of the link attachment.
		 * @returns {void}
		 */
		var addHostToAttachment = function(url) {
			if (url) {
				var regexpr = "(https?://)([^:^/]*)(:\\d*)?(.*)?";
				var regResult = url.match(regexpr);
				var urlHost = regResult[1] + regResult[2];
				$('#previewUrl_' + id).html("<a href='" + url + "'>" + urlHost + "</a>");
			}
		};

		/**
		 * Add preview images to the attachment.
		 *
		 * @param {array} images
		 *
		 * @returns {void}
		 */
		var addImagesToAttachment = function(images) {
			var imageClass = 'attachment-preview';

			if (Array.isArray(images) && images.length > 0) {
				$('#previewImages_' + id).show();
				$('#attachmentImageSrc_' + id).val(bin2hex(images[photoNumber].src));
				$('#attachmentImageWidth_' + id).val(images[photoNumber].width);
				$('#attachmentImageHeight_' + id).val(images[photoNumber].height);
			} else {
				$('#previewImages_' + id).hide();
			}

			images.length = parseInt(images.length);
			var appendImage = "";

			for (i = 0; i < images.length; i++) {
				// For small preview images we use a smaller attachment format.
				///@todo here we need to add a check for !DI::config()->get('system', 'always_show_preview').
				if (images[i].width >= 500 && images[i].width >= images[i].height) {
						imageClass = 'attachment-image';
				}

				if (i === 0) {
					appendImage += "<img id='imagePreview_" + id + "_" + i + "' src='" + images[i].src + "' class='" + imageClass + "' ></img>";
				} else {
					appendImage += "<img id='imagePreview_" + id + "_" + i + "' src='" + images[i].src + "' class='" + imageClass + "' style='display: none;'></img>";
				}
			}

			$('#previewImage_' + id).html(appendImage + "<div id='whiteImage' style='color: transparent; display:none;'>...</div>");

			// More than just one image.
			if (images.length > 1) {
				// Enable the button to change the preview pictures.
				$('#previewChangeImg_' + id).show();

				if (firstPosted === false) {
					firstPosted = true;

					$('#previewChangeImg_' + id).unbind('click').click(function (e) {
						e.stopPropagation();
						if (images.length > 1) {
							$('#imagePreview_' + id + '_' + photoNumber).css({
								'display': 'none'
							});
							photoNumber += 1;

							// If have reached the last image, begin with the first image.
							if (photoNumber === images.length) {
								photoNumber = 0;
							}

							$('#imagePreview_' + id + '_' + photoNumber).css({
								'display': 'block'
							});
							$('#photoNumber_' + id).val(photoNumber);
							$('#attachmentImageSrc_' + id).val(bin2hex(images[photoNumber].src));
							$('#attachmentImageWidth_' + id).val(images[photoNumber].width);
							$('#attachmentImageHeight_' + id).val(images[photoNumber].height);
						}
					});
				}
			}
		};

		/**
		 * Add event listener to control the attachment preview.
		 *
		 * @returns {void}
		 */
		var processEventListener = function() {
			$('#previewSpanTitle_' + id).unbind('click').click(function (e) {
				e.stopPropagation();
				if (blockTitle === false) {
					blockTitle = true;
					$('#previewSpanTitle_' + id).hide();
					$('#previewInputTitle_' + id).show();
					$('#previewInputTitle_' + id).val($('#previewInputTitle_' + id).val());
					$('#previewInputTitle_' + id).focus().select();
				}
			});

			$('#previewInputTitle_' + id).blur(function () {
				blockTitle = false;
				$('#previewSpanTitle_' + id).html($('#previewInputTitle_' + id).val());
				$('#previewSpanTitle_' + id).show();
				$('#previewInputTitle_' + id).hide();
			});

			$('#previewInputTitle_' + id).keypress(function (e) {
				if (e.which === 13) {
					blockTitle = false;
					$('#previewSpanTitle_' + id).html($('#previewInputTitle_' + id).val());
					$('#previewSpanTitle_' + id).show();
					$('#previewInputTitle_' + id).hide();
				}
			});

			$('#previewSpanDescription_' + id).unbind('click').click(function (e) {
				e.stopPropagation();
				if (blockDescription === false) {
					blockDescription = true;
					$('#previewSpanDescription_' + id).hide();
					$('#previewInputDescription_' + id).show();
					$('#previewInputDescription_' + id).val($('#previewInputDescription_' + id).val());
					$('#previewInputDescription_' + id).focus().select();
				}
			});

			$('#previewInputDescription_' + id).blur(function () {
				blockDescription = false;
				$('#previewSpanDescription_' + id).html($('#previewInputDescription_' + id).val());
				$('#previewSpanDescription_' + id).show();
				$('#previewInputDescription_' + id).hide();
			});

			$('#previewInputDescription_' + id).keypress(function (e) {
				if (e.which === 13) {
					blockDescription = false;
					$('#previewSpanDescription_' + id).html($('#previewInputDescription_' + id).val());
					$('#previewSpanDescription_' + id).show();
					$('#previewInputDescription_' + id).hide();
				}
			});

			$('#previewSpanTitle_' + id).mouseover(function () {
				$('#previewSpanTitle_' + id).css({
					"background-color": "#ff9"
				});
			});

			$('#previewSpanTitle_' + id).mouseout(function () {
				$('#previewSpanTitle_' + id).css({
					"background-color": "transparent"
				});
			});

			$('#previewSpanDescription_' + id).mouseover(function () {
				$('#previewSpanDescription_' + id).css({
					"background-color": "#ff9"
				});
			});

			$('#previewSpanDescription_' + id).mouseout(function () {
				$('#previewSpanDescription_' + id).css({
					"background-color": "transparent"
				});
			});

			$('#closePreview_' + id).unbind('click').click(function (e) {
				e.stopPropagation();
				block = false;
				images = '';
				isActive = false;
				firstPosted = false;
				$('#preview_' + id).fadeOut("fast", function () {
					$('#preview_' + id).remove();
					$('#profile-rotator').hide();
					$('#' + id).focus();
				});

			});
		};

		/**
		 * Convert attachment bbcode into an array.
		 *
		 * @param {string} content Text content with the attachment bbcode.
		 * @returns {object || null}
		 */
		var getAttachmentData = function(content) {
			var data = {};

			var match = content.match(/([\s\S]*)\[attachment([\s\S]*?)\]([\s\S]*?)\[\/attachment\]([\s\S]*)/im);
			if (match === null || match.length < 5) {
				return null;
			}

			var attributes = match[2];
			data.text = trim(match[1]);

			var type = '';
			var matches = attributes.match(/type='([\s\S]*?)'/im);
			if (matches !== null && typeof matches[1] !== 'undefined') {
				type = matches[1].toLowerCase();
			}

			matches = attributes.match(/type="([\s\S]*?)"/im);
			if (matches !== null && typeof matches[1] !== 'undefined') {
				type = matches[1].toLowerCase();
			}

			if (type === '') {
				return null;
			}

			if (
				type !== 'link'
				&& type !== 'audio'
				&& type !== 'photo'
				&& type !== 'video')
			{
				return null;
			}

			if (type !== '') {
				data.type = type;
			}

			var url = '';

			matches = attributes.match(/url='([\s\S]*?)'/im);
			if (matches !== null && typeof matches[1] !== 'undefined') {
				url = matches[1];
			}

			matches = attributes.match(/url="([\s\S]*?)"/im);
			if (matches !== null && typeof matches[1] !== 'undefined') {
				url = matches[1];
			}

			if(url !== '') {
				data.url = escapeHTML(url);
			}

			var title = '';

			matches = attributes.match(/title='([\s\S]*?)'/im);
			if (matches !== null && typeof matches[1] !== 'undefined') {
				title = trim(matches[1]);
			}

			matches = attributes.match(/title="([\s\S]*?)"/im);
			if (matches !== null && typeof matches[1] !== 'undefined') {
				title = trim(matches[1]);
			}

			if (title !== '') {
				data.title = escapeHTML(title);
			}

			var image = '';

			matches = attributes.match(/image='([\s\S]*?)'/im);
			if (matches !== null && typeof matches[1] !== 'undefined') {
				image = trim(matches[1]);
			}

			matches = attributes.match(/image="([\s\S]*?)"/im);
			if (matches !== null && typeof matches[1] !== 'undefined') {
				image = trim(matches[1]);
			}

			if (image !== '') {
				data.image = escapeHTML(image);
			}

			var preview = '';

			matches = attributes.match(/preview='([\s\S]*?)'/im);
			if (matches !== null && typeof matches[1] !== 'undefined') {
				preview = trim(matches[1]);
			}

			matches = attributes.match(/preview="([\s\S]*?)"/im);
			if (matches !== null && typeof matches[1] !== 'undefined') {
				preview = trim(matches[1]);
			}

			if (preview !== '') {
				data.preview = escapeHTML(preview);
			}

			data.text = trim(match[3]);
			data.after = trim(match[4]);

			return data;
		};

		/**
		 * Process txt content and if it contains attachment bbcode
		 * add it to the attachment preview .
		 *
		 * @param {string} content
		 * @returns {void}
		 */
		var addBBCodeToPreview =function(content) {
			var attachmentData = getAttachmentData(content);
			if (attachmentData) {
				reAddAttachment(attachmentData);
				// Remove the attachment bbcode from the textarea.
				var content = content.replace(/\[attachment[\s\S]*\[\/attachment]/im, '');
				$('#' + id).val(content);
				$('#' + id).focus();
			}
		};

		/**
		 * Add an Attachment with data from an old bbcode
		 * generated attachment.
		 *
		 * @param {object} json The attachment data.
		 * @returns {void}
		 */
		var reAddAttachment = function(json) {
			if (isActive) {
				$('#profile-rotator').hide();
				return;
			}

			if (json.type !== 'link' && json.type !== 'video' && json.type !== 'photo' || json.url === json.title) {
				$('#profile-rotator').hide();
				return;
			}

			var obj = {data: json};
			obj = sanitizeInputData(obj);

			var data = obj.data;

			resetPreview();

			processAttachmentTpl(data);
			addTitleDescription(data);
			addHostToAttachment(data.url);

			// Since we don't have an array of image data,
			// we need to add the preview images in a different way
			// than in function addImagesToAttachment().
			var imageClass = 'attachment-preview';
			var image = '';

			if (data.image !== '') {
				imageClass = 'attachment-image';
				image = data.image;
			} else {
				image = data.preview;
			}

			if (image !== '') {
				var appendImage = "<img id='imagePreview_" + id + "' src='" + image + "' class='" + imageClass + "' ></img>"
				$('#previewImage_' + id).html(appendImage);
				$('#attachmentImageSrc_' + id).val(bin2hex(image));

				// We need to add the image width and height when it is
				// loaded.
				$('<img/>' ,{
					load : function(){
						$('#attachmentImageWidth_' + id).val(this.width);
						$('#attachmentImageHeight_' + id).val(this.height);
					},
					src  : image
				});
			}

			processEventListener();
			$('#profile-rotator').hide();
		};

		/**
		 * Add missing default properties to the input data object.
		 *
		 * @param {object} obj Input data.
		 * @returns {object}
		 */
		var sanitizeInputData = function(obj) {
			if (typeof obj.contentType === 'undefined'
				|| obj.contentType === null)
			{
				obj.contentType = "";
			}
			if (typeof obj.data.url === 'undefined'
				|| obj.data.url === null)
			{
				obj.data.url = "";
			}
			if (typeof obj.data.title === 'undefined'
				|| obj.data.title === null
				|| obj.data.title === "")
			{
				obj.data.title = defaultTitle;
			}
			if (typeof obj.data.text === 'undefined'
				|| obj.data.text === null
				|| obj.data.text === "")
			{
				obj.data.text = "";
			}
			if (typeof obj.data.images === 'undefined'
				|| obj.data.images === null)
			{
				obj.data.images = "";
			}

			if (typeof obj.data.image === 'undefined'
				|| obj.data.image === null)
			{
				obj.data.image = "";
			}

			if (typeof obj.data.preview === 'undefined'
				|| obj.data.preview === null)
			{
				obj.data.preview = "";
			}

			return obj;
		};

		/**
		 * Destroy the plugin.
		 *
		 * @returns {void}
		 */
		var destroy = function() {
			$('#' + id).unbind();
			$('#preview_' + id).remove();
			binurl;
			block = false;
			blockTitle = false;
			blockDescription = false;
			cache = {};
			images = "";
			isExtern = false;
			photoNumber = 0;
			firstPosted = false;
			isActive = false;
			isCrawling = false;
			id = "";
		};

		var trim = function(str) {
			return str.replace(/^\s+|\s+$/g, "");
		};
		var escapeHTML = function(unsafe_str) {
			return unsafe_str
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/\"/g, '&quot;')
				.replace(/\[/g, '&#91;')
				.replace(/\]/g, '&#93;')
				.replace(/\'/g, '&#39;'); // '&apos;' is not valid HTML 4
		};

		// Initialize LinkPreview
		init();

		return {
			// make crawlText() accessable from the outside.
			crawlText: function(text) {
				crawlText(text);
			},
			addBBCodeToPreview: function(content) {
				addBBCodeToPreview(content);
			},
			destroy: function() {
				destroy();
			}
		};
	};

	$.fn.linkPreview.defaults = {
		defaultDescription: "Enter a description",
		defaultTitle: "Enter a title"
	};

	/**
	* Get in a textarea the previous word before the cursor.
	*
	* @param {object} text Textarea element.
	* @param {integer} caretPos Cursor position.
	*
	* @returns {string} Previous word.
	*/
	function returnWord(text, caretPos) {
		var index = text.indexOf(caretPos);
		var preText = text.substring(0, caretPos);
		// If the last character is a space or enter remove it
		// We need this in friendica for the url  preview.
		var lastChar = preText.slice(-1)
		if ( lastChar === " "
			|| lastChar === "\n"
			|| lastChar === "\r"
			)
		{
			preText = preText.substring(0, preText.length -1);
		}

		// Replace new line with space.
		preText = preText.replace(/\n/g, " ");

		if (preText.indexOf(" ") > 0) {
			var words = preText.split(" ");
			return words[words.length - 1]; //return last word
		}
		else {
			return preText;
		}
	}

	/**
	 * Get in a textarea the previous word before the cursor.
	 *
	 * @param {string} id The ID of a textarea element.
	 * @returns {sting|null} Previous word or null if no word is available.
	 */
	function getPrevWord(id) {
		var text = document.getElementById(id);
		var caretPos = getCaretPosition(text);
		var word = returnWord(text.value, caretPos);
		if (word != null) {
			return word
		}

	}

	/**
	 * Get the cursor position in an text element.
	 *
	 * @param {object} ctrl Textarea element.
	 * @returns {integer} Position of the cursor.
	 */
	function getCaretPosition(ctrl) {
		var CaretPos = 0;   // IE Support
		if (document.selection) {
			ctrl.focus();
			var Sel = document.selection.createRange();
			Sel.moveStart('character', -ctrl.value.length);
			CaretPos = Sel.text.length;
		}
		// Firefox support
		else if (ctrl.selectionStart || ctrl.selectionStart == '0') {
			CaretPos = ctrl.selectionStart;
		}
		return (CaretPos);
	}
})(jQuery);

// @license-end
