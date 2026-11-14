{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div class="field checkbox" id="profile-publish-wrapper-{{$instance}}">
	<input type="hidden" name="profile_publish_{{$instance}}" value="0">
	<input type="checkbox" name="profile_publish_{{$instance}}" id="profile-publish-{{$instance}}" value="1" {{$checked}}>
	<label id="profile-publish-label-{{$instance}}" for="profile-publish-{{$instance}}">{{$pubdesc}}</label>
</div>
