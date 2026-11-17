{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div class="intro-wrapper">
  <p class="intro-desc">{{$str_notification_type}} {{$str_type}}</p>
  {{if $madeby}}<div class="intro-madeby">{{$lbl_madeby}} {{$madeby}}</div>{{/if}}
  <div class="intro-fullname">{{$fullname}}</div>
  <a class="intro-url-link" href="{{$url}}"><img class="intro-photo lframe" src="{{$photo}}" width="175" height="175" title="{{$fullname}}" alt="{{$fullname}}" /></a>
  <div class="intro-note">{{$note}}</div>
  <div class="intro-wrapper-end"></div>
  <form class="intro-form" action="notification/{{$intro_id}}" method="post">
    <button class="intro-submit-ignore" type="submit" name="submit" value="ignore">{{$ignore}}</button>
    <button class="intro-submit-discard" type="submit" name="submit" value="discard">{{$discard}}</button>
  </form>
  <div class="intro-form-end"></div>

  <form class="intro-approve-form" action="{{$action}}" method="post">
    <input type="hidden" name="url" value="{{$url}}">
    <input type="hidden" name="dfrn-url" value="{{$dfrn_url}}">
    <input type="hidden" name="intro_id" value="{{$intro_id}}">
    <button class="intro-submit-approve" type="submit" name="submit" value="{{$approve}}">{{$approve}}</button>
  </form>
</div>
<div class="intro-end"></div>
