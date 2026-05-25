{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

	<div class="form-group field select">
		<label for="id_{{$field.0}}">{{$field.1}}</label>
		<select name="{{$field.0}}" id="id_{{$field.0}}" class="form-control" aria-describedby="{{$field.0}}_tip" {{$field.5 nofilter}}>
	{{foreach $field.4 as $opt => $val}}
		{{if $field.5 == "multiple"}}
			<option value="{{$opt}}" {{if in_array($opt, $field.2)}}selected{{/if}}>{{$val}}</option>
		{{else}}
			<option value="{{$opt}}" {{if $opt == $field.2}}selected{{/if}}>{{$val}}</option>
		{{/if}}
	{{/foreach}}
		</select>
	{{if $field.3}}
		<span class="help-block" id="{{$field.0}}_tip" role="tooltip">{{$field.3 nofilter}}</span>
	{{/if}}
	</div>
