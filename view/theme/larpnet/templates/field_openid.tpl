{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="id_{{$field.0}}_wrapper" class="form-group field input openid">
	<p id="openid-header">{{$field.1}}</p>
	<input id="id_{{$field.0}}" class="form-control" name="{{$field.0}}" type="text" placeholder="{{$field.5}}" value="{{$field.2}}" {{if $field.4}}readonly{{/if}} aria-describedby="{{$field.0}}_tip">
	{{if $field.3}}
	<span id="{{$field.0}}_tip" class="help-block" role="tooltip">{{$field.3 nofilter}}</span>
	{{/if}}
</div>
