{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div class="generic-page-wrapper" id="jot-page-wrapper-{{$id}}">
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
            {{if $l10n.placeholdercategory}}
                <div id="jot-category-wrap">
                    <input name="category" id="jot-category" class="jothidden jotforms form-control" type="text" placeholder="{{$l10n.placeholdercategory}}" title="{{$l10n.placeholdercategory}}" value="{{$category}}" tabindex="2" dir="auto" />
                </div>
            {{/if}}

            <div class="comment-edit-bb-{{$id}} btn-toolbar clearfix" role="toolbar" style="margin-bottom: 15px;">
                <div class="pull-left">
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-default template-icon bb-img" aria-label="{{$l10n.edimg}}" title="{{$l10n.edimg}}" data-role="insert-formatting" data-bbcode="img" data-id="{{$id}}" tabindex="6">
                            <i class="fa fa-picture-o"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-default template-icon bb-attach" aria-label="{{$l10n.edattach}}" title="{{$l10n.edattach}}" ondragenter="return commentLinkDrop(event, {{$id}});" ondragover="return commentLinkDrop(event, {{$id}});" ondrop="commentLinkDropper(event);" onclick="commentGetLink({{$id}}, '{{$l10n.prompttext}}');" tabindex="7">
                            <i class="fa fa-paperclip"></i>
                        </button>
                    </div>
                </div>

                <div class="pull-right">
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-default template-icon bb-url" aria-label="{{$l10n.edurl}}" title="{{$l10n.edurl}}" onclick="insertFormatting('url',{{$id}});" tabindex="8">
                            <i class="fa fa-link"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-default template-icon underline" aria-label="{{$l10n.eduline}}" title="{{$l10n.eduline}}" onclick="insertFormatting('u',{{$id}});" tabindex="9">
                            <i class="fa fa-underline"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-default template-icon italic" aria-label="{{$l10n.editalic}}" title="{{$l10n.editalic}}" onclick="insertFormatting('i',{{$id}});" tabindex="10">
                            <i class="fa fa-italic"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-default template-icon bold" aria-label="{{$l10n.edbold}}" title="{{$l10n.edbold}}" onclick="insertFormatting('b',{{$id}});" tabindex="11">
                            <i class="fa fa-bold"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-default template-icon quote" aria-label="{{$l10n.edquote}}" title="{{$l10n.edquote}}" onclick="insertFormatting('quote',{{$id}});" tabindex="12">
                            <i class="fa fa-quote-left"></i>
                        </button>
                    </div>

                    <div class="btn-group">
                        <button id="button_emojipicker" type="button" class="btn btn-sm btn-default template-icon emojis" aria-label="{{$l10n.edemojis}}" title="{{$l10n.edemojis}}" tabindex="13">
                            <i class="fa fa-smile-o"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-default template-icon bb-url" aria-label="{{$l10n.contentwarn}}" title="{{$l10n.contentwarn}}" onclick="insertFormatting('abstract',{{$id}});" tabindex="14">
                            <i class="fa fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-default template-icon code" aria-label="{{$l10n.edcode}}" title="{{$l10n.edcode}}" onclick="insertFormatting('code',{{$id}});" tabindex="4">
                            <i class="fa fa-code"></i>
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
                    <div class="pull-left form-inline">
                        <button type="button" name="permissions" class="btn btn-sm btn-default template-icon" id="toggle-permissions" title="{{$l10n.toggle_permissions_tooltip}}" onclick="togglePermissions()" style="margin-right: 10px;" tabindex="5">
                            <i class="fa fa-ellipsis-h"></i> {{$l10n.toggle_permissions}}
                        </button>

                        <input type="text" name="location" class="form-control input-sm d-inline-block" id="jot-location" value="{{$location}}" placeholder="{{$l10n.location_set}}" tabindex="6" style="width: auto; display: inline-block; vertical-align: middle;" />

                        <button type="button" class="btn btn-sm btn-default template-icon" id="profile-location"
                            data-title-set="{{$l10n.location_set}}"
                            data-title-disabled="{{$l10n.location_disabled}}"
                            data-title-unavailable="{{$l10n.location_unavailable}}"
                            data-title-clear="{{$l10n.location_clear}}"
                            title="{{$l10n.location_set}}"
                            tabindex="7">
                            <i class="fa fa-map-marker" aria-hidden="true"></i>
                        </button>
                    </div>
                {{/if}}

                <div class="pull-right">
                    <span role="presentation" id="character-counter" class="grey text-info" style="margin-right: 10px;"></span>
                    <button type="button" class="btn btn-default" onclick="preview_comment_toggle({{$id}}, '{{$l10n.preview}}');" id="comment-edit-preview-link-{{$id}}" tabindex="8">
                        <i class="fa fa-eye"></i> <span id="preview-btn-text-{{$id}}">{{$l10n.preview}}</span>
                    </button>
                    <button type="submit" class="btn btn-primary" id="comment-edit-submit-{{$id}}" name="submit" tabindex="9">
                        <i class="fa fa-envelope"></i> {{$l10n.submit}}
                    </button>
                </div>
            </div>

            <div id="comment-edit-preview-{{$id}}" class="comment-edit-preview" style="display:none;"></div>

            <div class="modal fade" id="permissions-modal-{{$id}}" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">{{$l10n.visibility_title}}</h4>
                        </div>
                        <div class="modal-body">
                            <div id="permissions-section">
                                {{if $type == 'post'}}
                                    <h3>{{$l10n.visibility_title}}</h3>
                                    {{$acl_selector nofilter}}
                                    <div class="jotplugins">{{$jotplugins nofilter}}</div>
                                    {{if $scheduled_at}}{{$scheduled_at nofilter}}{{/if}}
                                    {{if $created_at}}{{$created_at nofilter}}{{/if}}
                                {{else}}
                                    <input type="hidden" name="circle_allow" value="{{$circle_allow}}"/>
                                    <input type="hidden" name="contact_allow" value="{{$contact_allow}}"/>
                                    <input type="hidden" name="circle_deny" value="{{$circle_deny}}"/>
                                    <input type="hidden" name="contact_deny" value="{{$contact_deny}}"/>
                                {{/if}}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
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
        if (previewPane.style.display === 'block') {
            previewPane.style.display = 'none';
            btnTextSpan.textContent = originalText;
        } else {
            preview_comment(id);
            btnTextSpan.textContent = "Close preview";
            previewPane.style.display = 'block';
        }
    }

    function togglePermissions() {
        $('#permissions-modal-{{$id}}').modal('show');
    }

    var formSubmitting = false;

    function clearManualSave(id) {
        localStorage.removeItem(`comment-edit-text-${id}`);
        localStorage.removeItem(`last-saved-${id}`);
    }

    document.getElementById('comment-edit-form-{{$id}}').addEventListener('submit', function() {
        formSubmitting = true;
        clearManualSave('comment-edit-text-{{$id}}'); 
    });

    window.addEventListener("beforeunload", function (event) {
        if (!formSubmitting) {
            var textField = document.getElementById('comment-edit-text-{{$id}}').value.trim();
            if (textField.length > 0) {
                event.returnValue = 'Discard changes?';
            }
        }
    });

    document.addEventListener("DOMContentLoaded", function() {
        var textarea = document.getElementById('comment-edit-text-{{$id}}');

        if (textarea) {
            const savedContent = localStorage.getItem(`comment-edit-text-${textarea.id}`);
            const lastSaved = localStorage.getItem(`last-saved-${textarea.id}`);

            if (savedContent && lastSaved) {
                const currentTime = new Date().getTime();
                if (currentTime - parseInt(lastSaved, 10) <= 600000) {
                    textarea.value = savedContent;
                } else {
                    clearManualSave(textarea.id);
                }
            }

            setInterval(() => {
                if (textarea.value.trim() !== "" && !formSubmitting) {
                    localStorage.setItem(`comment-edit-text-${textarea.id}`, textarea.value);
                    localStorage.setItem(`last-saved-${textarea.id}`, new Date().getTime().toString());
                }
            }, 5000);
        }

        // Textarea-Resizing Logik (einmalig für alle Textareas)
        var textareas = document.querySelectorAll(".expandable-textarea");
        textareas.forEach(function(tx) {
            tx.addEventListener("input", function() {
                this.style.height = "auto";
                this.style.height = (this.scrollHeight) + "px";
            });

            tx.style.height = "auto";
            tx.style.height = (tx.scrollHeight) + "px";
        });
    });
</script>
