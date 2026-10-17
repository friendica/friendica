{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
	<div class="field radio">
		<input type="radio" name="{{$field.0}}" id="id_{{$field.0}}_{{$field.2}}" value="{{$field.2}}" {{if $field.4}}checked{{/if}} aria-describedby="{{$field.0}}_{{$field.2}}_tip">
		<label for="id_{{$field.0}}_{{$field.2}}">{{$field.1}}</label>
		{{if $field.3}}
		<span class="field_help" role="tooltip" id="{{$field.0}}_{{$field.2}}_tip">{{$field.3 nofilter}}</span>
		{{/if}}
	</div>
