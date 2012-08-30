var editor=false;
var textlen = 0;
var plaintext = '$editselect';

function initEditor(cb){
	if (editor==false){
		$("#profile-jot-text-loading").show();
		if(plaintext == 'none') {
			$("#profile-jot-text-loading").hide();
			$("#profile-jot-text").css({ 'height': 200, 'color': '#000' });
			$("#profile-jot-text").contact_autocomplete(baseurl+"/acl");
			editor = true;
			$("a#jot-perms-icon").fancybox({
				'transitionIn' : 'elastic',
				'transitionOut' : 'elastic'
			});
			$(".jothidden").show();
			if (typeof cb!="undefined") cb();
			return;
		}	
		tinyMCE.init({
			theme : "advanced",
			mode : "specific_textareas",
			editor_selector: $editselect,
			auto_focus: "profile-jot-text",
			plugins : "bbcode,paste,autoresize, inlinepopups",
			theme_advanced_buttons1 : "bold,italic,underline,undo,redo,link,unlink,image,forecolor,formatselect,code",
			theme_advanced_buttons2 : "",
			theme_advanced_buttons3 : "",
			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "center",
			theme_advanced_blockformats : "blockquote,code",
			gecko_spellcheck : true,
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
		$("a#jot-perms-icon").fancybox({
			'transitionIn' : 'elastic',
			'transitionOut' : 'elastic'
		}); 
	} else {
		if (typeof cb!="undefined") cb();
	}
}

function enableOnUser(){
	if (editor) return;
	$(this).val("");
	initEditor();
}
