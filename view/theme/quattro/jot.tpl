<form id="profile-jot-form" action="$action" method="post">
	<div id="jot">
		<div id="profile-jot-desc" class="jothidden">&nbsp;</div>
		<input name="title" id="jot-title" type="text" placeholder="$placeholdertitle" title="$placeholdertitle" value="$title" class="jothidden" style="display:none" /><input name="category" id="jot-category" type="text" placeholder="$placeholdercategory" title="$placeholdercategory" value="$category" class="jothidden" style="display:none" />
		<div id="character-counter" class="grey jothidden"></div>
		


		<input type="hidden" name="type" value="$ptyp" />
		<input type="hidden" name="profile_uid" value="$profile_uid" />
		<input type="hidden" name="return" value="$return_path" />
		<input type="hidden" name="location" id="jot-location" value="$defloc" />
		<input type="hidden" name="coord" id="jot-coord" value="" />
		<input type="hidden" name="post_id" value="$post_id" />
		<input type="hidden" name="preview" id="jot-preview" value="0" />

		<textarea rows="5" cols="64" class="profile-jot-text" id="profile-jot-text" name="body" >{{ if $content }}$content{{ else }}$share{{ endif }}</textarea>

		<ul id="jot-tools" class="jothidden" style="display:none">
			<li><a href="#" onclick="return false;" id="wall-image-upload" title="$upload">$shortupload</a></a></li>
			<li><a href="#" onclick="return false;" id="wall-file-upload"  title="$attach">$shortattach</a></li>
			<li><a id="profile-link"  ondragenter="return linkdropper(event);" ondragover="return linkdropper(event);" ondrop="linkdrop(event);" onclick="jotGetLink(); return false;" title="$weblink">$shortweblink</a></li>
			<li><a id="profile-video" onclick="jotVideoURL();return false;" title="$gvideo">$shortvideo</a></li>
			<li><a id="profile-audio" onclick="jotAudioURL();return false;" title="$audio">$shortaudio</a></li>
			<!-- TODO: waiting for a better placement 
			<li><a id="profile-location" onclick="jotGetLocation();return false;" title="$setloc">$shortsetloc</a></li>
			<li><a id="profile-nolocation" onclick="jotClearLocation();return false;" title="$noloc">$shortnoloc</a></li>
			-->
			<li><a id="jot-preview-link" onclick="preview_post(); return false;" title="$preview">$preview</a></li>
			$jotplugins

			<li class="perms"><a id="jot-perms-icon" href="#profile-jot-acl-wrapper" class="icon s22 $lockstate $bang"  title="$permset" ></a></li>
			<li class="submit"><input type="submit" id="profile-jot-submit" name="submit" value="$share" /></li>
			<li id="profile-rotator" class="loading" style="display: none"><img src="images/rotator.gif" alt="$wait" title="$wait"  /></li>
		</ul>
	</div>
	
	<div id="jot-preview-content" style="display:none;"></div>

	<div style="display: none;">
		<div id="profile-jot-acl-wrapper" style="width:auto;height:auto;overflow:auto;">
			$acl
			<hr style="clear:both"/>
			<div id="profile-jot-email-label">$emailcc</div><input type="text" name="emailcc" id="profile-jot-email" title="$emtitle" />
			<div id="profile-jot-email-end"></div>
			$jotnets
		</div>
	</div>

</form>

{{ if $content }}<script>initEditor();</script>{{ endif }}
