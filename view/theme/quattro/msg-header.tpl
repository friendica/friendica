
<script language="javascript" type="text/javascript" src="$baseurl/library/tinymce/jscripts/tiny_mce/tiny_mce_src.js"></script>
<script language="javascript" type="text/javascript">

var plaintext = '$editselect';

if(plaintext != 'none') {
	tinyMCE.init({
		theme : "advanced",
		mode : "specific_textareas",
		editor_selector: /(profile-jot-text|prvmail-text)/,
		plugins : "bbcode,paste",
		theme_advanced_buttons1 : "bold,italic,underline,undo,redo,link,unlink,image,forecolor",
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
		     //Character count
		theme_advanced_path : false,
		setup : function(ed) {
			ed.onInit.add(function(ed) {
				ed.pasteAsPlainText = true;
				var editorId = ed.editorId;
				var textarea = $('#'+editorId);
				if (typeof(textarea.attr('tabindex')) != "undefined") {
					$('#'+editorId+'_ifr').attr('tabindex', textarea.attr('tabindex'));
					textarea.attr('tabindex', null);
				}
			});
		}
	});
}
else
	$("#prvmail-text").contact_autocomplete(baseurl+"/acl");


</script>
<script type="text/javascript" src="js/ajaxupload.js" ></script>
<script>
	$(document).ready(function() {
		var uploader = new window.AjaxUpload(
			'prvmail-upload',
			{ action: 'wall_upload/$nickname',
				name: 'userfile',
				onSubmit: function(file,ext) { $('#profile-rotator').show(); },
				onComplete: function(file,response) {
					tinyMCE.execCommand('mceInsertRawHTML',false,response);
					$('#profile-rotator').hide();
				}				 
			}
		);

	});

	function jotGetLink() {
		reply = prompt("$linkurl");
		if(reply && reply.length) {
			$('#profile-rotator').show();
			$.get('parse_url?url=' + reply, function(data) {
				tinyMCE.execCommand('mceInsertRawHTML',false,data);
				$('#profile-rotator').hide();
			});
		}
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
			$('#profile-rotator').show();
			$.get('parse_url?url=' + reply, function(data) {
				tinyMCE.execCommand('mceInsertRawHTML',false,data);
				$('#profile-rotator').hide();
			});
		}
	}

</script>

