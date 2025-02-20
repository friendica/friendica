{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h2>{{$l10n.compose_title}}</h2>
	{{if $l10n.always_open_compose}}
	<p>{{$l10n.always_open_compose nofilter}}</p>
	{{/if}}
	<div id="profile-jot-wrapper">
		<form class="comment-edit-form" data-item-id="{{$id}}" id="comment-edit-form-{{$id}}" action="compose/{{$type}}" method="post">
			{{*<!--<input type="hidden" name="return" value="{{$return_path}}" />-->*}}
			<input type="hidden" name="post_id_random" value="{{$rand_num}}" />
			<input type="hidden" name="post_type" value="{{$posttype}}" />
			<input type="hidden" name="wall" value="{{$wall}}" />

			<div id="jot-title-wrap">
				<input type="text" name="title" id="jot-title" class="jothidden jotforms form-control" placeholder="{{$l10n.placeholdertitle}}" title="{{$l10n.placeholdertitle}}" value="{{$title}}" tabindex="1" dir="auto" />
			</div>
			{{if $l10n.placeholdercategory}}
				<div id="jot-category-wrap">
					<input name="category" id="jot-category" class="jothidden jotforms form-control" type="text" placeholder="{{$l10n.placeholdercategory}}" title="{{$l10n.placeholdercategory}}" value="{{$category}}" tabindex="2" dir="auto" />
				</div>
			{{/if}}

			<p class="comment-edit-bb-{{$id}} comment-icon-list">
				<span>
					<button type="button" class="btn btn-sm template-icon bb-img" aria-label="{{$l10n.edimg}}" title="{{$l10n.edimg}}" data-role="insert-formatting" data-bbcode="img" data-id="{{$id}}" tabindex="6">
						<i class="fa fa-picture-o"></i>
					</button>
					<button type="button" class="btn btn-sm template-icon bb-attach" aria-label="{{$l10n.edattach}}" title="{{$l10n.edattach}}" ondragenter="return commentLinkDrop(event, {{$id}});" ondragover="return commentLinkDrop(event, {{$id}});" ondrop="commentLinkDropper(event);" onclick="commentGetLink({{$id}}, '{{$l10n.prompttext}}');" tabindex="7">
						<i class="fa fa-paperclip"></i>
					</button>
				</span>
				<span>
					<button type="button" class="btn btn-sm template-icon bb-url" aria-label="{{$l10n.edurl}}" title="{{$l10n.edurl}}" onclick="insertFormatting('url',{{$id}});" tabindex="8">
						<i class="fa fa-link"></i>
					</button>
					<button type="button" class="btn btn-sm template-icon underline" aria-label="{{$l10n.eduline}}" title="{{$l10n.eduline}}" onclick="insertFormatting('u',{{$id}});" tabindex="9">
						<i class="fa fa-underline"></i>
					</button>
					<button type="button" class="btn btn-sm template-icon italic" aria-label="{{$l10n.editalic}}" title="{{$l10n.editalic}}" onclick="insertFormatting('i',{{$id}});" tabindex="10">
						<i class="fa fa-italic"></i>
					</button>
					<button type="button" class="btn btn-sm template-icon bold" aria-label="{{$l10n.edbold}}" title="{{$l10n.edbold}}" onclick="insertFormatting('b',{{$id}});" tabindex="11">
						<i class="fa fa-bold"></i>
					</button>
					<button type="button" class="btn btn-sm template-icon quote" aria-label="{{$l10n.edquote}}" title="{{$l10n.edquote}}" onclick="insertFormatting('quote',{{$id}});" tabindex="12">
						<i class="fa fa-quote-left"></i>
					</button>
					<button id="button_emojipicker" type="button" class="btn btn-sm template-icon emojis" aria-label="{{$l10n.edemojis}}" title="{{$l10n.edemojis}}" tabindex="13">
						<i class="fa fa-smile-o"></i>
					</button>
					<button type="button" class="btn btn-sm template-icon bb-url" aria-label="{{$l10n.contentwarn}}" title="{{$l10n.contentwarn}}" onclick="insertFormatting('abstract',{{$id}});" tabindex="14">
						<i class="fa fa-eye"></i>
					</button>
				</span>
			</p>
			<div id="dropzone-{{$id}}" class="dropzone">
				<p>
					<textarea id="comment-edit-text-{{$id}}" class="comment-edit-text form-control text-autosize expandable-textarea" name="body" placeholder="{{$l10n.default}}" rows="18" tabindex="3" dir="auto" onkeydown="sendOnCtrlEnter(event, 'comment-edit-submit-{{$id}}')">{{$body}}</textarea>
				</p>
			</div>
			<p class="comment-edit-submit-wrapper">
{{if $type == 'post'}}
				<span class="form-inline">
					<button type="button" name="permissions" class="btn btn-sm template-icon" id="toggle-permissions" title="{{$l10n.toggle_permissions_tooltip}}" onclick="togglePermissions()" style="margin-right: 10px;" tabindex="5">
						<i class="fa fa-ellipsis-h"></i> {{$l10n.toggle_permissions}}
					</button>
					<input type="text" name="location" class="form-control" id="jot-location" value="{{$location}}" placeholder="{{$l10n.location_set}}" tabindex="6"/>
                    			<button type="button" class="btn btn-sm template-icon" id="profile-location"
						data-title-set="{{$l10n.location_set}}"
						data-title-disabled="{{$l10n.location_disabled}}"
						data-title-unavailable="{{$l10n.location_unavailable}}"
						data-title-clear="{{$l10n.location_clear}}"
						title="{{$l10n.location_set}}"
						tabindex="7">
						<i class="fa fa-map-marker" aria-hidden="true"></i>
					</button>
				</span>
{{/if}}
				<span id="profile-rotator-wrapper">
					<img id="profile-rotator" src="images/rotator.gif" alt="{{$l10n.wait}}" title="{{$l10n.wait}}" style="display: none;" />
				</span>
				<span id="character-counter" class="grey text-info"></span>
				<button type="button" class="btn btn-default" onclick="preview_comment({{$id}});" id="comment-edit-preview-link-{{$id}}" tabindex="8"><i class="fa fa-eye"></i> {{$l10n.preview}}</button>
				<button type="submit" class="btn btn-primary" id="comment-edit-submit-{{$id}}" name="submit" tabindex="9"><i class="fa fa-envelope"></i> {{$l10n.submit}}</button>
			</p>

			<div id="comment-edit-preview-{{$id}}" class="comment-edit-preview" style="display:none;"></div>

			<div id="permissions-section" style="display: none;">
			<script>
				dzFactory.setupDropzone('#dropzone-{{$id}}', 'comment-edit-text-{{$id}}');
			</script>
{{if $type == 'post'}}
			<h3>{{$l10n.visibility_title}}</h3>
			{{$acl_selector nofilter}}

			<div class="jotplugins">
				{{$jotplugins nofilter}}
			</div>

			{{if $scheduled_at}}{{$scheduled_at nofilter}}{{/if}}
			{{if $created_at}}{{$created_at nofilter}}{{/if}}
{{else}}
			<input type="hidden" name="circle_allow" value="{{$circle_allow}}"/>
			<input type="hidden" name="contact_allow" value="{{$contact_allow}}"/>
			<input type="hidden" name="circle_deny" value="{{$circle_deny}}"/>
			<input type="hidden" name="contact_deny" value="{{$contact_deny}}"/>
{{/if}}
            </div>
        </form>
    </div>
</div>
<script>
	document.addEventListener("DOMContentLoaded", function() {
		var textareas = document.querySelectorAll(".expandable-textarea");

		textareas.forEach(function(textarea) {
			textarea.addEventListener("input", function() {
				this.style.height = "auto";
				this.style.height = (this.scrollHeight) + "px";
			});

			// Set initial height
			textarea.style.height = "auto";
			textarea.style.height = (textarea.scrollHeight) + "px";
		});
	});

	function togglePermissions() {
		var permissionsSection = document.getElementById('permissions-section');
		if (permissionsSection.style.display === 'none' || permissionsSection.style.display === '') {
			permissionsSection.style.display = 'block';
		} else {
			permissionsSection.style.display = 'none';
		}
	}

	// Warn the user before leaving the page
	var formSubmitting = false;

	function setFormSubmitting() {
		formSubmitting = true;
	}

	document.addEventListener("DOMContentLoaded", function() {
		var textareas = document.querySelectorAll(".expandable-textarea");

		textareas.forEach(function(textarea) {
			// Set initial height and restore saved content
			textarea.style.height = "auto";
			textarea.style.height = (textarea.scrollHeight) + "px";

			const savedContent = localStorage.getItem(`comment-edit-text-${textarea.id}`);
			const lastSaved = localStorage.getItem(`last-saved-${textarea.id}`);

			if (savedContent && lastSaved) {
				// Check whether 10 minutes have elapsed since the last save
				const currentTime = new Date().getTime();
				const timeElapsed = currentTime - parseInt(lastSaved, 10);

				if (timeElapsed <= 600000) {  // 600000 ms = 10 Minuten
					textarea.value = savedContent;
					textarea.style.height = "auto";
					textarea.style.height = (textarea.scrollHeight) + "px";
				} else {
					// Content is older than 10 minutes, therefore delete
					localStorage.removeItem(`comment-edit-text-${textarea.id}`);
					localStorage.removeItem(`last-saved-${textarea.id}`);
				}
			}
		});
	});

	// Auto-save content to localStorage every 5 seconds
	setInterval(() => {
		var textareas = document.querySelectorAll(".expandable-textarea");
		textareas.forEach(function(textarea) {
			if (textarea.value.trim() !== "") {
				// Saving the content
				localStorage.setItem(`comment-edit-text-${textarea.id}`, textarea.value);

				// Saving the timestamp of the last save
				const currentTime = new Date().getTime();
				localStorage.setItem(`last-saved-${textarea.id}`, currentTime.toString());
			}
		});
	}, 5000);

	// Remove content saved when submitting the form
	function setFormSubmitting() {
		formSubmitting = true;

		// Removing the content from localStorage
		var textareas = document.querySelectorAll(".expandable-textarea");
		textareas.forEach(function(textarea) {
			localStorage.removeItem(`comment-edit-text-${textarea.id}`);
			localStorage.removeItem(`last-saved-${textarea.id}`);
		});
	}

	window.addEventListener("beforeunload", function (event) {
		if (!formSubmitting) {
			// Get the value of the text field
			var textField = document.getElementById('comment-edit-text-{{$id}}').value.trim();

			// Check whether the text field contains at least one character
			if (textField.length > 0) {
				var confirmationMessage = 'Are you sure you want to reload the page? All unsaved changes will be lost.';
				event.returnValue = confirmationMessage;
				return confirmationMessage;
			}
		}
	});

	// Set the formSubmitting flag when the form is submitted
	document.getElementById('comment-edit-form-{{$id}}').addEventListener('submit', setFormSubmitting);
</script>
