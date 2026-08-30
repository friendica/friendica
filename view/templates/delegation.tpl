{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="delegation" class="generic-page-wrapper">
	<h2>{{$l10n.title}}</h2>
	<p id="identity-delegation-desc">{{$l10n.desc}}</p>
	<p id="identity-delegation-choose">{{$l10n.choose}}</p>

	<div id="identity-selector-wrapper" role="menu">
		<form action="delegation" method="post">

{{foreach $identities as $identity}}
		<div class="identity-match-wrapper {{if $identity.selected}}selected-identity{{/if}}" id="identity-match-{{$identity.uid}}">
			<div class="identity-match-photo" id="identity-match-photo-{{$identity.uid}}">
				<button type="submit" name="identity" value="{{$identity.uid}}" title="{{$identity.username}}">
					<img src="{{$identity.thumb}}" alt="{{$identity.username}}" />
					{{if $identity.notifications}}<span class="delegation-notify badge">{{$identity.notifications}}</span>{{/if}}
				</button>
			</div>

			<div class="identity-match-break"></div>

			<div class="identity-match-desc">
				<div class="identity-match-name" id="identity-match-name-{{$identity.uid}}">
					{{if $identity.selected}}
						{{$identity.username}}
					{{else}}
						<button type="submit" name="identity" class="btn-link" value="{{$identity.uid}}">{{$identity.username}}</button>
					{{/if}}
				</div>
				<div class="identity-match-details" id="identity-match-nick-{{$identity.uid}}">({{$identity.nickname}})</div>
			</div>
			<div class="identity-match-end"></div>
		</div>
{{/foreach}}

		<div class="identity-match-break"></div>

		</form>
	</div>

	<p>
		<a href="settings/delegation" class="btn btn-primary" up-follow="false"><i class="ri ri-settings-3-line"></i> {{$l10n.settings_label}}</a>
	</p>
</div>
