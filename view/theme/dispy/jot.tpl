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

		<textarea rows="5" cols="64" class="profile-jot-text" id="profile-jot-text" name="body">{{ if $content }}$content{{ else }}$share{{ endif }}</textarea>

		<div id="jot-tools" class="jothidden" style="display:none">
			<span class="icon border camera"><a href="#" onclick="return false;" id="wall-image-upload" title="$upload"></a></span>
			<span class="icon border attach"><a href="#" onclick="return false;" id="wall-file-upload" title="$attach"></a></span>
			<span class="icon border link"><a id="profile-link" ondragenter="return linkdropper(event);" ondragover="return linkdropper(event);" ondrop="linkdrop(event);" onclick="jotGetLink(); return false;" title="$weblink"></a></span>
			<span class="icon border video"><a id="profile-video" onclick="jotVideoURL();return false;" title="$gvideo"></a></span>
			<span class="icon border audio"><a id="profile-audio" onclick="jotAudioURL();return false;" title="$audio"></a></span>
			<span class="icon border globe"><a id="profile-location" onclick="jotGetLocation();return false;" title="$setloc"></a></span>
			<span class="icon border noglobe"><a id="profile-nolocation" onclick="jotClearLocation();return false;" title="$noloc"></a></span>
			$jotplugins
			<ul id="profile-jot-submit-wrapper">
				<li>
					<a class="icon-text-preview pointer"></a><a id="jot-preview-link" class="pointer" onclick="preview_post(); return false;" title="$preview">$preview</a>
				</li>

				<li id="profile-jot-perms" class="profile-jot-perms">
					<a id="jot-perms-icon" href="#profile-jot-acl-wrapper" class="icon $lockstate $bang" title="$permset"></a>
				</li>

				<li><input type="submit" id="profile-jot-submit" name="submit" value="$share" /></li>

			</ul>
			<span id="profile-rotator" class="loading" style="display: none">
				<img src="images/rotator.gif" alt="$wait" title="$wait" /></span>
		</div>
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
