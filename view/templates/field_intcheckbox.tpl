{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}


	<div class="field checkbox">
		<input type="checkbox" name="{{$field.0}}" id="id_{{$field.0}}" value="{{$field.3}}" {{if $field.2}}checked{{/if}} aria-describedby="{{$field.0}}_tip">
		<label for="id_{{$field.0}}">{{$field.1}}</label>
		{{if $field.4}}
		<span class="field_help" role="tooltip" id="{{$field.0}}_tip">{{$field.4 nofilter}}</span>
		{{/if}}
	</div>
