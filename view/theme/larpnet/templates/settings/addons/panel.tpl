{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
	<div class="section-subtitle-wrapper panel-heading" role="tab" id="{{$addon}}-settings-title">
		<h2>
			<button class="btn-link accordion-toggle{{if !$open}} collapsed{{/if}}" data-toggle="collapse" data-parent="#settings-addons" href="#{{$addon}}-settings-content" aria-expanded="false" aria-controls="{{$addon}}-settings-content">
				{{$title}}
			</button>
		</h2>
	</div>
	<div id="{{$addon}}-settings-content" class="panel-collapse collapse{{if $open}} in{{/if}}" role="tabpanel" aria-labelledby="{{$addon}}-settings-title">
		<div class="panel-body">
            {{$html nofilter}}
		</div>
		<div class="panel-footer">
{{if $submit}}
    {{if $submit|is_string}}
			<button type="submit" name="{{$addon}}-submit" class="btn btn-primary settings-submit" value="{{$submit}}">{{$submit}}</button>
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
