{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="mail-list-wrapper">
	<span class="mail-subject {{if $seen}}seen{{else}}unseen{{/if}}"><a href="message/{{$id}}" class="mail-link">{{$subject}}</a></span>
	<span class="mail-from">{{$from_name}}</span>
	<span class="mail-date" title="{{$date}}">{{$ago}}</span>
	<span class="mail-count">{{$count}}</span>
	
	<a href="message/dropconv/{{$id}}" onclick="return confirmDelete();"  title="{{$delete}}" class="mail-delete icon s22 delete"></a>
</div>
