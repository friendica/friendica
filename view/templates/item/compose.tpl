{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<style>
	.expandable-textarea {
		resize: none;
		overflow-y: auto;
	}

	@media (max-width: 991px) {
		.expandable-textarea {
			max-height: 300px;
		}
	}

	#jot-summary {
		max-height: 120px;
	}

	#permissions-modal-{{$id}} .modal-body {
		max-height: 65vh;
		overflow-y: auto;
	}

	@media (min-width: 992px) {
		.expandable-textarea {
			line-height: 1.6;
			max-height: 50vh;
		}

		/* Wide editor mode - layout rules borrowed from the Advanced Composer (desktop only) */
		body.wide-editor-active #region-sidebar-first,
		body.wide-editor-active #region-sidebar-second,
		body.wide-editor-active .region-sidebar-first,
		body.wide-editor-active .region-sidebar-second,
		body.wide-editor-active #aside,
		body.wide-editor-active .sidebar-col {
			display: none !important;
		}

		body.wide-editor-active #content {
			width: 100% !important;
			max-width: 1350px !important;
			float: none !important;
			margin: 0 auto !important;
		}

		body.wide-editor-active.wide-preview-active form.comment-edit-form {
			display: grid;
			grid-template-columns: minmax(0, 1fr) minmax(280px, 360px);
			grid-template-areas: "title title" "summary summary" "category category" "toolbar toolbar" "body preview" "submit submit";
		}

		body.wide-editor-active.wide-preview-active #jot-title-wrap { grid-area: title; }
		body.wide-editor-active.wide-preview-active #jot-summary-wrap { grid-area: summary; }
		body.wide-editor-active.wide-preview-active #jot-category-wrap { grid-area: category; }
		body.wide-editor-active.wide-preview-active .comment-edit-bb-{{$id}} { grid-area: toolbar; }
		body.wide-editor-active.wide-preview-active #dropzone-{{$id}} { grid-area: body; }
		body.wide-editor-active.wide-preview-active .comment-edit-submit-wrapper { grid-area: submit; }
		body.wide-editor-active.wide-preview-active #comment-edit-preview-{{$id}} {
			grid-area: preview;
			align-self: start;
			border: 1px solid #ccc;
			border-radius: 4px;
			padding: 15px;
			overflow-y: auto;
		}

		body.wide-editor-active #comment-edit-text-{{$id}} {
			max-height: calc(100vh - 180px);
		}
	}
</style>
<div class="generic-page-wrapper">
	<h2>{{$l10n.compose_title}}</h2>
	{{if $l10n.always_open_compose}}
	<p>{{$l10n.always_open_compose nofilter}}</p>
	{{/if}}
	<div id="profile-jot-wrapper">
		<form class="comment-edit-form" data-item-id="{{$id}}" id="comment-edit-form-{{$id}}" action="compose/{{$type}}" method="post">
			<input type="hidden" name="post_id_random" value="{{$rand_num}}" />
			<input type="hidden" name="post_type" value="{{$posttype}}" />
			<input type="hidden" name="wall" value="{{$wall}}" />

			<div id="jot-title-wrap">
				<input type="text" name="title" id="jot-title" class="jothidden jotforms form-control" placeholder="{{$l10n.placeholdertitle}}" title="{{$l10n.placeholdertitle}}" value="{{$title}}" tabindex="1" dir="auto" />
			</div>
			{{if $l10n.placeholdersummary}}
			<div id="jot-summary-wrap">
				<textarea name="summary" id="jot-summary" class="jothidden jotforms form-control expandable-textarea" placeholder="{{$l10n.placeholdersummary}}" title="{{$l10n.placeholdersummary}}" rows="1" tabindex="2" dir="auto" style="min-height: 34px; height: 34px;">{{$summary}}</textarea>
			</div>
			{{/if}}
			{{if $l10n.placeholdercategory}}
			<div id="jot-category-wrap">
				<input name="category" id="jot-category" class="jothidden jotforms form-control" type="text" placeholder="{{$l10n.placeholdercategory}}" title="{{$l10n.placeholdercategory}}" value="{{$category}}" tabindex="3" dir="auto" />
			</div>
			{{/if}}

			<div class="comment-edit-bb-{{$id}} btn-toolbar clearfix" role="toolbar">
				<div class="btn-group">
					<button type="button" class="btn btn-default bb-img" aria-label="{{$l10n.edimg}}" title="{{$l10n.edimg}}" data-role="insert-formatting" data-bbcode="img" data-id="{{$id}}" tabindex="4">
						<i class="ri ri-image-line"></i>
					</button>
					<button type="button" class="btn btn-default bb-attach" aria-label="{{$l10n.edattach}}" title="{{$l10n.edattach}}" ondragenter="return commentLinkDrop(event, {{$id}});" ondragover="return commentLinkDrop(event, {{$id}});" ondrop="commentLinkDropper(event);" onclick="commentGetLink({{$id}}, '{{$l10n.prompttext}}');" tabindex="5">
						<i class="ri ri-attachment-2"></i>
					</button>
					<button type="button" id="button_emojipicker" class="btn btn-default emojis" aria-label="{{$l10n.edemojis}}" title="{{$l10n.edemojis}}" tabindex="6">
						<i class="ri ri-emotion-line"></i>
					</button>
					{{if !$enableAdvancedComposer}}
					<button type="button" class="btn btn-default" id="wide-editor-toggle" aria-label="{{$l10n.btnZen}}" title="{{$l10n.btnZen}}">
						<i class="ri ri-fullscreen-line"></i>
					</button>
					{{/if}}
				</div>

				<div class="pull-right">
					<div class="btn-group">
						<button type="button" class="btn btn-default bb-url" aria-label="{{$l10n.edurl}}" title="{{$l10n.edurl}}" onclick="insertFormatting('url',{{$id}});" tabindex="7">
							<i class="ri ri-link"></i>
						</button>
						<button type="button" class="btn btn-default underline" aria-label="{{$l10n.eduline}}" title="{{$l10n.eduline}}" onclick="insertFormatting('u',{{$id}});" tabindex="9">
							<i class="ri ri-underline"></i>
						</button>
						<button type="button" class="btn btn-default italic" aria-label="{{$l10n.editalic}}" title="{{$l10n.editalic}}" onclick="insertFormatting('i',{{$id}});" tabindex="10">
							<i class="ri ri-italic"></i>
						</button>
						<button type="button" class="btn btn-default bold" aria-label="{{$l10n.edbold}}" title="{{$l10n.edbold}}" onclick="insertFormatting('b',{{$id}});" tabindex="11">
							<i class="ri ri-bold"></i>
						</button>
					</div>
					<div class="btn-group">
						<button type="button" class="btn btn-default quote" aria-label="{{$l10n.edquote}}" title="{{$l10n.edquote}}" onclick="insertFormatting('quote',{{$id}});" tabindex="12">
							<i class="ri ri-double-quotes-l"></i>
						</button>
						<button type="button" class="btn btn-default bb-url" aria-label="{{$l10n.contentwarn}}" title="{{$l10n.contentwarn}}" onclick="insertFormatting('abstract',{{$id}});" tabindex="13">
							<i class="ri ri-eye-line"></i>
						</button>
						<button type="button" class="btn btn-default code" aria-label="{{$l10n.edcode}}" title="{{$l10n.edcode}}" onclick="insertFormatting('code',{{$id}});" tabindex="14">
							<i class="ri ri-code-line"></i>
						</button>
					</div>
				</div>
			</div>

			<div id="dropzone-{{$id}}" class="dropzone">
				<p>
					<textarea id="comment-edit-text-{{$id}}" class="comment-edit-text form-control text-autosize expandable-textarea" name="body" placeholder="{{$l10n.default}}" rows="18" tabindex="3" dir="auto" onkeydown="sendOnCtrlEnter(event, 'comment-edit-submit-{{$id}}')">{{$body}}</textarea>
				</p>
			</div>
			<div class="comment-edit-submit-wrapper clearfix">
				{{if $type == 'post'}}
				<div id="compose-additional-settings-location">
					<button type="button" name="permissions" class="btn btn-default" id="toggle-permissions" title="{{$l10n.toggle_permissions_tooltip}}" tabindex="5">
						<i class="ri ri-more-line"></i> {{$l10n.toggle_permissions}}
					</button>
					<input type="text" name="location" class="form-control" id="jot-location" value="{{$location}}" placeholder="{{$l10n.location_set}}" tabindex="6" />
					<button type="button" class="btn btn-default" id="profile-location"
						data-title-set="{{$l10n.location_set}}"
						data-title-disabled="{{$l10n.location_disabled}}"
						data-title-unavailable="{{$l10n.location_unavailable}}"
						data-title-clear="{{$l10n.location_clear}}"
						title="{{$l10n.location_set}}"
						tabindex="7">
						<i class="ri ri-map-pin-line" aria-hidden="true"></i>
					</button>
				</div>
				{{/if}}
				<div>
					<span role="presentation" id="profile-rotator-wrapper">
						<img role="presentation" id="profile-rotator" src="images/rotator.gif" alt="{{$l10n.wait}}" title="{{$l10n.wait}}" style="display: none;" />
					</span>
					<span role="presentation" id="character-counter" class="grey text-info"></span>
					<button type="button" class="btn btn-default" onclick="preview_comment_toggle({{$id}}, '{{$l10n.preview}}');" id="comment-edit-preview-link-{{$id}}" tabindex="8">
						<i class="ri ri-eye-line"></i> <span id="preview-btn-text-{{$id}}">{{$l10n.preview}}</span>
					</button>
					{{if $enableAdvancedComposer}}
					<button type="button" class="btn btn-default" id="easy-compose-toggle" title="{{$l10n.btnAssistant}}" onclick="AdvancedComposerTogglePanel()">
						<i class="ri ri-quill-pen-line"></i> <span class="ec-btn-text">{{$l10n.btnAssistant}}</span>
					</button>
					<button type="button" class="btn btn-default" id="easy-compose-distraction-toggle" title="{{$l10n.btnZen}}" onclick="AdvancedComposerToggleDistractionFree()">
						<i class="ri ri-fullscreen-line"></i> <span class="ec-btn-text">{{$l10n.btnZen}}</span>
					</button>
					<button type="button" class="btn btn-default" id="easy-compose-focus-preview-toggle" title="{{$l10n.btnFocusPreview}}" onclick="AdvancedComposerOpenFocusPreview()">
						<i class="ri ri-eye-line"></i> <span class="ec-btn-text">{{$l10n.btnFocusPreview}}</span>
					</button>
					<button type="button" class="btn btn-default ec-hidden" id="easy-compose-ep-zen-toggle" title="{{$l10n.btnEpZen}}" onclick="AdvancedComposerToggleEpZen()">
						<i class="ri ri-image-line"></i> <span class="ec-btn-text">{{$l10n.btnEpZen}}</span>
					</button>
					{{/if}}
					<button type="submit" class="btn btn-primary pull-right" id="comment-edit-submit-{{$id}}" name="submit" tabindex="9"><i class="ri ri-send-plane-line"></i> {{$l10n.submit}}</button>
				</div>
				<div class="jotplugins">
					{{$jotplugins nofilter}}
				</div>
			</div>

			<div id="comment-edit-preview-{{$id}}" class="comment-edit-preview" style="display:none;"></div>

			<div class="modal fade" id="permissions-modal-{{$id}}" tabindex="-1" role="dialog" aria-labelledby="permissions-modal-title-{{$id}}">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
							<h4 class="modal-title" id="permissions-modal-title-{{$id}}">{{$l10n.visibility_title}}</h4>
						</div>
						<div class="modal-body">
							{{if $type == 'post'}}
							{{$acl_selector nofilter}}
							{{include file="field_checkbox.tpl" field=$sensitive}}
							{{if $scheduled_at}}{{$scheduled_at nofilter}}{{/if}}
							{{if $created_at}}{{$created_at nofilter}}{{/if}}
							{{else}}
							<input type="hidden" name="circle_allow" value="{{$circle_allow}}" />
							<input type="hidden" name="contact_allow" value="{{$contact_allow}}" />
							<input type="hidden" name="circle_deny" value="{{$circle_deny}}" />
							<input type="hidden" name="contact_deny" value="{{$contact_deny}}" />
							{{/if}}
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-primary" data-dismiss="modal">{{$l10n.submit}}</button>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
<script>
	dzFactory.setupDropzone('#dropzone-{{$id}}', 'comment-edit-text-{{$id}}');

	function preview_comment_toggle(id, originalText) {
		var previewPane = document.getElementById('comment-edit-preview-' + id);
		var btnTextSpan = document.getElementById('preview-btn-text-' + id);

		if (document.body.classList.contains('wide-editor-active')) {
			if (document.body.classList.contains('wide-preview-active')) {
				document.body.classList.remove('wide-preview-active');
				previewPane.style.display = 'none';
				btnTextSpan.textContent = originalText;
			} else {
				document.body.classList.add('wide-preview-active');
				previewPane.style.display = 'block';
				btnTextSpan.textContent = "Close preview";
				updateWidePreview();
			}
			sizeWideEditor();
			return;
		}

		if (previewPane.style.display === 'block') {
			previewPane.style.display = 'none';
			btnTextSpan.textContent = originalText;
		} else {
			preview_comment(id);
			btnTextSpan.textContent = "Close preview";
			previewPane.style.display = 'block';
		}
	}

	window.wideEditorPreviewTimer = null;

	function updateWidePreview() {
		var previewPane = document.getElementById('comment-edit-preview-{{$id}}');
		if (!document.body.classList.contains('wide-editor-active') || !previewPane || previewPane.style.display === 'none') {
			return;
		}
		$("#profile-rotator").show();
		$.post(
			"item",
			$("#comment-edit-form-{{$id}}").serialize() + '&preview=1',
			function (data) {
				if (data.preview) {
					$("#comment-edit-preview-{{$id}}").html(data.preview);
					$("#comment-edit-preview-{{$id}} a").click(function () { return false; });
				}
				$("#profile-rotator").hide();
			},
			"json"
		);
	}

	function sizeWideEditor() {
		var textarea = document.getElementById('comment-edit-text-{{$id}}');
		var height = Math.max(300, window.innerHeight - textarea.getBoundingClientRect().top - 140);
		// Pin height and maxHeight to keep the autosize library from expanding the textarea
		textarea.style.height = height + 'px';
		textarea.style.maxHeight = height + 'px';
		var previewPane = document.getElementById('comment-edit-preview-{{$id}}');
		if (previewPane) {
			previewPane.style.maxHeight = height + 'px';
		}
	}

	function toggleWideEditor() {
		var active = document.body.classList.toggle('wide-editor-active');
		var previewPane = document.getElementById('comment-edit-preview-{{$id}}');

		if (active) {
			if (previewPane && previewPane.style.display === 'block') {
				document.body.classList.add('wide-preview-active');
			}
			sizeWideEditor();
			return;
		}

		document.body.classList.remove('wide-preview-active');
		if (previewPane) {
			previewPane.style.maxHeight = '';
		}
		var wideTextarea = document.getElementById('comment-edit-text-{{$id}}');
		wideTextarea.style.height = '';
		wideTextarea.style.maxHeight = '';
		sizeExpandableTextareas();
	}

	window.wideEditorToggleBtn = null;

	window.onDocumentReady("body", function() {
		var toggleBtn = document.getElementById('wide-editor-toggle');
		// In SPA mode the button is re-created on navigation, rebind when the element changes
		if (toggleBtn && toggleBtn !== window.wideEditorToggleBtn) {
			toggleBtn.addEventListener('click', toggleWideEditor);
			window.wideEditorToggleBtn = toggleBtn;
		}
	});

	if (window.wideEditorResize) {
		window.removeEventListener("resize", window.wideEditorResize);
	}
	window.wideEditorResize = function () {
		if (document.body.classList.contains('wide-editor-active')) {
			sizeWideEditor();
		}
	};
	window.addEventListener("resize", window.wideEditorResize);

	if (!window.wideEditorEscapeBound) {
		window.wideEditorEscapeBound = true;
		document.addEventListener("keydown", function (e) {
			if (e.key !== "Escape" || !document.body.classList.contains('wide-editor-active')) {
				return;
			}
			if (document.querySelector('.modal.in')) {
				return;
			}
			toggleWideEditor();
		});
	}

	function togglePermissions() {
		$('#permissions-modal-{{$id}}').modal('show');
	}

	window.permissionsToggleBtn = null;

	window.onDocumentReady("body", function() {
		var permissionsBtn = document.getElementById('toggle-permissions');
		if (permissionsBtn !== window.permissionsToggleBtn) {
			permissionsBtn.addEventListener('click', togglePermissions);
			window.permissionsToggleBtn = permissionsBtn;
		}
	});

	function autosizeTextarea(textarea) {
		textarea.style.height = "auto";
		textarea.style.height = (textarea.scrollHeight) + "px";
	}

	function clearDraft(textarea) {
		localStorage.removeItem(`comment-edit-text-${textarea.id}`);
		localStorage.removeItem(`last-saved-${textarea.id}`);
	}

	function sizeExpandableTextareas() {
		document.querySelectorAll(".expandable-textarea").forEach(function(textarea) {
			autosizeTextarea(textarea);
		});
	}

	window.onDocumentReady("body", function() {
		var textareas = document.querySelectorAll(".expandable-textarea");

		textareas.forEach(function(textarea) {
			textarea.addEventListener("input", function() {
				if (this.id === 'comment-edit-text-{{$id}}' && document.body.classList.contains('wide-editor-active')) {
					sizeWideEditor();
				} else {
					autosizeTextarea(this);
				}
				if (this.id === 'comment-edit-text-{{$id}}') {
					// Debounce interval as in the Advanced Composer preview
					clearTimeout(window.wideEditorPreviewTimer);
					window.wideEditorPreviewTimer = setTimeout(updateWidePreview, 2000);
				}
			});
		});

		if (document.fonts && document.fonts.ready) {
			document.fonts.ready.then(function() {
				sizeExpandableTextareas();
			});
		}
		window.addEventListener("load", sizeExpandableTextareas);

		textareas.forEach(function(textarea) {
			const savedContent = localStorage.getItem(`comment-edit-text-${textarea.id}`);
			const lastSaved = localStorage.getItem(`last-saved-${textarea.id}`);

			if (savedContent && lastSaved) {
				const currentTime = new Date().getTime();
				const timeElapsed = currentTime - parseInt(lastSaved, 10);

				if (timeElapsed <= 600000) {
					textarea.value = savedContent;
				} else {
					clearDraft(textarea);
				}
			}
		});

		sizeExpandableTextareas();
	});

	window.composeFormSubmitting = false;

	// The inline script re-runs on SPA navigation, replace the previous timer and handler
	if (window.composeDraftSaver) {
		clearInterval(window.composeDraftSaver);
	}
	window.composeDraftSaver = setInterval(function () {
		document.querySelectorAll(".expandable-textarea").forEach(function(textarea) {
			if (textarea.value.trim() !== "") {
				localStorage.setItem(`comment-edit-text-${textarea.id}`, textarea.value);
				localStorage.setItem(`last-saved-${textarea.id}`, new Date().getTime().toString());
			}
		});
	}, 5000);

	function setFormSubmitting() {
		window.composeFormSubmitting = true;
		document.querySelectorAll(".expandable-textarea").forEach(clearDraft);
	}

	if (window.composeConfirmUnload) {
		window.removeEventListener("beforeunload", window.composeConfirmUnload);
	}
	window.composeConfirmUnload = function (event) {
		if (window.composeFormSubmitting) {
			return;
		}
		var unsaved = Array.from(document.querySelectorAll(".expandable-textarea")).some(function(textarea) {
			return textarea.value.trim().length > 0;
		});
		if (unsaved) {
			event.preventDefault();
			event.returnValue = "";
		}
	};
	window.addEventListener("beforeunload", window.composeConfirmUnload);

	document.getElementById('comment-edit-form-{{$id}}').addEventListener('submit', setFormSubmitting);
</script>
