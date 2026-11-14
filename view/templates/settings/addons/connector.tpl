{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<summary class="settings-heading"><h2><img class="connector{{if !$enabled}}-disabled{{/if}}" src="{{$image}}" /><span class="connector">{{$title}}</span></h2></summary>
	{{$html nofilter}}
	<div class="clear"></div>
{{if $submit}}
	<div class="settings-submit-wrapper panel-footer">
    {{if $submit|is_string}}
		<button type="submit" name="{{$connector}}-submit" class="btn btn-primary settings-submit" value="{{$submit}}">{{$submit}}</button>
    {{else}}
        {{$count = 1}}
		{{foreach $submit as $name => $label}}{{if $label}}
			{{if $count == 1}}
		<button type="submit" name="{{$name}}" class="btn btn-primary settings-submit" value="{{$label}}">{{$label}}</button>
            {{/if}}
            {{if $count == 2}}
		<div class="btn-group" role="group" aria-label="...">
			{{/if}}
            {{if $count != 1}}
			<button type="submit" name="{{$name}}" class="btn btn-default settings-submit" value="{{$label}}">{{$label}}</button>
            {{/if}}
            {{$count = $count + 1}}
        {{/if}}{{/foreach}}
		{{if $submit|count > 1}}
		</div>
		{{/if}}
    {{/if}}
	</div>
{{/if}}
