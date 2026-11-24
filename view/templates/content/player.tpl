{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="media-player">
	<div class="media-player-{{$type}}" style="{{if $type == 'video'}}--media-ratio:{{$ratio}};{{else}}width:min(500px, 100vw - 48px);{{/if}}">
		{{$player nofilter}}
	</div>
</div>
