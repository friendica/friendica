{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<script type="text/javascript" src="{{$baseurl}}/view/js/ajaxupload.js?v={{$VERSION}}"></script>
<script type="text/javascript" src="{{$baseurl}}/view/js/linkPreview.js?v={{$VERSION}}"></script>
<script type="text/javascript" src="{{$baseurl}}/view/theme/frio/js/jot.js?v={{$VERSION}}"></script>

<script type="text/javascript">
	var editor = false;
	var textlen = 0;
	var formModified = false;

	function initEditor(callback) {
		if (editor == false) {
			$("#profile-jot-text-loading").show();
			$("#profile-jot-text-loading").hide();
			//$("#profile-jot-text").addClass("profile-jot-text-full").removeClass("profile-jot-text-empty");
			$("#jot-category").show();
			$("#jot-category").addClass("jot-category-ex");
			$("#jot-profile-jot-wrapper").show();
			$("#profile-jot-text").editor_autocomplete(baseurl + '/search/acl');
			$("#profile-jot-text").bbco_autocomplete('bbcode');
			$("a#jot-perms-icon").colorbox({
				'inline' : true,
				'transition' : 'elastic'
			});
			$(".jothidden").show();
			$("#profile-jot-text").keyup(function(){
				textlen = $(this).val().length;
				$('#character-counter').text(textlen);
				formModified = true; // Mark the form as modified when the user types
			});

			editor = true;
		}
		if (typeof callback != "undefined") {
			callback();
		}
	}

	function enableOnUser(){
		initEditor();
	}

	// Warn user before leaving the page if the form is modified
	window.addEventListener('beforeunload', function (e) {
		if (formModified) {
			var confirmationMessage = 'There are unsaved changes. Are you sure you want to leave this page?';
			e.returnValue = confirmationMessage; // Gecko, Trident, Chrome 34+
			return confirmationMessage; // Gecko, WebKit, Chrome <34
		}
	});

	// Reset formModified flag after successful submission
	function resetFormModifiedFlag() {
		formModified = false;
	}

</script>

<script type="text/javascript">
	var ispublic = '{{$ispublic nofilter}}';
	aStr.linkurl = '{{$linkurl}}';


	$(document).ready(function() {

		/* enable editor on focus and click */
		$("#profile-jot-text").focus(enableOnUser);
		$("#profile-jot-text").click(enableOnUser);

		// When clicking on a group in acl we should remove the profile jot textarea
		// default value before inserting the group mention
		$("body").on('click', '#jot-modal .acl-list-item.group', function(){
			jotTextOpenUI(document.getElementById("profile-jot-text"));
		});

		/* show images / file browser window
		 *
		 **/

		/* callback */
		$('body').on('fbrowser.photo.main', function(e, filename, embedcode, id) {
			///@todo this part isn't ideal and need to be done in a better way
			jotTextOpenUI(document.getElementById("profile-jot-text"));
			jotActive();
			addeditortext(embedcode);
		})
		.on('fbrowser.attachment.main', function(e, filename, embedcode, id) {
			jotTextOpenUI(document.getElementById("profile-jot-text"));
			jotActive();
			addeditortext(embedcode);
		})
		// Asynchronous jot submission
		.on('submit', '#profile-jot-form', function (e) {
			e.preventDefault();

			// Disable jot submit buttons during processing
			let $share = $('#profile-jot-submit').button('loading');
			let $sharePreview = $('#profile-jot-preview-submit').button('loading');

			let formData = new FormData(e.target);
			// This cancels the automatic redirection after item submission
			formData.delete('return');

			$.ajax({
				url: 'item',
				data: formData,
				processData: false,
				contentType: false,
				type: 'POST',
			})
			.then(function () {
				// Reset the form for jot reuse in the same page
				e.target.reset();
				$('#jot-modal').modal('hide');
				resetFormModifiedFlag(); // Reset formModified after successful submission
			})
			.always(function() {
				// Reset the post_id_random to avoid duplicate post errors
				let new_post_id_random = Math.floor(Math.random() * (Number.MAX_SAFE_INTEGER - (Number.MAX_SAFE_INTEGER / 10))) + Number.MAX_SAFE_INTEGER / 10;
				$('#profile-jot-form [name=post_id_random]').val(new_post_id_random);

				// Reset jot submit button state
				$share.button('reset');
				$sharePreview.button('reset');

				// Force the display update of the edited post/comment
				if (formData.get('post_id')) {
					force_update = true;
					update_item = formData.get('post_id');
				}

				NavUpdate();
			});
		});

		$('#wall-image-upload').on('click', function(){
			Dialog.doImageBrowser("main");
			jotActive();
		});

		$('#wall-file-upload').on('click', function(){
			Dialog.doFileBrowser("main");
			jotActive();
		});

		$('body').on('click', '.p-category .filerm', function(e){
			e.preventDefault();

			let $href = $(e.target).attr('href');
			// Prevents arbitrary Ajax requests
			if ($href.substr(0, 7) === 'filerm/') {
				$(e.target).parent().removeClass('btn-success btn-danger');
				$.post($href)
				.done(function() {
					liking = 1;
					force_update = true;
				})
				.always(function () {
					NavUpdate();
				});
			}
		});
	});

	function deleteCheckedItems() {
		if (confirm('{{$delitems}}')) {
			let checkedstr = '';
			const ItemsToDelete = {};

			$('#item-delete-selected').prop('disabled', true);
			$('#item-delete-selected i').toggleClass('fa-trash fa-hourglass fa-spin');

			$('.item-select').each(function () {
				if ($(this).is(':checked')) {
					if (checkedstr.length > 0) {
						checkedstr = checkedstr + ',' + $(this).val();
					} else {
						checkedstr = $(this).val();
					}

					// Get the corresponding item container
					const deleteItem = this.closest(".wall-item-container");
					ItemsToDelete[deleteItem.id] = deleteItem;
				}
			});

			// Fade the container from the items we want to delete
			for (const key in ItemsToDelete) {
				$(ItemsToDelete[key]).fadeTo('fast', 0.33);
			}

			$.post('item', {dropitems: checkedstr}, function (data) {
			}).done(function () {
				// Loop through the ItemsToDelete Object and remove
				// corresponding item div
				for (const key in ItemsToDelete) {
					$(ItemsToDelete[key]).remove();
				}

				$('#item-delete-selected i').toggleClass('fa-trash fa-hourglass fa-spin')
				$('#item-delete-selected').prop('disabled', false).hide();
			});
		}
	}

	function jotVideoURL() {
		reply = prompt("{{$vidurl}}");
		if(reply && reply.length) {
			addeditortext('[video]' + reply + '[/video]');
			formModified = true; // Mark the form as modified
		}
	}

	function jotAudioURL() {
		reply = prompt("{{$audurl}}");
		if(reply && reply.length) {
			addeditortext('[audio]' + reply + '[/audio]');
			formModified = true; // Mark the form as modified
		}
	}

	function jotGetLocation() {
		reply = prompt("{{$whereareu}}", $('#jot-location').val());
		if(reply && reply.length) {
			$('#jot-location').val(reply);
			formModified = true; // Mark the form as modified
		}
	}

	function jotShare(id) {
		$.get('post/' + id + '/share', function(data) {
			// remove the former content of the text input
			$("#profile-jot-text").val("");
			initEditor(function(){
				addeditortext(data);
			});
			formModified = true; // Mark the form as modified
		});

		jotShow();

		$("#jot-popup").show();
	}

	function linkDropper(event) {
		var linkFound = event.dataTransfer.types.includes("text/uri-list");
		if(linkFound)
			event.preventDefault();
	}

	function linkDrop(event) {
		var reply = event.dataTransfer.getData("text/uri-list");
		var noAttachment = '';
		event.target.textContent = reply;
		event.preventDefault();
		if(reply && reply.length) {
			reply = bin2hex(reply);
			$('#profile-rotator').show();
			if (currentText.includes("[attachment") && currentText.includes("[/attachment]")) {
				noAttachment = '&noAttachment=1';
			}
			$.get('parseurl?binurl=' + reply + noAttachment, function(data) {
				if (!editor) $("#profile-jot-text").val("");
				initEditor(function(){
					addeditortext(data);
					$('#profile-rotator').hide();
					formModified = true; // Mark the form as modified
				});
			});
			autosize.update($("#profile-jot-text"));
		}
	}

	function itemTag(id) {
		reply = prompt("{{$term}}");
		if(reply && reply.length) {
			reply = reply.replace('#','');
			if(reply.length) {

				commentBusy = true;
				$('body').css('cursor', 'wait');

				$.post('post/' + id + '/tag/add', {term: reply});
				if(timer) clearTimeout(timer);
				timer = setTimeout(NavUpdate,3000);
				liking = 1;
				formModified = true; // Mark the form as modified
			}
		}
	}

	function itemFiler(id) {
		var modal = $('#modal').modal();

		$.get('filer/', function (data) {
			modal
				.find('#modal-body')
				.append(data);

			modal
				.find('#modal-header h4')
				.append("{{$fileas}}");

			// Ensure focus after the modal is fully visible
			modal.on('shown.bs.modal', function () {
				$('#id_term').trigger('focus');
			});

			$("#filer_save").click(function (e) {
				e.preventDefault();
				const term = $("#id_term").val();
				if (term && term.length) {
					commentBusy = true;
					formModified = true;
					$('body').css('cursor', 'wait');
					$.get('filer/' + id + '?term=' + term)
						.done(function () {
							$('#modal-body').empty();
							$('#modal').modal('hide');
							resetFormModifiedFlag();
						})
						.always(function () {
							liking = 1;
							force_update = true;
							update_item = id;
							NavUpdate();
						});
				} else {
					$("#id_term").css("border-color", "#FF0000");
				}

				return false;
			});
		});
	}

	function jotClearLocation() {
		$('#jot-coord').val('');
		$('#profile-nolocation-wrapper').hide();
		formModified = true; // Mark the form as modified
	}

	function addeditortext(data) {
		// get the textfield
		var textfield = document.getElementById("profile-jot-text");
		// check if the textfield does have the default-value
		jotTextOpenUI(textfield);
		// save already existent content
		var currentText = $("#profile-jot-text").val();
		//insert the data as new value
		textfield.value = currentText + data;
		autosize.update($("#profile-jot-text"));
		formModified = true; // Mark the form as modified
	}

	{{$geotag nofilter}}

	function jotShow() {
		var modal = $('#jot-modal').modal();
		jotcache = $("#jot-sections");

		// Auto focus on the first enabled field in the modal
		modal.on('shown.bs.modal', function (e) {
			$('#jot-modal-content').find('select:not([disabled]), input:not([type=hidden]):not([disabled]), textarea:not([disabled])').first().focus();
		})

		modal
			.find('#jot-modal-content')
			.append(jotcache)
			.modal.show;

		// Jot attachment live preview.
		linkPreview = $('#profile-jot-text').linkPreview();
	}

	// Activate the jot text section in the jot modal
	function jotActive() {
		// Make sure jot text does have really the active class (we do this because there are some
		// other events which trigger jot text (we need to do this for the desktop and mobile
		// jot nav
		var elem = $("#jot-modal .jot-nav #jot-text-lnk");
		var elemMobile = $("#jot-modal .jot-nav #jot-text-lnk-mobile")
		toggleJotNav(elem[0]);
		toggleJotNav(elemMobile[0]);
	}
</script>

