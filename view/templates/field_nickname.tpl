{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div id="id_nickname_wrapper" class="form-group field input">
  {{if !isset($label) || $label != false }}
  	<label for="id_nickname" id="label_nickname">{{$field.1 nofilter}}{{if $field.4}} <span class="required" title="{{$field.4}}">*</span>{{/if}}</label>
  {{/if}}
  <input id="id_nickname" class="form-control" name="{{$field.0}}" pattern="[a-zA-Z][a-zA-Z0-9_]*" type="text" value="{{$field.2}}" {{if $field.4}}required{{/if}} {{$field.5 nofilter}} aria-describedby="nickname_tip" {{if $field.7}}placeholder="{{$field.7}}"{{/if}}>
  {{if $field.3}}
  	<span class="help-block" id="nickname_tip" role="tooltip">{{$field.3 nofilter}}</span>
  {{/if}}
</div>
