{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

	<div class="field checkbox" id="div_id_{{$field.0}}">
			<input type="hidden" name="{{$field.0}}" value="0">
			<input type="checkbox" name="{{$field.0}}" id="id_{{$field.0}}" value="1" {{if $field.2}}checked{{/if}} {{if $field.3}}aria-describedby="{{$field.0}}_tip"{{/if}} {{$field.4 nofilter}}>
			<label for="id_{{$field.0}}">
				{{$field.1}}
				{{if $field.3}}
				<span class="help-block" id="{{$field.0}}_tip" role="tooltip">{{$field.3 nofilter}}</span>
				{{/if}}
			</label>
	</div>
