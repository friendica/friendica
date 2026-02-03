{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div class="notif-item {{if !$item_seen}}unseen{{/if}}" {{if $item_seen}}aria-hidden="true"{{/if}}>
	<a href="{{$notification.link}}">
		<img src="{{$notification.image}}" aria-hidden="true" class="notif-image">
		<div>
			<p class="notif-text">{{$notification.text}}</p>
			<p class="notif-when"><small data-toggle="tooltip" title="{{$notification.when}}">{{$notification.ago}}</small></p>
		</div>
	</a>
</div>
