{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

	<div class="field radio">
		<div class="radio">
			<input type="radio" name="{{$field.0}}" id="id_{{$field.0}}_{{$field.2}}" value="{{$field.2}}" {{if $field.4}}checked{{/if}} tabindex="{{if $field.4}}0{{else}}-1{{/if}}" aria-describedby="{{$field.0}}_{{$field.2}}_tip">
			<label for="id_{{$field.0}}_{{$field.2}}">
				{{$field.1}}
				{{if $field.3}}
				<span class="help-block" id="{{$field.0}}_{{$field.2}}_tip" role="tooltip">{{$field.3 nofilter}}</span>
				{{/if}}
			</label>
		</div>
	</div>
