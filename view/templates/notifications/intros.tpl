{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<h2>{{$header}}</h2>

<div class="intro-wrapper" id="intro-{{$contact_id}}">

<p class="intro-desc">{{$str_notification_type}} {{$str_type}}</p>
  <img id="photo-{{$contact_id}}" class="intro-photo" src="{{$photo}}" width="175" height=175" title="{{$fullname}}" alt="{{$fullname}}" />
  <dl><dt>{{$lbl_url}}</dt><dd><a target="blank" href="{{$zrl}}">{{$url}}</a></dd></dl>
  {{if $location}}<dl><dt>{{$lbl_location}}</dt><dd>{{$location}}</dd></dl>{{/if}}
  {{if $keywords}}<dl><dt>{{$lbl_keywords}}</dt><dd>{{$keywords}}</dd></dl>{{/if}}
  {{if $about}}<dl><dt>{{$lbl_about}}</dt><dd>{{$about nofilter}}</dd></dl>{{/if}}
  <div class="intro-knowyou">{{$lbl_knowyou}} {{$knowyou}}</div>
  <div class="intro-note" id="intro-note-{{$contact_id}}">{{$note}}</div>
  <div class="intro-wrapper-end" id="intro-wrapper-end-{{$contact_id}}"></div>
  <form class="intro-form" action="notification/{{$intro_id}}" method="post">
    <button class="intro-submit-ignore" type="submit" name="submit" value="ignore"></button>
   {{if $discard}}<button class="intro-submit-discard" type="submit" name="submit" value="discard"></button>{{/if}}
  </form>
  <div class="intro-form-end"></div>

  <form class="intro-approve-form" action="{{$action}}" method="post">
  {{include file="field_checkbox.tpl" field=$hidden}}
  <div role="radiogroup" aria-labelledby="connection_type">
    <label id="connection_type">{{$lbl_connection_type}}</label>
    {{include file="field_radio.tpl" field=$friend}}
    {{include file="field_radio.tpl" field=$follower}}
  </div>

  <input type="hidden" name="dfrn_id" value="{{$dfrn_id}}">
  <input type="hidden" name="intro_id" value="{{$intro_id}}">
  <input type="hidden" name="contact_id" value="{{$contact_id}}">

  <button class="intro-submit-approve" type="submit" name="submit" value="{{$approve}}"></button>
  </form>
</div>
<div class="intro-end"></div>
