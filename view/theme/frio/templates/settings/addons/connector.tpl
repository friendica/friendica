{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<details class="panel"{{if $open}} open{{/if}}>
	<summary class="section-subtitle-wrapper panel-heading accordion-toggle" id="{{$connector}}-settings-title"><h2><img class="connector{{if !$enabled}}-disabled{{/if}}" src="{{$image}}" /> {{$title}}</h2></summary>
	<div id="{{$connector}}-settings-content">
		<div class="panel-body">
			{{$html nofilter}}
		</div>
		<div class="panel-footer">
{{if $submit}}
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
{{/if}}
		</div>
	</div>
</details>
