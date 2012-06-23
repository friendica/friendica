<form id="profile-jot-form" action="$action" method="post">
	<div id="jot">
		<div id="profile-jot-desc" class="jothidden">&#160;</div>
		<input name="title" id="jot-title" type="text" placeholder="$placeholdertitle" value="$title" class="jothidden" style="display:none" />
		<div id="character-counter" class="grey jothidden"></div>

		<input type="hidden" name="type" value="$ptyp" />
		<input type="hidden" name="profile_uid" value="$profile_uid" />
		<input type="hidden" name="return" value="$return_path" />
		<input type="hidden" name="location" id="jot-location" value="$defloc" />
		<input type="hidden" name="coord" id="jot-coord" value="" />
		<input type="hidden" name="post_id" value="$post_id" />
		<input type="hidden" name="preview" id="jot-preview" value="0" />
		<div id="jot-category-wrap"><input name="category" id="jot-category" type="text" placeholder="$placeholdercategory" value="$category" class="jothidden" style="display:none" /></div>
		<textarea rows="5" cols="64" class="profile-jot-text" id="profile-jot-text" name="body">{{ if $content }}$content{{ else }}$share{{ endif }}
		</textarea>


<div id="jot-tools" class="jothidden" style="display:none">
	<div id="profile-jot-submit-wrapper" class="jothidden">

	<div id="profile-upload-wrapper" style="display: $visitor;">
		<div id="wall-image-upload-div"><a href="#" onclick="return false;" id="wall-image-upload" class="icon camera" title="$upload"></a></div>
	</div>
	<div id="profile-attach-wrapper" style="display: $visitor;">
		<div id="wall-file-upload-div"><a href="#" onclick="return false;" id="wall-file-upload" class="icon attach" title="$attach"></a></div>
	</div>

	<div id="profile-link-wrapper" style="display: $visitor;" ondragenter="linkdropper(event);" ondragover="linkdropper(event);" ondrop="linkdrop(event);">
		<a id="profile-link" class="icon link" ondragenter="return linkdropper(event);" ondragover="return linkdropper(event);" ondrop="linkdrop(event);" onclick="jotGetLink(); return false;" title="$weblink"></a>
	</div>
	<div id="profile-video-wrapper" style="display: $visitor;">
		<a id="profile-video" class="icon video" onclick="jotVideoURL();return false;" title="$video"></a>
	</div>
	<div id="profile-audio-wrapper" style="display: $visitor;">
		<a id="profile-audio" class="icon audio" onclick="jotAudioURL();return false;" title="$audio"></a>
	</div>
	<div id="profile-location-wrapper" style="display: $visitor;">
		<a id="profile-location" class="icon globe" onclick="jotGetLocation();return false;" title="$setloc"></a>
	</div>
	<div id="profile-nolocation-wrapper" style="display: none;">
		<a id="profile-nolocation" class="icon noglobe" onclick="jotClearLocation();return false;" title="$noloc"></a>
	</div>

	<div id="profile-jot-plugin-wrapper">
  	$jotplugins
	</div>

	<a class="icon-text-preview pointer"></a><a id="jot-preview-link" class="pointer" onclick="preview_post(); return false;" title="$preview">$preview</a>
	<input type="submit" id="profile-jot-submit" name="submit" value="$share" />
	<div id="profile-jot-perms" class="profile-jot-perms">
		<a id="jot-perms-icon" href="#profile-jot-acl-wrapper" class="icon $lockstate $bang" title="$permset"></a>
	</div>
	<span id="profile-rotator" class="loading" style="display: none"><img src="images/rotator.gif" alt="$wait" title="$wait" /></span>
	</div>

	</div> <!-- /#profile-jot-submit-wrapper -->
</div> <!-- /#jot-tools -->
	
	<div id="jot-preview-content" style="display:none;"></div>

	<div style="display: none;">
		<div id="profile-jot-acl-wrapper" style="width:auto;height:auto;overflow:auto;">
			$acl
			<hr style="clear:both" />
			<div id="profile-jot-email-label">$emailcc</div><input type="text" name="emailcc" id="profile-jot-email" title="$emtitle" />
			<div id="profile-jot-email-end"></div>
			$jotnets
		</div>
	</div>

</form>
		{{ if $content }}<script>initEditor();</script>{{ endif }}
