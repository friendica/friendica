{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="event">

{{if $event.item.author_name}}<a href="{{$event.item.author_link}}"><img src="{{$event.item.author_avatar}}" height="32" width="32" />{{$event.item.author_name}}</a>{{/if}}
{{$event.html nofilter}}
{{if $event.plink.orig}}<a href="{{$event.plink.orig}}" title="{{$event.plink.orig_title}}" target="_blank" rel="noopener noreferrer" class="plink-event-link icon s22 remote-link"></a>{{/if}}
{{if $event.edit}}<a href="{{$event.edit.0}}" title="{{$event.edit.1}}" class="edit-event-link icon s22 pencil"></a>{{/if}}
{{if $event.copy}}<a href="{{$event.copy.0}}" title="{{$event.copy.1}}" class="copy-event-link icon s22 copy"></a>{{/if}}
{{if $event.drop}}<a href="{{$event.drop.0}}" onclick="return confirmDelete();" title="{{$event.drop.1}}" class="drop-event-link icon s22 delete post-action"></a>{{/if}}
</div>
<div class="clear"></div>
