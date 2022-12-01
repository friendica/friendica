
<script type="text/javascript" src="{{$baseurl}}/view/js/ajaxupload.js?v={{$smarty.const.FRIENDICA_VERSION}}"></script>
<script type="text/javascript" src="{{$baseurl}}/view/js/linkPreview.js?v={{$smarty.const.FRIENDICA_VERSION}}"></script>
<script type="text/javascript" src="{{$baseurl}}/view/theme/frio/js/jot.js?v={{$smarty.const.FRIENDICA_VERSION}}"></script>

<script type="text/javascript">
	var editor = false;
	var textlen = 0;

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
				var textlen = $(this).val().length;
				$('#character-counter').text(textlen);
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
</script>

<script type="text/javascript">
	var ispublic = '{{$ispublic nofilter}}';
	aStr.linkurl = '{{$linkurl}}';


	$(document).ready(function() {

		/* enable editor on focus and click */
		$("#profile-jot-text").focus(enableOnUser);
		$("#profile-jot-text").click(enableOnUser);

		// When clicking on a forum in acl we should remove the profile jot textarea
		// default value before inserting the forum mention
		$("body").on('click', '#jot-modal .acl-list-item.forum', function(){
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
				// Reset to form for jot reuse in the same page
				e.target.reset();
				$('#jot-modal').modal('hide');
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
			})
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
		if(confirm('{{$delitems}}')) {
			var checkedstr = '';
			var ItemsToDelete = {};

			$("#item-delete-selected").hide();
			$('#item-delete-selected-rotator').show();
			$('body').css('cursor', 'wait');

			$('.item-select').each( function() {
				if($(this).is(':checked')) {
					if(checkedstr.length != 0) {
						checkedstr = checkedstr + ',' + $(this).val();
						var deleteItem = this.closest(".wall-item-container");
						ItemsToDelete[deleteItem.id] = deleteItem;
					}
					else {
						checkedstr = $(this).val();
					}

					// Get the corresponding item container
					var deleteItem = this.closest(".wall-item-container");
					ItemsToDelete[deleteItem.id] = deleteItem;
				}
			});

			// Fade the the the container from the items we want to delete
			for(var key in  ItemsToDelete) {
				$(ItemsToDelete[key]).fadeTo('fast', 0.33);
			};

			$.post('item', { dropitems: checkedstr }, function(data) {
			}).done(function() {
				// Loop through the ItemsToDelete Object and remove
				// corresponding item div
				for(var key in  ItemsToDelete) {
					$(ItemsToDelete[key]).remove();
				}
				$('body').css('cursor', 'auto');
				$('#item-delete-selected-rotator').hide();
			});
		}
	}

	function jotVideoURL() {
		reply = prompt("{{$vidurl}}");
		if(reply && reply.length) {
			addeditortext('[video]' + reply + '[/video]');
		}
	}

	function jotAudioURL() {
		reply = prompt("{{$audurl}}");
		if(reply && reply.length) {
			addeditortext('[audio]' + reply + '[/audio]');
		}
	}

	function jotGetLocation() {
		reply = prompt("{{$whereareu}}", $('#jot-location').val());
		if(reply && reply.length) {
			$('#jot-location').val(reply);
		}
	}

	function jotShare(id) {
		$.get('post/' + id + '/share', function(data) {
			// remove the former content of the text input
			$("#profile-jot-text").val("");
			initEditor(function(){
				addeditortext(data);
			});
		});

		jotShow();

		$("#jot-popup").show();
	}

	function linkDropper(event) {
		var linkFound = event.dataTransfer.types.contains("text/uri-list");
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
			}
		}
	}

	function itemFiler(id) {
		var bordercolor = $("input").css("border-color");

		$.get('filer/', function(data){
			$.colorbox({html:data});
			$("#id_term").keypress(function(){
				$(this).css("border-color",bordercolor);
			})
			$("#select_term").change(function(){
				$("#id_term").css("border-color",bordercolor);
			})

			$("#filer_save").click(function(e){
				e.preventDefault();
				reply = $("#id_term").val();
				if(reply && reply.length) {
					commentBusy = true;
					$('body').css('cursor', 'wait');
					$.get('filer/' + id + '?term=' + reply, NavUpdate);
//					if(timer) clearTimeout(timer);
//					timer = setTimeout(NavUpdate,3000);
					liking = 1;
					force_update = true;
					$.colorbox.close();
				} else {
					$("#id_term").css("border-color","#FF0000");
				}
				return false;
			});
		});
	}

	function jotClearLocation() {
		$('#jot-coord').val('');
		$('#profile-nolocation-wrapper').hide();
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
