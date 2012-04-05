
<script language="javascript" type="text/javascript" src="$baseurl/library/tinymce/jscripts/tiny_mce/tiny_mce_src.js"></script>
<script language="javascript" type="text/javascript">

var editor=false;
var textlen = 0;
var plaintext = '$editselect';

function initEditor(cb) {
    if (editor==false) {
        $("#profile-jot-text-loading").show();
 if(plaintext == 'none') {
            $("#profile-jot-text-loading").hide();
            $("#profile-jot-text").css({ 'height': 200, 'color': '#000' });
            $(".jothidden").show();
            editor = true;
            $("a#jot-perms-icon").fancybox({
                'transitionIn' : 'elastic',
                'transitionOut' : 'elastic'
            });
	                            $("#profile-jot-submit-wrapper").show();
								{{ if $newpost }}
    	                            $("#profile-upload-wrapper").show();
        	                        $("#profile-attach-wrapper").show();
            	                    $("#profile-link-wrapper").show();
                	                $("#profile-video-wrapper").show();
                    	            $("#profile-audio-wrapper").show();
                        	        $("#profile-location-wrapper").show();
                            	    $("#profile-nolocation-wrapper").show();
                                	$("#profile-title-wrapper").show();
	                                $("#profile-jot-plugin-wrapper").show();
	                                $("#jot-preview-link").show();
								{{ endif }}   


            if (typeof cb!="undefined") cb();
            return;
        }
        tinyMCE.init({
                theme : "advanced",
                mode : "specific_textareas",
                editor_selector: /(profile-jot-text|prvmail-text)/,
                plugins : "bbcode,paste,fullscreen,autoresize",
                theme_advanced_buttons1 : "bold,italic,underline,undo,redo,link,unlink,image,forecolor,formatselect,code,fullscreen",
                theme_advanced_buttons2 : "",
                theme_advanced_buttons3 : "",
                theme_advanced_toolbar_location : "top",
                theme_advanced_toolbar_align : "center",
                theme_advanced_blockformats : "blockquote,code",
                //theme_advanced_resizing : true,
                //theme_advanced_statusbar_location : "bottom",
                paste_text_sticky : true,
                entity_encoding : "raw",
                add_unload_trigger : false,
                remove_linebreaks : false,
                force_p_newlines : false,
                force_br_newlines : true,
                forced_root_block : '',
                convert_urls: false,
                content_css: "$baseurl/view/custom_tinymce.css",
                theme_advanced_path : false,
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
	                            $("#profile-jot-submit-wrapper").show();
								{{ if $newpost }}
    	                            $("#profile-upload-wrapper").show();
        	                        $("#profile-attach-wrapper").show();
            	                    $("#profile-link-wrapper").show();
                	                $("#profile-video-wrapper").show();
                    	            $("#profile-audio-wrapper").show();
                        	        $("#profile-location-wrapper").show();
                            	    $("#profile-nolocation-wrapper").show();
                                	$("#profile-title-wrapper").show();
	                                $("#profile-jot-plugin-wrapper").show();
	                                $("#jot-preview-link").show();
								{{ endif }}   
                             $("#character-counter").show();
                                if (typeof cb!="undefined") cb();
                        });
                }
        });
        editor = true;
        // setup acl popup
        $("a#jot-perms-icon").fancybox({
            'transitionIn' : 'none',
            'transitionOut' : 'none'
        }); 
    } else {
        if (typeof cb!="undefined") cb();
    }
} // initEditor
</script>
<script type="text/javascript" src="js/ajaxupload.js" ></script>
<script>
    var ispublic = '$ispublic';
	$(document).ready(function() {
                /* enable tinymce on focus */
                $("#profile-jot-text").focus(function(){
                    if (editor) return;
                    $(this).val("");
                    initEditor();
                }); 


		var uploader = new window.AjaxUpload(
			'wall-image-upload',
			{ action: 'wall_upload/$nickname',
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
			{ action: 'wall_attach/$nickname',
				name: 'userfile',
				onSubmit: function(file,ext) { $('#profile-rotator').show(); },
				onComplete: function(file,response) {
					addeditortext(response);
					$('#profile-rotator').hide();
				}				 
			}
		);		
		$('#contact_allow, #contact_deny, #group_allow, #group_deny').change(function() {
			var selstr;
			$('#contact_allow option:selected, #contact_deny option:selected, #group_allow option:selected, #group_deny option:selected').each( function() {
				selstr = $(this).text();
				$('#jot-perms-icon').removeClass('unlock').addClass('lock');
				$('#jot-public').hide();
				$('.profile-jot-net input').attr('disabled', 'disabled');
			});
			if(selstr == null) { 
				$('#jot-perms-icon').removeClass('lock').addClass('unlock');
				$('#jot-public').show();
				$('.profile-jot-net input').attr('disabled', false);
			}

		}).trigger('change');

	});

	function deleteCheckedItems() {
		var checkedstr = '';

		$('.item-select').each( function() {
			if($(this).is(':checked')) {
				if(checkedstr.length != 0) {
					checkedstr = checkedstr + ',' + $(this).val();
				}
				else {
					checkedstr = $(this).val();
				}
			}	
		});
		$.post('item', { dropitems: checkedstr }, function(data) {
			window.location.reload();
		});
	}

	function jotGetLink() {
		reply = prompt("$linkurl");
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
		reply = prompt("$vidurl");
		if(reply && reply.length) {
			addeditortext('[video]' + reply + '[/video]');
		}
	}

	function jotAudioURL() {
		reply = prompt("$audurl");
		if(reply && reply.length) {
			addeditortext('[audio]' + reply + '[/audio]');
		}
	}


	function jotGetLocation() {
		reply = prompt("$whereareu", $('#jot-location').val());
		if(reply && reply.length) {
			$('#jot-location').val(reply);
		}
	}

	function jotTitle() {
		reply = prompt("$title", $('#jot-title').val());
		if(reply && reply.length) {
			$('#jot-title').val(reply);
		}
	}

	function jotShare(id) {
		$('#like-rotator-' + id).show();
		$.get('share/' + id, function(data) {
				if (!editor) $("#profile-jot-text").val("");
				initEditor(function(){
					addeditortext(data);
					$('#like-rotator-' + id).hide();
					$(window).scrollTop(0);
				});
		});
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
		reply = prompt("$term");
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
			$.fancybox(data);
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
					$.get('filer/' + id + '?term=' + reply);
					if(timer) clearTimeout(timer);
					timer = setTimeout(NavUpdate,3000);
					liking = 1;
					$.fancybox.close();
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
            var currentText = $("#profile-jot-text").val();
            $("#profile-jot-text").val(currentText + data);
        }
        else
            tinyMCE.execCommand('mceInsertRawHTML',false,data);
    }


	$geotag

</script>

