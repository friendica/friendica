{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="live-photos"></div>
<h3 id="photo-album-title"><a href="{{$album.0}}">{{$album.1}}</a></h3>

<div id="photo-edit-link-wrap">
{{if $tools}}
    {{if $tools.view}}
        <a id="photo-view-link" href="{{$tools.view.0}}">{{$tools.view.1}}</a>
    {{/if}}
    {{if $tools.edit}}
        <a id="photo-edit-link" href="{{$tools.edit.0}}">{{$tools.edit.1}}</a>
    {{/if}}
    {{if $tools.delete}}
        | <a id="photo-edit-link" href="{{$tools.delete.0}}">{{$tools.delete.1}}</a>
    {{/if}}
    {{if $tools.profile}}
        | <a id="photo-toprofile-link" href="{{$tools.profile.0}}">{{$tools.profile.1}}</a>
    {{/if}}
    {{if $tools.lock}}
        | <img src="images/lock_icon.gif" class="lockview" alt="{{$tools.lock}}" onclick="lockview(event, 'photo', {{$id}});" />
    {{/if}}
{{/if}}
</div>

<div id="photo-photo"><a href="{{$photo.href}}" title="{{$photo.title}}"><img src="{{$photo.src}}" /></a></div>
{{if $prevlink}}<div id="photo-prev-link"><a href="{{$prevlink.0}}">{{$prevlink.1 nofilter}}</a></div>{{/if}}
{{if $nextlink}}<div id="photo-next-link"><a href="{{$nextlink.0}}">{{$nextlink.1 nofilter}}</a></div>{{/if}}
<div id="photo-caption">{{$desc}}</div>
{{if $edit}}{{$edit nofilter}}{{/if}}

