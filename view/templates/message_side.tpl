{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<nav id="message-sidebar" class="widget">
  <div id="message-new"><a href="{{$new.url}}" accesskey="m" class="{{if $new.sel}}newmessage-selected{{/if}}">{{$new.label}}</a> </div>

  {{if $tabs}}
    <div id="message-preview">
      {{$tabs nofilter}}
    </div>
  {{/if}}

</nav>