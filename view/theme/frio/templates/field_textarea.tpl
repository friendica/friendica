{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
	<div class="form-group field textarea">
	{{if $field.1}}
		<label for="id_{{$field.0}}">{{$field.1}}{{if $field.4}} <span class="required" title="{{$field.4}}">*</span>{{/if}}</label>
	{{/if}}

	{{if $field.6}} {{* BBCode buttons *}}
		<div class="comment-icon-list">
			<div class="btn-group">
				<button type="button" class="btn btn-default icon bb-img" style="cursor: pointer;" title="{{$field.7.edimg}}" data-role="insert-formatting" data-comment=" " data-bbcode="img" data-id="id_{{$field.0}}">
					<i class="ri ri-image-line"></i>
				</button>
				<button type="button" class="btn btn-default emojis" style="cursor: pointer;" aria-label="{{$field.7.edemojis}}" title="{{$edemojis}}">
					<i class="ri ri-emotion-line"></i>
				</button>
			</div>

			<div class="btn-group">
				<button type="button" class="btn btn-default icon bb-url" style="cursor: pointer;" title="{{$field.7.edurl}}" data-role="insert-formatting" data-comment=" " data-bbcode="url" data-id="id_{{$field.0}}">
					<i class="ri ri-link"></i>
				</button>
				<button type="button" class="btn btn-default icon underline" style="cursor: pointer;" title="{{$field.7.eduline}}" data-role="insert-formatting" data-comment=" " data-bbcode="u" data-id="id_{{$field.0}}">
					<i class="ri ri-underline"></i>
				</button>
				<button type="button" class="btn btn-default icon italic" style="cursor: pointer;" title="{{$field.7.editalic}}" data-role="insert-formatting" data-comment=" " data-bbcode="i" data-id="id_{{$field.0}}">
					<i class="ri ri-italic"></i>
				</button>
				<button type="button" class="btn btn-default icon bold" style="cursor: pointer;"  title="{{$field.7.edbold}}" data-role="insert-formatting" data-comment=" " data-bbcode="b" data-id="id_{{$field.0}}">
					<i class="ri ri-bold"></i>
				</button>
				<button type="button" class="btn btn-default icon quote" style="cursor: pointer;" title="{{$field.7.edquote}}" data-role="insert-formatting" data-comment=" " data-bbcode="quote" data-id="id_{{$field.0}}">
					<i class="ri ri-double-quotes-l"></i>
				</button>
				<button type="button" class="btn btn-default icon code" style="cursor: pointer;" title="{{$field.7.edcode}}" data-role="insert-formatting" data-comment=" " data-bbcode="code" data-id="id_{{$field.0}}">
					<i class="ri ri-code-line"></i>
				</button>
			</div>
		</div>
	{{/if}}
	<textarea class="form-control text-autosize emojis-target" name="{{$field.0}}" id="id_{{$field.0}}" {{if $field.4}}required{{/if}} {{$field.5 nofilter}} aria-describedby="{{$field.0}}_tip">{{$field.2}}</textarea>
	{{if $field.3}}
		<span class="help-block" id="{{$field.0}}_tip" role="tooltip">{{$field.3 nofilter}}</span>
	{{/if}}
		<div class="clear"></div>
	</div>
