{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

{{if $field.5=="preview"}}
	<script type="text/javascript">$(document).ready(function(){ previewTheme($("#id_{{$field.0}}")[0]); });</script>
{{/if}}
	<div id="field-theme-select-container" class="form-group field select">
		<label for="id_{{$field.0}}">{{$field.1}}</label>
		<select class="form-control" name="{{$field.0}}" id="id_{{$field.0}}" {{if $field.5=="preview"}}onchange="previewTheme(this);"{{/if}} aria-describedby="{{$field.0}}_tip">
	{{foreach $field.4 as $opt=>$val}}
			<option value="{{$opt}}" {{if $opt==$field.2}}selected{{/if}}>{{$val}}</option>
	{{/foreach}}
		</select>
	{{if $field.3}}
		<span class="help-block" id="{{$field.0}}_tip" role="tooltip">{{$field.3 nofilter}}</span>
	{{/if}}
	{{if $field.5=="preview"}}
		<div id="theme-preview"></div>
	{{/if}}
	</div>
