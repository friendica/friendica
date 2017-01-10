
<script type="text/javascript" src="{{$baseurl}}/js/ajaxupload.js" ></script>

<script type="text/javascript">
	var editor=false;
	var textlen = 0;
	var plaintext = '{{$editselect}}';

	function initEditor(cb){
		if (editor==false){
			$("#profile-jot-text-loading").show();
			if(plaintext == 'none') {
				$("#profile-jot-text-loading").hide();
				//$("#profile-jot-text").addClass("profile-jot-text-full").removeClass("profile-jot-text-empty");
				$("#jot-category").show();
				$("#jot-category").addClass("jot-category-ex");
				$("#jot-profile-jot-wrapper").show();
				$("#profile-jot-text").editor_autocomplete(baseurl+"/acl");
				$("#profile-jot-text").bbco_autocomplete('bbcode');
				editor = true;
				$("a#jot-perms-icon").colorbox({
					'inline' : true,
					'transition' : 'elastic'
				});
				$(".jothidden").show();
				if (typeof cb!="undefined") cb();
				$("#profile-jot-text").keyup(function(){
					var textlen = $(this).val().length;
					$('#character-counter').text(textlen);
				});
				return;
			}	
			tinyMCE.init({
				theme : "advanced",
				mode : "specific_textareas",
				editor_selector: {{$editselect}},
				auto_focus: "profile-jot-text",
				plugins : "bbcode,paste,autoresize, inlinepopups",
				theme_advanced_buttons1 : "bold,italic,underline,undo,redo,link,unlink,image,forecolor,formatselect,code",
				theme_advanced_buttons2 : "",
				theme_advanced_buttons3 : "",
				theme_advanced_toolbar_location : "top",
				theme_advanced_toolbar_align : "center",
				theme_advanced_blockformats : "blockquote,code",
				theme_advanced_resizing : true,
				gecko_spellcheck : true,
				paste_text_sticky : true,
				entity_encoding : "raw",
				add_unload_trigger : false,
				remove_linebreaks : false,
				//force_p_newlines : false,
				//force_br_newlines : true,
				forced_root_block : 'div',
				convert_urls: false,
				content_css: "{{$baseurl}}/view/custom_tinymce.css",
				theme_advanced_path : false,
				file_browser_callback : "fcFileBrowser",
				setup : function(ed) {
					cPopup = null;
					ed.onKeyDown.add(function(ed,e) {
						if(cPopup !== null)
							cPopup.onkey(e);
					});

					ed.onKeyUp.add(function(ed, e) {
						var txt = tinyMCE.activeEditor.getContent();
						match = txt.match(/@([^ \n]+)$/);
						if(match!==null) {
							if(cPopup === null) {
								cPopup = new ACPopup(this,baseurl+"/acl");
							}
							if(cPopup.ready && match[1]!==cPopup.searchText) cPopup.search(match[1]);
							if(! cPopup.ready) cPopup = null;
						}
						else {
							if(cPopup !== null) { cPopup.close(); cPopup = null; }
						}

						textlen = txt.length;
						if(textlen != 0 && $('#jot-perms-icon').is('.unlock')) {
							$('#profile-jot-desc').html(ispublic);
						}
						else {
							$('#profile-jot-desc').html('&nbsp;');
						}	 

					 //Character count

						if(textlen <= 140) {
							$('#character-counter').removeClass('red');
							$('#character-counter').removeClass('orange');
							$('#character-counter').addClass('grey');
						}
						if((textlen > 140) && (textlen <= 420)) {
							$('#character-counter').removeClass('grey');
							$('#character-counter').removeClass('red');
							$('#character-counter').addClass('orange');
						}
						if(textlen > 420) {
							$('#character-counter').removeClass('grey');
							$('#character-counter').removeClass('orange');
							$('#character-counter').addClass('red');
						}
						$('#character-counter').text(textlen);
					});

					ed.onInit.add(function(ed) {
						ed.pasteAsPlainText = true;
						$("#profile-jot-text-loading").hide();
						$(".jothidden").show();
						if (typeof cb!="undefined") cb();
					});

				}

			});
			editor = true;

			// setup acl popup
			$("a#jot-perms-icon").colorbox({
				'inline' : true,
				'transition' : 'elastic'
			}); 

		} else {
			if (typeof cb!="undefined") cb();
		}
	}

	function enableOnUser(){
		if (editor) return;
		//$(this).val("");
		initEditor();
	}
</script>

<script type="text/javascript">
	var ispublic = '{{$ispublic}}';


	$(document).ready(function() {

		/* enable tinymce on focus and click */
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
		$('body').on('fbrowser.image.main', function(e, filename, embedcode, id) {
			///@todo this part isn't ideal and need to be done in a better way
			jotTextOpenUI(document.getElementById("profile-jot-text"));
			jotActive();
			addeditortext(embedcode);
		});
		$('body').on('fbrowser.file.main', function(e, filename, embedcode, id) {
			jotTextOpenUI(document.getElementById("profile-jot-text"));
			jotActive();
			addeditortext(embedcode);
		});

		$('#wall-image-upload').on('click', function(){
			Dialog.doImageBrowser("main");
			jotActive();
		});

		$('#wall-file-upload').on('click', function(){
			Dialog.doFileBrowser("main");
			jotActive();
		});

		/**
			var uploader = new window.AjaxUpload(
				'wall-image-upload',
				{ action: 'wall_upload/{{$nickname}}',
					name: 'userfile',
					onSubmit: function(file,ext) { $('#profile-rotator').show(); },
					onComplete: function(file,response) {
						addeditortext(response);
						$('#profile-rotator').hide();
					}
				}
			);
			var file_uploader = new window.AjaxUpload(
				'wall-file-upload',
				{ action: 'wall_attach/{{$nickname}}',
					name: 'userfile',
					onSubmit: function(file,ext) { $('#profile-rotator').show(); },
					onComplete: function(file,response) {
						addeditortext(response);
						$('#profile-rotator').hide();
					}
				}
			);

		}
		**/
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

	function jotGetLink() {
		reply = prompt("{{$linkurl}}");
		if(reply && reply.length) {
			reply = bin2hex(reply);
			$('#profile-rotator').show();
			$.get('parse_url?binurl=' + reply, function(data) {
				addeditortext(data);
				$('#profile-rotator').hide();
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
		$.get('share/' + id, function(data) {
			// remove the former content of the text input
			$("#profile-jot-text").val("");
			initEditor(function(){
				addeditortext(data);
			});
		});

		jotShow();

		$("#jot-popup").show();
	}

	function linkdropper(event) {
		var linkFound = event.dataTransfer.types.contains("text/uri-list");
		if(linkFound)
			event.preventDefault();
	}

	function linkdrop(event) {
		var reply = event.dataTransfer.getData("text/uri-list");
		event.target.textContent = reply;
		event.preventDefault();
		if(reply && reply.length) {
			reply = bin2hex(reply);
			$('#profile-rotator').show();
			$.get('parse_url?binurl=' + reply, function(data) {
				if (!editor) $("#profile-jot-text").val("");
				initEditor(function(){
					addeditortext(data);
					$('#profile-rotator').hide();
				});
			});
		}
	}

	function itemTag(id) {
		reply = prompt("{{$term}}");
		if(reply && reply.length) {
			reply = reply.replace('#','');
			if(reply.length) {

				commentBusy = true;
				$('body').css('cursor', 'wait');

				$.get('tagger/' + id + '?term=' + reply);
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
		if(plaintext == 'none') {
			// get the textfield
			var textfield = document.getElementById("profile-jot-text");
			// check if the textfield does have the default-value
			jotTextOpenUI(textfield);
			// save already existent content
			var currentText = $("#profile-jot-text").val();
			//insert the data as new value
			textfield.value = currentText + data;
		}
		else
			tinyMCE.execCommand('mceInsertRawHTML',false,data);
	}

	{{$geotag}}

	function jotShow() {
		var modal = $('#jot-modal').modal();
		jotcache = $("#jot-sections");

		modal
			.find('#jot-modal-content')
			.append(jotcache)
			.modal.show;
	}

	// the following functions show/hide the specific jot content 
	// in dependence of the selected nav
	function aclActive() {
		$(".modal-body #profile-jot-wrapper, .modal-body #jot-preview-content, .modal-body #jot-fbrowser-wrapper").hide();
		$(".modal-body #profile-jot-acl-wrapper").show();
	}


	function previewActive() {
		$(".modal-body #profile-jot-wrapper, .modal-body #profile-jot-acl-wrapper,.modal-body #jot-fbrowser-wrapper").hide();
		preview_post();
	}

	function jotActive() {
		$(".modal-body #profile-jot-acl-wrapper, .modal-body #jot-preview-content, .modal-body #jot-fbrowser-wrapper").hide();
		$(".modal-body #profile-jot-wrapper").show();

		//make sure jot text does have really the active class (we do this because there are some
		// other events which trigger jot text
		toggleJotNav($("#jot-modal .jot-nav #jot-text-lnk"));
	}

	function fbrowserActive() {
		$(".modal-body #profile-jot-wrapper, .modal-body #jot-preview-content, .modal-body #profile-jot-acl-wrapper").hide();

		$(".modal-body #jot-fbrowser-wrapper").show();

		$(function() {Dialog.showJot();});
	}


</script>

