{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<nav id="follow-sidebar" class="widget">
  <h3>{{$connect}}</h3>
  <div id="connect-desc">{{$desc nofilter}}</div>
  <form action="contact/follow" method="post">
    <input id="side-follow-url" type="text" name="follow-url" value="{{$value}}" size="24" placeholder="{{$hint}}" title="{{$hint}}" /><input id="side-follow-submit" type="submit" name="submit" value="{{$follow}}" />
  </form>
</nav>