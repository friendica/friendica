{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="media-player">
	<div class="media-player-{{$type}}" {{if $type == 'video'}}style="--media-ratio:{{$ratio}};"{{/if}}>
		{{$player nofilter}}
	</div>
</div>
