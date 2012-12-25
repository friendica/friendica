<!DOCTYPE html>
<html>
	<head>
	<script type="text/javascript" src="{{$baseurl}}/library/tinymce/jscripts/tiny_mce/tiny_mce_popup.js"></script>
	<style>
		.panel_wrapper div.current{.overflow: auto; height: auto!important; }
		.filebrowser.path { font-family: fixed; font-size: 10px; background-color: #f0f0ee; height:auto; overflow:auto;}
		.filebrowser.path a { border-left: 1px solid #C0C0AA; background-color: #E0E0DD; display: block; float:left; padding: 0.3em 1em;}
		.filebrowser ul{ list-style-type: none; padding:0px; }
		.filebrowser.folders a { display: block; padding: 0.3em }
		.filebrowser.folders a:hover { background-color: #f0f0ee; }
		.filebrowser.files.image { overflow: auto; height: auto; }
		.filebrowser.files.image img { height:50px;}
		.filebrowser.files.image li { display: block; padding: 5px; float: left; }
		.filebrowser.files.image span { display: none;}
		.filebrowser.files.file img { height:16px; vertical-align: bottom;}
		.filebrowser.files a { display: block;  padding: 0.3em}
		.filebrowser.files a:hover { background-color: #f0f0ee; }
		.filebrowser a { text-decoration: none; }
	</style>
	<script>
		var FileBrowserDialogue = {
			init : function () {
				// Here goes your code for setting your custom things onLoad.
			},
			mySubmit : function (URL) {
				//var URL = document.my_form.my_field.value;
				var win = tinyMCEPopup.getWindowArg("window");

				// insert information now
				win.document.getElementById(tinyMCEPopup.getWindowArg("input")).value = URL;

				// are we an image browser
				if (typeof(win.ImageDialog) != "undefined") {
					// we are, so update image dimensions...
					if (win.ImageDialog.getImageData)
						win.ImageDialog.getImageData();

					// ... and preview if necessary
					if (win.ImageDialog.showPreviewImage)
						win.ImageDialog.showPreviewImage(URL);
				}

				// close popup window
				tinyMCEPopup.close();
			}
		}

		tinyMCEPopup.onInit.add(FileBrowserDialogue.init, FileBrowserDialogue);
	</script>
	</head>
	<body>
	
	<div class="tabs">
		<ul >
			<li class="current"><span>FileBrowser</span></li>
		</ul>
	</div>
	<div class="panel_wrapper">

		<div id="general_panel" class="panel current">
			<div class="filebrowser path">
				{{foreach $path as $p}}<a href="{{$p.0}}">{{$p.1}}</a>{{/foreach}}
			</div>
			<div class="filebrowser folders">
				<ul>
					{{foreach $folders as $f}}<li><a href="{{$f.0}}/">{{$f.1}}</a></li>{{/foreach}}
				</ul>
			</div>
			<div class="filebrowser files {{$type}}">
				<ul>
				{{foreach $files as $f}}
					<li><a href="#" onclick="FileBrowserDialogue.mySubmit('{{$f.0}}'); return false;"><img src="{{$f.2}}"><span>{{$f.1}}</span></a></li>
				{{/foreach}}
				</ul>
			</div>
		</div>
	</div>
	<div class="mceActionPanel">
		<input type="button" id="cancel" name="cancel" value="{{$cancel}}" onclick="tinyMCEPopup.close();" />
	</div>	
	</body>
	
</html>
