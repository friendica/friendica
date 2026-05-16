{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

	<div class="field input" id="wrapper_{{$field.0}}">
		{{if $label != false}}
			<label for="id_{{$field.0}}">{{$field.1}}{{if $field.4}} <span class="required" title="{{$field.4}}">*</span>{{/if}}</label>
		{{/if}}
		<input type="{{$field.6|default:'text'}}" name="{{$field.0}}" id="id_{{$field.0}}" value="{{$field.2}}"{{if $field.4}} required{{/if}} {{$field.5 nofilter}} aria-describedby="{{$field.0}}_tip" dir="auto" {{if $field.7}}placeholder="{{$field.7}}"{{/if}}>
	{{if $field.3}}
		<span class="field_help" role="tooltip" id="{{$field.0}}_tip">{{$field.3 nofilter}}</span>
	{{/if}}
	</div>
