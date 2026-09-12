{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
</p>
<div class="poll">
{{if $can_vote}}
	<form id="poll-form-{{$item_id}}" class="poll-form" onsubmit="doPollVote({{$item_id}}, '{{$vote_recorded|escape:'javascript'}}', '{{$vote_failed|escape:'javascript'}}'); return false;">
	<ul class="poll-options">
	{{foreach $options as $option}}
		<li class="poll-option">
			<div class="poll-option-bar" style="width: {{$option.percent}}%;"></div>
			<label>
				<input type="{{if $multiple}}checkbox{{else}}radio{{/if}}" name="poll-option-{{$item_id}}" value="{{$option.id}}">
				{{$option.vote}}
			</label>
		</li>
	{{/foreach}}
	</ul>
	<button type="submit" class="poll-vote-button">{{$vote_label}}</button>
	</form>
{{else}}
	<ul class="poll-options">
	{{foreach $options as $option}}
		<li class="poll-option">
			<div class="poll-option-bar" style="width: {{$option.percent}}%;"></div>
			{{$option.vote}}
		</li>
	{{/foreach}}
	</ul>
{{/if}}
</div>
{{$summary}}
</p>
