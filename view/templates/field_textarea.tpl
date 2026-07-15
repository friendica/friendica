{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

	<div class="field textarea">
		<label for="id_{{$field.0}}">{{$field.1}}{{if $field.4}} <span class="required" title="{{$field.4}}">*</span>{{/if}}</label>
		<textarea class="form-control text-autosize" name="{{$field.0}}" id="id_{{$field.0}}" {{if $field.4}}required{{/if}} {{$field.5 nofilter}} placeholder="{{$field.6}}" aria-describedby="{{$field.0}}_tip" dir="auto">{{$field.2}}</textarea>
	{{if $field.3}}
		<span class="field_help" role="tooltip" id="{{$field.0}}_tip">{{$field.3 nofilter}}</span>
	{{/if}}
	</div>
